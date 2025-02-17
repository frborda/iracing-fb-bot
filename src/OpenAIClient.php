<?php

namespace Src;

class OpenAIClient {
    private $apiKey;
    private $assistantId;
    private $baseUrl = "https://api.openai.com/v1/chat/completions";

    public function __construct($apiKey, $assistantId, $model, $context) {
        $this->apiKey = $apiKey;
        $this->assistantId = $assistantId;
        $this->model = $model;
        $this->context = $context;
    }

    public function sendMessage($message, $temperature = 0.7) {
        $data = [
            "model" => $this->model,
            "messages" => [
                ["role" => "system", "content" => $this->context],
                ["role" => "user", "content" => $message]
            ],
            "temperature" => $temperature
        ];

        $response = $this->makeRequest($data);
        return $response["choices"][0]["message"]["content"] ?? "Error: No se recibió respuesta.";
    }

    private function makeRequest($data) {
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
    
        if ($error) {
            die("Error de cURL: $error");
        }
    
        if ($httpCode !== 200) {
            die("Error HTTP: $httpCode - Respuesta: " . $response);
        }
    
        return json_decode($response, true);
    }
    
}


?>
