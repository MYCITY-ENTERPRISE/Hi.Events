<?php

namespace HiEvents\Services\Application\Handlers\Order\DTO;

use HiEvents\DataTransferObjects\Attributes\CollectionOf;
use HiEvents\DataTransferObjects\BaseDTO;
use HiEvents\Services\Domain\Tracking\DTO\AdTrackingContextDTO;
use Illuminate\Support\Collection;

class CompleteOrderDTO extends BaseDTO
{
    /**
     * @param  Collection<CompleteOrderProductDataDTO>  $products
     */
    public function __construct(
        public CompleteOrderOrderDTO $order,
        #[CollectionOf(CompleteOrderProductDataDTO::class)]
        public Collection $products,
        public int $event_id,
        public ?AdTrackingContextDTO $ad_tracking_context = null,
    ) {}
}
