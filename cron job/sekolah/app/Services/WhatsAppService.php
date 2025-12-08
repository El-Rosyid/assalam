<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class WhatsAppService
{
    protected $client;
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('whatsapp.api_url');
        $this->apiKey = config('whatsapp.api_key');
        $this->client = new Client();
    }

    public function sendMessage($recipient, $message)
    {
        try {
            $response = $this->client->post($this->apiUrl . '/send', [
                'json' => [
                    'to' => $recipient,
                    'message' => $message,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            // Handle the exception (log it, rethrow it, etc.)
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function handleApiResponse($response)
    {
        // Process the API response as needed
        if (isset($response['success']) && $response['success']) {
            return true;
        }

        return false;
    }
}