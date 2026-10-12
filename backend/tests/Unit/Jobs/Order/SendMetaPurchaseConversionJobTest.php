<?php

namespace Tests\Unit\Jobs\Order;

use HiEvents\Exceptions\Meta\MetaConversionsApiException;
use HiEvents\Jobs\Order\SendMetaPurchaseConversionJob;
use HiEvents\Services\Domain\Tracking\MetaPurchaseConversionService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\NullLogger;
use Tests\TestCase;

class SendMetaPurchaseConversionJobTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_sends_the_purchase_for_the_order(): void
    {
        $service = Mockery::mock(MetaPurchaseConversionService::class);
        $service->shouldReceive('sendPurchase')->once()->with(42);

        $job = (new SendMetaPurchaseConversionJob(42))->withFakeQueueInteractions();
        $job->handle($service, new NullLogger);

        $job->assertNotReleased();
        $job->assertNotFailed();
    }

    public function test_a_meta_error_releases_the_job_for_a_retry_instead_of_throwing(): void
    {
        $job = (new SendMetaPurchaseConversionJob(42))->withFakeQueueInteractions();
        $job->handle($this->failingService(), new NullLogger);

        $job->assertReleased(60);
        $job->assertNotFailed();
    }

    public function test_a_meta_error_on_the_last_attempt_is_logged_without_retrying_or_throwing(): void
    {
        $job = (new SendMetaPurchaseConversionJob(42))->withFakeQueueInteractions();
        $job->job->attempts = 3;
        $job->handle($this->failingService(), new NullLogger);

        $job->assertNotReleased();
        $job->assertNotFailed();
    }

    private function failingService(): MetaPurchaseConversionService
    {
        $service = Mockery::mock(MetaPurchaseConversionService::class);
        $service->shouldReceive('sendPurchase')->andThrow(new MetaConversionsApiException('Invalid OAuth access token'));

        return $service;
    }
}
