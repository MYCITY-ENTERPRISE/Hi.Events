<?php

namespace HiEvents\Services\Domain\CheckInList\DTO;

use HiEvents\DataTransferObjects\BaseDTO;
use HiEvents\DataTransferObjects\ErrorBagDTO;
use Illuminate\Support\Collection;

class CreateAttendeeCheckInsResponseDTO extends BaseDTO
{
    /**
     * @param  Collection  $attendeeCheckIns  the check-ins of the attendees, created by this call or already there
     * @param  Collection  $createdCheckIns  the check-ins created by this call only
     */
    public function __construct(
        public Collection $attendeeCheckIns,
        public ErrorBagDTO $errors,
        public Collection $createdCheckIns,
    ) {}
}
