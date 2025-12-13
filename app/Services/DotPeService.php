<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DotPeService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('DOTPE_API_KEY');
    }

    public function getTemplates()
    {
        $url = 'https://api.dotpe.in/api/comm/public/enterprise/v1/templates';

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Dotpe-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendTemplateMessage($wabaNumber, $recipients, $templateName, $language, $params)
    {
        $url = 'https://api.dotpe.in/api/comm/public/enterprise/v1/wa/send';

        $payload = [
            'template' => [
                'name' => $templateName,
                'language' => $language,
            ],
            'wabaNumber' => $wabaNumber,
            'recipients' => $recipients,
            'source' => 'crm',
            'params' => $params,
        ];
        // dd($payload);
        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Dotpe-Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
