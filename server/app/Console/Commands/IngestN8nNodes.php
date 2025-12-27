<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class IngestN8nNodes extends Command{

    protected $signature = 'app:ingest-n8n-nodes';
    protected $description = 'Fetch n8n nodes from GitHub and store in Qdrant';

    public function handle(){
        $this->info("Fetching node list from GitHub...");

        // get nodes folder from n8n github (contains essentially all nodes)
        $response = Http::withHeaders([
            'User-Agent' => 'Laravel-RAG'
        ])->get('https://api.github.com/repos/n8n-io/n8n/contents/packages/nodes-base/nodes');

        if (!$response->ok()) {
            $this->error('GitHub API failed');
            return;
        }
        
        $nodes = $response->json();
        // for each folder in nodes get required data (description/properties/operations...)
        foreach ($nodes as $node) {
            if ($node['type'] !== 'dir') continue;

            $nodeName = $node['name'];
            $this->info("Processing $nodeName");

            $url = "https://raw.githubusercontent.com/n8n-io/n8n/master/packages/nodes-base/nodes/$nodeName/$nodeName.description.ts";

            $ts = Http::get($url)->body();
            if (!$ts) continue;

            $parsed = $this->parseDescriptionFile($ts, $nodeName);
            if (!$parsed) continue;

            $this->storeInQdrant($parsed);
        }

        $this->info("Done.");
    }

    private function parseDescriptionFile(string $ts, string $nodeName): ?array{
        // extract displayName along with some meta data
        preg_match("/displayName:\s*'([^']+)'/", $ts, $display);
        preg_match("/description:\s*'([^']+)'/", $ts, $description);
        preg_match("/group:\s*\[([^\]]+)\]/", $ts, $group);
        preg_match("/version:\s*([0-9]+)/", $ts, $version);

        return [
            "id" => strtolower($nodeName),
            "node_name" => $nodeName,
            "display_name" => $display[1] ?? $nodeName,
            "description" => $description[1] ?? '',
            "group" => $group[1] ?? '',
            "version" => intval($version[1] ?? 1),
            "raw" => $ts
        ];
    }

    private function storeInQdrant(array $node){
        $vector = $this->embed($node['display_name'] . " " . $node['description']);

        Http::put(env('QDRANT_CLUSTER_ENDPOINT') . '/collections/n8n_catalog/points', [
            "points" => [
                [
                    "id" => $node['id'],
                    "vector" => $vector,
                    "payload" => $node
                ]
            ]
        ]);
    }

    private function embed(string $text): array{
        $response = Http::withToken(env('QDRANT_API_KEY'))
            ->post('https://api.openai.com/v1/embeddings', [
                "model" => "text-embedding-3-large",
                "input" => $text
            ]);

        return $response['data'][0]['embedding'];
    }
}

