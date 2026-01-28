<?php

namespace App\Console\Commands;

use App\Console\Commands\Services\IngestionService;
use App\Console\Commands\Services\prompt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IngestN8nNodes extends Command{

    private string $resumeFile = __DIR__ . '/ingestion_resume.json';
    private ?string $lastIngestedPath = null;


    protected $signature = 'app:ingest-all-n8n-nodes';
    protected $description = 'Recursively ingest ALL n8n .node.ts files into Qdrant';

    private int $ingested = 0;
    private int $skipped = 0;
    private int $aiUsed = 0;
    private int $parsed = 0;

    public function handle(){

        if (file_exists($this->resumeFile)){
            $data = json_decode(file_get_contents($this->resumeFile), true);
            $this->lastIngestedPath = $data['last_ingested'] ?? null;
            $this->info('Resuming ingestion from: ' . ($this->lastIngestedPath ?? 'start'));
        }

        $root = 'https://api.github.com/repos/n8n-io/n8n/contents/packages/nodes-base/nodes';

        $this->info('Starting recursive ingestion of n8n nodes...');
        $this->crawl($root);

        $this->newLine();
        $this->info('Ingestion complete.');
        $this->info("Parsed: {$this->parsed}");
        $this->info("Ingested: {$this->ingested}");
        $this->warn("AI fallback used: {$this->aiUsed}");
        $this->warn("Skipped: {$this->skipped}");

        if (file_exists($this->resumeFile)){
            unlink($this->resumeFile);
        }
    }

    private function crawl(string $url): void{
        /** @var Response */
        $response = Http::withHeaders([
            'User-Agent'    => 'Laravel-RAG',
            'Authorization' => 'token ' . env('GITHUB_TOKEN'),
        ])->timeout(30)->get($url);

        if(!$response->ok()){
            $this->warn("Failed to fetch: {$url}");
            return;
        }

        foreach($response->json() as $item){
            if($item['type'] === 'dir'){
                $this->crawl($item['url']);
                continue;
            }

            if ($item['type'] === 'file' && str_ends_with($item['name'], '.node.ts')){
                if ($this->lastIngestedPath && $this->lastIngestedPath !== $item['path']) continue;
                $this->processFile($item);
            }
        }
    }

    private function processFile(array $item): void{
        $this->lastIngestedPath = null; // found, start ingesting from here
        
        $this->ingestNodeTsFile($item['download_url'], $item['path']);
        try{
            file_put_contents($this->resumeFile, json_encode(['last_ingested' => $item['path']] , JSON_PRETTY_PRINT));
        }catch(\Exception $ex){
            $this->warn("Could not save resume file: {$ex->getMessage()}");
        }
    }

    private function ingestNodeTsFile(string $url, string $path): void{
        $this->line("→ {$path}");

        $content = Http::get($url)->body();
        if (!$content) {
            $this->skipped++;
            return;
        }

        $parsed = $this->parseNodeTs($content);
        if (!$parsed) {
            $this->skipped++;
            return;
        }

        $this->parsed++;

        // AI fallback if critical fields missing
        if(empty($parsed['description']) || empty($parsed['display_name'])){
            $this->warn('  ↳ Missing fields, using AI fallback');
            $aiData = $this->aiFallback($parsed, $path);

            $parsed = array_merge($parsed, $aiData);
            $this->aiUsed++;
        }

        $domain = $this->extractDomain($path);

        $payload = array_merge($parsed, $domain, [
            'source' => 'n8n',
        ]);

        $this->storeInQdrant($payload);
        $this->ingested++;
    }

    private function parseNodeTs(string $content): ?array{
        if (!preg_match('/export class (\w+)/', $content, $classMatch)) {
            return null;
        }

        preg_match("/displayName:\s*'([^']+)'/", $content, $displayName);
        preg_match("/name:\s*'([^']+)'/", $content, $name);
        preg_match("/description:\s*'([^']+)'/", $content, $description);
        preg_match("/group:\s*\[([^\]]+)\]/", $content, $groupMatch);
        preg_match("/usableAsTool:\s*(true|false)/", $content, $usable);

        $groups = isset($groupMatch[1])
            ? array_map(fn($g) => trim(str_replace("'", '', $g)), explode(',', $groupMatch[1]))
            : [];

        return [
            'class_name'     => $classMatch[1],
            'node_id'        => $name[1] ?? strtolower($classMatch[1]),
            'display_name'   => $displayName[1] ?? null,
            'description'    => $description[1] ?? null,
            'groups'         => $groups,
            'node_type'      => in_array('trigger', $groups) ? 'trigger' : 'action',
            'usable_as_tool' => ($usable[1] ?? 'false') === 'true',
        ];
    }

    private function extractDomain(string $path): array{
        $parts = explode('/', $path);
        $nodesIndex = array_search('nodes', $parts);

        $namespaceParts = array_slice($parts, $nodesIndex + 1, -1);

        return [
            'service'   => strtolower($namespaceParts[0] ?? 'core'),
            'namespace' => implode('/', array_map('strtolower', $namespaceParts)),
        ];
    }

    private function aiFallback(array $parsed, string $path): array{
        $node_type = $parsed["node_type"];
        $class_name = $parsed["class_name"];
        $prompt = prompt::getAIFallBackDescriptionPrompt($class_name, $path, $node_type);

        $result = $this->callOpenAI($prompt);
        if(!$result){
            $this->error("Failed to get AI response... defaulting");
            return[
                "description" => "",
                "display_name" => ""
            ];
        }

        return [
            'display_name' => $result['display_name'] ?? $parsed['class_name'],
            'description'  => $result['description'] ?? '',
        ];
    }

    private function storeInQdrant(array $node): void{
        $text = $this->getEmbeddingText($node);

        $denseVector = IngestionService::embed($text);
        $sparseVector = IngestionService::buildSparseVector($text);

        Http::withHeaders([
            'api-key' => env('QDRANT_API_KEY'),
        ])->put(
            rtrim(env('QDRANT_CLUSTER_ENDPOINT'), '/') . '/collections/n8n_catalog/points?wait=true',
            [
                'points' => [[
                    'id'     => (string) Str::uuid(),
                    'vector' => [
                        'dense-vector' => $denseVector,
                        'text-sparse'  => $sparseVector,
                    ],
                    'payload' => $node,
                ]],
            ]
        );
    }

    private function callOpenAI($prompt){
        $model = env("OPENAI_MODEL");

        /** @var Response $response */
        $response = Http::withToken(env("OPENAI_API_KEY"))
                ->timeout(90)
                ->post("https://api.openai.com/v1/chat/completions", [
                    "model" => $model,
                    "temperature" => 0,
                    "messages" => [
                        ["role" => "system", "content" => "You are an n8n node documentor"],
                        ["role" => "user", "content" => $prompt]
                    ]
                ]);
 
        $results = trim($response->json("choices.0.message.content"));
        $decoded = json_decode($results , true);

        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null){
            return $decoded;
        }


        $decodedFallBack = $this->aiMarkdownFallback($results);

        return $decodedFallBack ?? null;
    }

    private function aiMarkdownFallback($results){
        if(preg_match('/\{.*\}|\[.*\]/s', $results, $m)){// AI may have included some markdown or explanation
            $candidate = $m[0];
            $decoded2 = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded2 !== null) {
                return $decoded2;
            }
        }else{
            return null;
        }
    }

    private function getEmbeddingText(array $node): string{
        return implode("\n", array_filter([
            "n8n {$node['node_type']} node",
            $node['display_name'],
            $node['description'],
            "Service: " . ucfirst($node['service']),
            "Groups: " . implode(', ', $node['groups']),
        ]));
    }
}
