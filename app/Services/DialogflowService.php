<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Auth\ApplicationDefaultCredentials;
use Google\Auth\Credentials\ServiceAccountCredentials;

class DialogflowService
{
    protected $projectId;
    protected $language;
    protected $sessionId;
    protected $credentialsPath;
    protected $accessToken;
    protected $region;

    public function __construct()
    {
        $this->projectId = config('dialogflow.project_id');
        $this->language = config('dialogflow.language');
        $this->sessionId = config('dialogflow.session_id');
        $this->credentialsPath = config('dialogflow.credentials_path');
        $this->region = config('dialogflow.region');

        $this->accessToken = $this->getGoogleAccessToken();
    }

    protected function getGoogleAccessToken()
    {
        try {
            $jsonKey = json_decode(file_get_contents($this->credentialsPath), true);
            
            if (!$jsonKey) {
                throw new \Exception('Failed to read credentials file');
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/cloud-platform',
                $jsonKey
            );

            $token = $credentials->fetchAuthToken();
            return $token['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Dialogflow authentication error: ' . $e->getMessage());
            return null;
        }
    }

    public function detectIntent($userMessage)
    {
        if (!$this->accessToken) {
            throw new \Exception('Failed to authenticate with Google Cloud');
        }

$hostname = $this->region === 'global'
    ? 'dialogflow.googleapis.com'
    : "{$this->region}-dialogflow.googleapis.com";

$url = "https://{$hostname}/v2/projects/{$this->projectId}/locations/{$this->region}/agent/sessions/{$this->sessionId}:detectIntent";

        // Using the default 'draft' environment and '-' as the user ID
        $sessionPath = "projects/{$this->projectId}/agent/environments/draft/users/-/sessions/{$this->sessionId}";
        
        try {
            $response = Http::withToken($this->accessToken)
                ->post($url, [
                    'query_input' => [
                        'text' => [
                            'text' => $userMessage,
                            'language_code' => $this->language,
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Dialogflow API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'session_path' => $sessionPath
                ]);
                throw new \Exception('Dialogflow API error: ' . $response->body());
            }
            return $response->json()["queryResult"]["parameters"];
        } catch (\Exception $e) {
            Log::error('Dialogflow request failed', [
                'error' => $e->getMessage(),
                'session_path' => $sessionPath
            ]);
            throw $e;
        }
    }
}
