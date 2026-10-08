<?php

namespace Tests\Unit\Services\Infrastructure\Meta;

use HiEvents\Exceptions\Meta\MetaConversionsApiException;
use HiEvents\Services\Infrastructure\Meta\MetaConversionsApiClient;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaConversionsApiClientTest extends TestCase
{
    private function makeClient(): MetaConversionsApiClient
    {
        return new MetaConversionsApiClient(app(HttpClient::class), new Repository([
            'services' => [
                'meta' => [
                    'graph_api_base_url' => 'https://graph.facebook.com/',
                    'graph_api_version' => 'v23.0',
                    'request_timeout_seconds' => 5,
                ],
            ],
        ]));
    }

    public function test_posts_events_to_the_pixel_endpoint(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $this->makeClient()->sendEvents('1588480782483786', 'EAAtoken', [['event_name' => 'Purchase']]);

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://graph.facebook.com/v23.0/1588480782483786/events'
                && $request['access_token'] === 'EAAtoken'
                && $request['data'] === [['event_name' => 'Purchase']];
        });
    }

    public function test_throws_when_meta_rejects_the_request(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 400),
        ]);

        $this->expectException(MetaConversionsApiException::class);

        $this->makeClient()->sendEvents('1588480782483786', 'bad-token', [['event_name' => 'Purchase']]);
    }

    public function test_throws_when_meta_cannot_be_reached(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::failedConnection(),
        ]);

        $this->expectException(MetaConversionsApiException::class);

        $this->makeClient()->sendEvents('1588480782483786', 'EAAtoken', [['event_name' => 'Purchase']]);
    }
}
