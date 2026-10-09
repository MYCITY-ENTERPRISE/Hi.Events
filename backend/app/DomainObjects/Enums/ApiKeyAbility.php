<?php

namespace HiEvents\DomainObjects\Enums;

/**
 * The closed list of abilities an account API key can hold. The routes each ability opens are listed in
 * config/api-keys.php. There is no wildcard: a key only reaches the routes of the abilities it was given.
 */
enum ApiKeyAbility: string
{
    use BaseEnum;

    case EVENTS_READ = 'events:read';
    case PRODUCTS_READ = 'products:read';
    case ORDERS_READ = 'orders:read';
    case ATTENDEES_READ = 'attendees:read';
    case CHECK_IN_LISTS_READ = 'check-in-lists:read';
    case REPORTS_READ = 'reports:read';
    case WEBHOOKS_READ = 'webhooks:read';
    case CHECK_INS_WRITE = 'check-ins:write';

    /**
     * Read abilities only ever open GET routes.
     */
    public function allowsWrites(): bool
    {
        return $this === self::CHECK_INS_WRITE;
    }
}
