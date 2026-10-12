<?php

namespace HiEvents\Jobs\Order;

use HiEvents\Exceptions\Meta\MetaConversionsApiException;
use HiEvents\Services\Domain\Tracking\MetaPurchaseConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

class SendMetaPurchaseConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const RETRY_DELAYS_SECONDS = [60, 300];

    public int $tries = 3;

    public function __construct(public readonly int $orderId) {}

    public function handle(MetaPurchaseConversionService $service, LoggerInterface $logger): void
    {
        try {
            $service->sendPurchase($this->orderId);
        } catch (MetaConversionsApiException $exception) {
            $logger->warning('Meta Conversions API purchase could not be sent', [
                'order_id' => $this->orderId,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release(self::RETRY_DELAYS_SECONDS[$this->attempts() - 1]);
            }
        }
    }
}
