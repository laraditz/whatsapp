<?php

namespace Laraditz\Whatsapp\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Laraditz\Whatsapp\DTOs\Account;
use Laraditz\Whatsapp\Exceptions\WhatsappApiException;
use Laraditz\Whatsapp\Exceptions\WhatsappAuthException;
use Laraditz\Whatsapp\Exceptions\WhatsappRateLimitException;
use Laraditz\Whatsapp\Models\WhatsappApiLog;

class WhatsappClient
{
    protected array $config;

    public function __construct(
        protected Account $account,
        protected string $baseUrl,
        protected string $apiVersion,
    ) {
        $this->config = config('whatsapp', []);
    }

    public function get(string $endpoint, array $data = []): Response
    {
        return $this->execute(method: 'get', endpoint: $endpoint, data: $data);
    }

    public function post(string $endpoint, array $data = []): Response
    {
        return $this->execute(method: 'post', endpoint: $endpoint, data: $data);
    }

    public function put(string $endpoint, array $data = []): Response
    {
        return $this->execute(method: 'put', endpoint: $endpoint, data: $data);
    }

    public function delete(string $endpoint, array $data = []): Response
    {
        return $this->execute(method: 'delete', endpoint: $endpoint, data: $data);
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    protected function execute(string $method, string $endpoint, array $data = []): Response
    {
        $url = "{$this->baseUrl}/{$this->apiVersion}/{$endpoint}";

        $startTime = microtime(true);

        $pendingRequest = Http::withToken($this->account->accessToken)
            ->acceptJson();

        $response = match ($method) {
            'get' => $pendingRequest->get($url, $data),
            'post' => $pendingRequest->post($url, $data),
            'put' => $pendingRequest->put($url, $data),
            'delete' => $pendingRequest->delete($url, $data),
        };

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        $this->logRequest(
            method: $method,
            endpoint: $endpoint,
            requestPayload: $data,
            responsePayload: $response->json() ?? [],
            statusCode: $response->status(),
            durationMs: $durationMs,
        );

        if ($response->failed()) {
            $this->handleErrorResponse(response: $response);
        }

        return $response;
    }

    protected function handleErrorResponse(Response $response): never
    {
        $error = $response->json('error', []);

        $code = $error['code'] ?? 0;
        $subCode = $error['error_subcode'] ?? null;

        if (in_array($code, [190]) || in_array($subCode, [463, 460])) {
            throw WhatsappAuthException::fromResponse($error);
        }

        if (in_array($code, [4, 80007])) {
            throw WhatsappRateLimitException::fromResponse($error);
        }

        throw WhatsappApiException::fromResponse($error);
    }

    protected function logRequest(
        string $method,
        string $endpoint,
        array $requestPayload,
        array $responsePayload,
        int $statusCode,
        int $durationMs,
    ): void {
        if (! ($this->config['logging']['api_requests'] ?? false)) {
            return;
        }

        WhatsappApiLog::create([
            'account_name' => $this->account->name,
            'method' => strtoupper($method),
            'endpoint' => $endpoint,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
        ]);
    }
}
