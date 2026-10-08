<?php

namespace HiEvents\Listeners\Order;

use HiEvents\Events\OrderStatusChangedEvent;
use HiEvents\Jobs\Order\SendMetaPurchaseConversionJob;

class SendMetaPurchaseConversionListener
{
    public function handle(OrderStatusChangedEvent $changedEvent): void
    {
        $order = $changedEvent->order;

        if (! $order->isOrderCompleted() || ! is_array($order->getAdTrackingContext())) {
            return;
        }

        dispatch(new SendMetaPurchaseConversionJob($order->getId()))->afterCommit();
    }
}
