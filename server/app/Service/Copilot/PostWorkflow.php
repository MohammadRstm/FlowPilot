<?php

namespace App\Service\Copilot;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class PostWorkflow{

    public static function postWorkflow(array $workflow , Model $user ,?callable $trace){

        if(!$user->n8n_api_key || !$user->n8n_base_url){
            return;
        }
        
        try {
            $url = rtrim($user->n8n_base_url, '/') . '/rest/workflows';
            $api_key = $user->n8n_api_key;

            $workflow_json = json_encode($workflow);

            self::postUsingCurl($url, $api_key, $workflow_json , $trace);
           
        } catch (\Throwable $e) {
            throw new Exception("Failed to post workflow: " . $e->getMessage());
        }

    }

    private static function postUsingCurl(string $url, string $api_key,string $workflow_json , ?callable $trace){

        $ch = curl_init($url);
        self::setCurlOptions($ch, $api_key, $workflow_json);
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

       self::handleCurlResponse($ch, $httpcode, $response , $trace);

        curl_close($ch);
    }

    private static function setCurlOptions($ch , string $api_key, string $workflow_json){
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $workflow_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-N8N-API-KEY: ' . $api_key,
            'Content-Length: ' . strlen($workflow_json)
        ));
    }

    private static function handleCurlResponse($ch,int $httpcode, $response , ?callable $trace){
        if($httpcode == 200 || $httpcode == 201){
            $trace && $trace("postWorkflowSuccess" ,["message" => 'Workflow posted successfully to n8n instance.']);
        } 

        if(curl_errno($ch)){
            $trace && $trace("postWorkflowFailed" ,["message" => 'Failed to post workflow, please check your credentials and n8n instance status.']);
        }else{
            throw new Exception('Failed to post workflow, HTTP Status Code: ' . $httpcode . ', Response: ' . $response);
        }
    }
}
