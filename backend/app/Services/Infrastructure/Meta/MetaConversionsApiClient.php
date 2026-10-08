<?php

declare(strict_types=1);

namespace HiEvents\Services\Infrastructure\Meta;

use HiEvents\Exceptions\Meta\MetaConversionsApiException;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Factory as HttpClient;
use Throwable;

class MetaConversionsApiClient
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly Repository $config,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $events
     *
     * @throws MetaConversionsApiException
     */
    public function sendEvents(string $pixelId, string $accessToken, array $events): void
    {
        $url = sprintf(
            '%s/%s/%s/events',
            rtrim((string) $this->config->get('services.meta.graph_api_base_url'), '/'),
            $this->config->get('services.meta.graph_api_version'),
            rawurlencode($pixelId),
        );

        try {
            $response = $this->http
                ->timeout((int) $this->config->get('services.meta.request_timeout_seconds'))
                ->asJson()
                ->post($url, [
                    'data' => $events,
                    'access_token' => $accessToken,
                ]);
        } catch (Throwable $exception) {
            throw new MetaConversionsApiException(
                __('Could not reach the Meta Conversions API: :message', ['message' => $exception->getMessage()]),
                previous: $exception,
            );
        }

        if ($response->successful()) {
            return;
        }

        throw new MetaConversionsApiException(
            __('Meta rejected the Conversions API request (:status): :body', [
                'status' => $response->status(),
                'body' => $response->body(),
            ])
        );
    }
}
