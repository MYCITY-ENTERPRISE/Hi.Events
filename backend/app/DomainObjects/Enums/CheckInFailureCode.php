<?php

namespace HiEvents\DomainObjects\Enums;

/**
 * Why a check-in or a check-out was refused. Returned as error_code by the authenticated check-in routes, so that
 * API clients don't have to read the translated message.
 */
enum CheckInFailureCode: string
{
    use BaseEnum;

    case CHECK_IN_LIST_EXPIRED = 'CHECK_IN_LIST_EXPIRED';
    case CHECK_IN_LIST_NOT_ACTIVE = 'CHECK_IN_LIST_NOT_ACTIVE';
    case CHECK_IN_LIST_NOT_FOUND = 'CHECK_IN_LIST_NOT_FOUND';
    case ATTENDEE_PRODUCT_NOT_ON_LIST = 'ATTENDEE_PRODUCT_NOT_ON_LIST';
    case ATTENDEE_OTHER_EVENT = 'ATTENDEE_OTHER_EVENT';
    case ATTENDEE_OCCURRENCE_MISMATCH = 'ATTENDEE_OCCURRENCE_MISMATCH';
    case ATTENDEE_CODE_UNKNOWN = 'ATTENDEE_CODE_UNKNOWN';
    case ATTENDEE_NOT_CHECKED_IN = 'ATTENDEE_NOT_CHECKED_IN';
}
