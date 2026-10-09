<?php

namespace Tests\Feature\Webhooks;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use HiEvents\DomainObjects\EventOccurrenceDomainObject;
use HiEvents\DomainObjects\Status\WebhookStatus;
use HiEvents\DomainObjects\WebhookDomainObject;
use HiEvents\Jobs\Webhook\SecureCallWebhookJob;
use HiEvents\Repository\Interfaces\EventOccurrenceRepositoryInterface;
use HiEvents\Repository\Interfaces\WebhookRepositoryInterface;
use HiEvents\Services\Infrastructure\DomainEvents\Enums\DomainEventType;
use HiEvents\Services\Infrastructure\Webhook\DTO\WebhookTargetDTO;
use HiEvents\Services\Infrastructure\Webhook\WebhookDispatchService;
use HiEvents\Services\Infrastructure\Webhook\WebhookResponseHandlerService;
use HiEvents\Services\Infrastructure\Webhook\WebhookUrlValidator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class WebhookDispatchRetriesTest extends TestCase
{
    private const EVENT_ID = 41;

    private const WEBHOOK_URL = 'https://hooks.example.com/hi-events';

    private const WEBHOOK_SECRET = 'test-webhook-secret';

    /** @var array<int, array{request: RequestInterface}> */
    private array $sentRequests = [];

    private MockHandler $httpResponses;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.webhook_queue_name' => 'webhook-queue']);

        $this->httpResponses = new MockHandler;
        $stack = HandlerStack::create($this->httpResponses);
        $stack->push(Middleware::history($this->sentRequests));
        $this->app->instance(Client::class, new Client(['handler' => $stack]));

        $validator = Mockery::mock(WebhookUrlValidator::class);
        $validator->shouldReceive('validate')->andReturn(new WebhookTargetDTO(
            host: 'hooks.example.com',
            port: 443,
            ipAddresses: ['93.184.216.34'],
        ));
        $this->app->instance(WebhookUrlValidator::class, $validator);

        $this->app->instance(WebhookResponseHandlerService::class, Mockery::spy(WebhookResponseHandlerService::class));

        $webhookRepository = Mockery::mock(WebhookRepositoryInterface::class);
        $webhookRepository->shouldReceive('findEnabledByEventId')
            ->with(self::EVENT_ID)
            ->andReturn(collect([
                (new WebhookDomainObject)
                    ->setId(7)
                    ->setUrl(self::WEBHOOK_URL)
                    ->setEventTypes([DomainEventType::OCCURRENCE_CANCELLED->value])
                    ->setStatus(WebhookStatus::ENABLED->name)
                    ->setSecret(self::WEBHOOK_SECRET),
            ]));
        $this->app->instance(WebhookRepositoryInterface::class, $webhookRepository);

        $occurrenceRepository = Mockery::mock(EventOccurrenceRepositoryInterface::class);
        $occurrenceRepository->shouldReceive('findById')
            ->andReturn((new EventOccurrenceDomainObject)
                ->setId(3)
                ->setEventId(self::EVENT_ID)
                ->setShortId('oc_test')
                ->setStartDate('2026-11-14 20:00:00')
                ->setStatus('CANCELLED'));
        $this->app->instance(EventOccurrenceRepositoryInterface::class, $occurrenceRepository);
    }

    public function test_the_webhook_call_is_queued_when_the_default_connection_is_not_sync(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();

        $this->dispatchOccurrenceCancelled();

        Queue::assertPushedOn('webhook-queue', SecureCallWebhookJob::class, function (SecureCallWebhookJob $job) {
            // dispatchSync() also goes through the queue manager, on the "sync" connection.
            return $job->connection !== 'sync'
                && $job->tries === 3
                && $job->webhookUrl === self::WEBHOOK_URL;
        });
        $this->assertCount(0, $this->sentRequests, 'A queued webhook must not be sent before a worker runs it.');
    }

    public function test_the_webhook_is_sent_immediately_when_the_default_connection_is_sync(): void
    {
        config(['queue.default' => 'sync']);
        $this->httpResponses->append(new Response(200));

        $this->dispatchOccurrenceCancelled();

        $this->assertCount(1, $this->sentRequests);
    }

    public function test_the_signed_payload_carries_a_delivery_id(): void
    {
        config(['queue.default' => 'sync']);
        $this->httpResponses->append(new Response(200));

        $this->dispatchOccurrenceCancelled();

        $this->assertCount(1, $this->sentRequests);
        $request = $this->sentRequests[0]['request'];
        $body = (string) $request->getBody();
        $envelope = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('delivery_id', $envelope);
        $this->assertTrue(Str::isUuid($envelope['delivery_id']));
        $this->assertSame(DomainEventType::OCCURRENCE_CANCELLED->value, $envelope['event_type']);
        $this->assertSame(
            hash_hmac('sha256', $body, self::WEBHOOK_SECRET),
            $request->getHeaderLine('Signature'),
            'delivery_id must be inside the signed body.',
        );
    }

    public function test_a_failed_call_is_retried_with_the_same_delivery_id_and_a_valid_signature(): void
    {
        config(['queue.default' => 'database']);
        Queue::fake();
        $this->httpResponses->append(new Response(500), new Response(200));

        $this->dispatchOccurrenceCancelled();

        $pushed = Queue::pushed(SecureCallWebhookJob::class);
        $this->assertCount(1, $pushed);
        // What the queue stores, and hands back to the worker on every attempt.
        $storedJob = serialize($pushed->first());

        $firstAttempt = unserialize($storedJob);
        $firstAttempt->withFakeQueueInteractions()->attempts = 1;
        $firstAttempt->handle();
        $firstAttempt->assertReleased(delay: 10);

        $secondAttempt = unserialize($storedJob);
        $secondAttempt->withFakeQueueInteractions()->attempts = 2;
        $secondAttempt->handle();
        $secondAttempt->assertNotReleased();
        $secondAttempt->assertNotFailed();

        $this->assertCount(2, $this->sentRequests);
        $bodies = array_map(fn (array $sent) => (string) $sent['request']->getBody(), $this->sentRequests);

        $this->assertSame($bodies[0], $bodies[1], 'A retry must send the same signed body.');
        $deliveryId = json_decode($bodies[0], true, flags: JSON_THROW_ON_ERROR)['delivery_id'] ?? null;
        $this->assertTrue(Str::isUuid((string) $deliveryId));

        foreach ($this->sentRequests as $sent) {
            $this->assertSame(
                hash_hmac('sha256', (string) $sent['request']->getBody(), self::WEBHOOK_SECRET),
                $sent['request']->getHeaderLine('Signature'),
            );
        }
    }

    public function test_webhook_tries_defaults_to_three(): void
    {
        $this->assertSame(3, $this->webhookServerConfigWithTries(null)['tries']);
    }

    public function test_webhook_tries_is_read_from_the_environment(): void
    {
        $this->assertSame(4, $this->webhookServerConfigWithTries('4')['tries']);
    }

    public function test_webhook_tries_is_capped_at_five(): void
    {
        $this->assertSame(5, $this->webhookServerConfigWithTries('9')['tries']);
    }

    public function test_webhook_tries_is_at_least_one(): void
    {
        $this->assertSame(1, $this->webhookServerConfigWithTries('0')['tries']);
    }

    private function dispatchOccurrenceCancelled(): void
    {
        $this->app->make(WebhookDispatchService::class)
            ->dispatchOccurrenceWebhook(DomainEventType::OCCURRENCE_CANCELLED, 3);
    }

    private function webhookServerConfigWithTries(?string $tries): array
    {
        $previous = [$_ENV['WEBHOOK_TRIES'] ?? null, $_SERVER['WEBHOOK_TRIES'] ?? null, getenv('WEBHOOK_TRIES')];

        $this->setEnvironmentValue($tries);

        try {
            return require base_path('config/webhook-server.php');
        } finally {
            [$_ENV['WEBHOOK_TRIES'], $_SERVER['WEBHOOK_TRIES']] = [$previous[0], $previous[1]];
            if ($previous[0] === null) {
                unset($_ENV['WEBHOOK_TRIES']);
            }
            if ($previous[1] === null) {
                unset($_SERVER['WEBHOOK_TRIES']);
            }
            putenv($previous[2] === false ? 'WEBHOOK_TRIES' : 'WEBHOOK_TRIES='.$previous[2]);
        }
    }

    private function setEnvironmentValue(?string $value): void
    {
        if ($value === null) {
            unset($_ENV['WEBHOOK_TRIES'], $_SERVER['WEBHOOK_TRIES']);
            putenv('WEBHOOK_TRIES');

            return;
        }

        $_ENV['WEBHOOK_TRIES'] = $value;
        $_SERVER['WEBHOOK_TRIES'] = $value;
        putenv('WEBHOOK_TRIES='.$value);
    }
}
