<?php

namespace HiEvents\Services\Domain\CheckInList\DTO;

class CheckInResultDTO
{
    public function __construct(
        public readonly ?object $checkIn = null,
        public readonly ?string $error = null,
        // False when $checkIn is an existing check-in, returned with an "already checked in" error.
        public readonly bool $created = false,
    ) {}
}
