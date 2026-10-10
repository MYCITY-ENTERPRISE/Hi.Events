<?php

namespace HiEvents\Http\Request\CheckInList;

use HiEvents\DomainObjects\Enums\AttendeeCheckInActionType;
use HiEvents\Http\Request\BaseRequest;
use Illuminate\Validation\Rule;

class CreateAttendeeCheckInRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'attendees' => ['required', 'array'],
            'attendees.*.public_id' => ['required', 'string'],
            'attendees.*.action' => ['required', 'string', Rule::in(AttendeeCheckInActionType::valuesArray())],
        ];
    }

    /**
     * Whether every attendee of the request is only checked in, without marking their order as paid.
     */
    public function onlyChecksIn(): bool
    {
        return collect($this->validated('attendees'))
            ->every(fn (array $attendee) => $attendee['action'] === AttendeeCheckInActionType::CHECK_IN->value);
    }
}
