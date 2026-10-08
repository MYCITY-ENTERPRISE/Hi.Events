<?php

namespace HiEvents\Jobs\Order;

use HiEvents\Exceptions\Meta\MetaConversionsApiException;
use HiEvents\Services\Domain\Tracking\MetaPurchaseConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMetaPurchaseConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public readonly int $orderId) {}

    /**
     * @throws MetaConversionsApiException
     */
    public function handle(MetaPurchaseConversionService $service): void
    {
        $service->sendPurchase($this->orderId);
    }
}
