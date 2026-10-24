<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin OpenAI-compatible chat-completions client. Exactly one call site for
 * the provider so timeouts, retries and secret hygiene stay centralized.
 */
final class AiClient
{
    private const MAX_ATTEMPTS = 3;

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @param-out list<string> $errors detail lines safe for the UI
     */
    public function chat(array $messages, bool $jsonMode = false, array &$errors = []): string
    {
        if (! (bool) config('services.ai.enabled')) {
            throw new GenerationException('AI generation is not configured (AI_API_KEY missing).');
        }

        $attempts = min(max((int) config('services.ai.max_attempts', 3), 1), self::MAX_ATTEMPTS);
        $lastError = 'unknown error';

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $body = $this->buildRequest()
                    ->post('/chat/completions', [
                        'model' => (string) config('services.ai.model'),
                        'messages' => $messages,
                        'temperature' => 0.4,
                        ...($jsonMode ? ['response_format' => ['type' => 'json_object']] : []),
                    ])
                    ->throw()
                    ->json();

                $content = $body['choices'][0]['message']['content'] ?? null;

                if (is_string($content) && trim($content) !== '') {
                    return $content;
                }

                $lastError = $body === null ? 'empty response body' : 'no message content in response';
            } catch (ConnectionException|RequestException $e) {
                // Timeout, 4xx/5xx. Log without headers/secrets, show generic text.
                Log::warning('AI chat request failed', ['attempt' => $attempt, 'status' => $e->getCode()]);
                $lastError = match (true) {
                    (int) $e->getCode() >= 500 => 'provider service error',
                    (int) $e->getCode() === 429 => 'provider rate limited',
                    (int) $e->getCode() >= 400 => 'provider rejected the request',
                    default => 'connection problem',
                };
            }
        }

        $errors[] = $lastError;

        throw new GenerationException("AI provider did not produce usable output after {$attempts} attempts: {$lastError}");
    }

    private function buildRequest(): PendingRequest
    {
        return Http::withToken((string) config('services.ai.api_key'))
            ->baseUrl((string) config('services.ai.base_url'))
            ->timeout((int) config('services.ai.timeout', 120))
            ->connectTimeout(10)
            ->acceptJson();
    }
}
