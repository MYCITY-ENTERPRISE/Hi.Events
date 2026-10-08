<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Tracking\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;

class AdTrackingContextDTO extends BaseDataObject
{
    public function __construct(
        public readonly ?string $fbp = null,
        public readonly ?string $fbc = null,
        public readonly ?string $event_source_url = null,
        public readonly ?string $client_ip_address = null,
        public readonly ?string $client_user_agent = null,
    ) {}
}
