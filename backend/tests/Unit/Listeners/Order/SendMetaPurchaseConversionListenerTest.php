<?php

namespace Tests\Unit\Listeners\Order;

use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Events\OrderStatusChangedEvent;
use HiEvents\Jobs\Order\SendMetaPurchaseConversionJob;
use HiEvents\Listeners\Order\SendMetaPurchaseConversionListener;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class SendMetaPurchaseConversionListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    public function test_queues_the_conversion_for_a_completed_order_with_tracking_context(): void
    {
        $this->handle($this->makeOrder(OrderStatus::COMPLETED, ['fbp' => 'fb.1.1.1']));

        Bus::assertDispatched(
            SendMetaPurchaseConversionJob::class,
            fn (SendMetaPurchaseConversionJob $job) => $job->orderId === 42,
        );
    }

    public function test_does_not_queue_for_an_order_awaiting_offline_payment(): void
    {
        $this->handle($this->makeOrder(OrderStatus::AWAITING_OFFLINE_PAYMENT, ['fbp' => 'fb.1.1.1']));

        Bus::assertNotDispatched(SendMetaPurchaseConversionJob::class);
    }

    public function test_does_not_queue_for_an_order_without_tracking_context(): void
    {
        $this->handle($this->makeOrder(OrderStatus::COMPLETED, null));

        Bus::assertNotDispatched(SendMetaPurchaseConversionJob::class);
    }

    private function handle(OrderDomainObject $order): void
    {
        (new SendMetaPurchaseConversionListener)->handle(new OrderStatusChangedEvent($order));
    }

    private function makeOrder(OrderStatus $status, ?array $trackingContext): OrderDomainObject
    {
        return (new OrderDomainObject)
            ->setId(42)
            ->setStatus($status->name)
            ->setAdTrackingContext($trackingContext);
    }
}
