<?php

declare(strict_types=1);

namespace Frumle;

/**
 * HTTP client for communicating with the Frumle backend API.
 */
class ApiClient
{
    private const DEFAULT_API_URL = 'https://dev-doc-726dc734499e.herokuapp.com';

    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = getenv('FRUMLE_API_URL') ?: self::DEFAULT_API_URL;
    }

    /**
     * Verify an API key with the backend (without requiring it to be saved).
     *
     * @return array Status response with quota and usage info
     * @throws \RuntimeException on failure
     */
    public function verifyApiKey(string $apiKey): array
    {
        return $this->request('GET', '/api/v1/auth/status/apikey', null, $apiKey);
    }

    /**
     * Check the saved API key status.
     *
     * @return array Status response
     * @throws \RuntimeException on failure
     */
    public function checkStatus(): array
    {
        $apiKey = Config::getApiKey();
        if ($apiKey === null) {
            throw new \RuntimeException("No API key found. Run 'frumle add-key <api-key>' first.");
        }
        return $this->verifyApiKey($apiKey);
    }

    /**
     * Submit codebase for analysis.
     *
     * @param array $payload Request body containing files, directory, projectName, etc.
     * @return array Analysis response
     * @throws \RuntimeException on failure
     */
    public function analyzeCodebase(array $payload): array
    {
        $apiKey = Config::getApiKey();
        if ($apiKey === null) {
            throw new \RuntimeException("No API key found. Run 'frumle add-key <api-key>' first.");
        }
        return $this->request('POST', '/api/v1/analyze/apikey', $payload, $apiKey);
    }

    /**
     * @throws \RuntimeException
     */
    private function request(string $method, string $endpoint, ?array $body, string $apiKey): array
    {
        $url = rtrim($this->apiUrl, '/') . $endpoint;

        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize cURL');
        }

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);

        if ($method === 'POST' && $body !== null) {
            $json = json_encode($body);
            if ($json === false) {
                curl_close($ch);
                throw new \RuntimeException('Failed to encode request body as JSON');
            }
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('HTTP request failed: ' . $error);
        }

        $data = json_decode((string) $response, true);

        if ($httpCode === 401) {
            throw new \RuntimeException('Invalid API key. Please check your key and try again.');
        }

        if ($httpCode === 429) {
            $msg = $data['message'] ?? 'Quota exceeded. Check your usage with: frumle status';
            throw new \RuntimeException($msg);
        }

        if ($httpCode >= 400) {
            $msg = $data['message'] ?? $data['error'] ?? "HTTP {$httpCode} error";
            throw new \RuntimeException($msg);
        }

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid JSON response from server');
        }

        return $data;
    }
}
