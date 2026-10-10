<?php

use HiEvents\DomainObjects\Enums\ApiKeyAbility;

/*
 * Account API keys (Authorization: Bearer <key>) call the same routes as the web app, but only the routes
 * listed below. A route is identified by "METHOD uri", the uri as declared in routes/api.php, without the
 * leading slash. Any route missing from these lists is refused to API keys, and ApiKeyRouteCoverageTest
 * fails until every authenticated route is listed in exactly one of them.
 */
return [
    // Version of this contract, returned by GET /api-key. Increase it when the routes or abilities change.
    'contract' => 1,

    // Optional capabilities of this instance, returned by GET /api-key.
    'features' => [
        'authenticated_check_in',
    ],

    // Routes any valid API key can call.
    'open_routes' => [
        'GET api-key',
    ],

    // Route => ability the key needs. Read abilities only open GET routes.
    'routes' => [
        'GET organizers' => ApiKeyAbility::EVENTS_READ->value,
        'GET organizers/{organizer_id}' => ApiKeyAbility::EVENTS_READ->value,
        'GET organizers/{organizer_id}/events' => ApiKeyAbility::EVENTS_READ->value,
        'GET events' => ApiKeyAbility::EVENTS_READ->value,
        'GET events/{event_id}' => ApiKeyAbility::EVENTS_READ->value,
        'GET events/{event_id}/settings' => ApiKeyAbility::EVENTS_READ->value,
        'GET events/{event_id}/occurrences' => ApiKeyAbility::EVENTS_READ->value,
        'GET events/{event_id}/occurrences/{occurrence_id}' => ApiKeyAbility::EVENTS_READ->value,

        'GET events/{event_id}/product-categories' => ApiKeyAbility::PRODUCTS_READ->value,
        'GET events/{event_id}/product-categories/{category_id}' => ApiKeyAbility::PRODUCTS_READ->value,
        'GET events/{event_id}/products' => ApiKeyAbility::PRODUCTS_READ->value,
        'GET events/{event_id}/products/{ticket_id}' => ApiKeyAbility::PRODUCTS_READ->value,
        'GET events/{event_id}/capacity-assignments' => ApiKeyAbility::PRODUCTS_READ->value,
        'GET events/{event_id}/capacity-assignments/{capacity_assignment_id}' => ApiKeyAbility::PRODUCTS_READ->value,
        'GET accounts/{account_id}/taxes-and-fees' => ApiKeyAbility::PRODUCTS_READ->value,

        'GET events/{event_id}/orders' => ApiKeyAbility::ORDERS_READ->value,
        'GET events/{event_id}/orders/{order_id}' => ApiKeyAbility::ORDERS_READ->value,
        'GET organizers/{organizer_id}/orders' => ApiKeyAbility::ORDERS_READ->value,

        'GET events/{event_id}/attendees' => ApiKeyAbility::ATTENDEES_READ->value,
        'GET events/{event_id}/attendees/{attendee_id}' => ApiKeyAbility::ATTENDEES_READ->value,

        'GET events/{event_id}/check-in-lists' => ApiKeyAbility::CHECK_IN_LISTS_READ->value,
        'GET events/{event_id}/check-in-lists/{check_in_list_id}' => ApiKeyAbility::CHECK_IN_LISTS_READ->value,

        'POST events/{event_id}/check-in-lists/{check_in_list_id}/check-ins' => ApiKeyAbility::CHECK_INS_WRITE->value,
        'DELETE events/{event_id}/check-in-lists/{check_in_list_id}/check-ins/{check_in_short_id}' => ApiKeyAbility::CHECK_INS_WRITE->value,

        'GET events/{event_id}/stats' => ApiKeyAbility::REPORTS_READ->value,
        'GET events/{event_id}/counts' => ApiKeyAbility::REPORTS_READ->value,
        'GET events/{event_id}/reports/{report_type}' => ApiKeyAbility::REPORTS_READ->value,
        'GET organizers/{organizer_id}/stats' => ApiKeyAbility::REPORTS_READ->value,
        'GET organizers/{organizer_id}/reports/{report_type}' => ApiKeyAbility::REPORTS_READ->value,

        'GET organizers/{organizer_id}/webhooks' => ApiKeyAbility::WEBHOOKS_READ->value,
        'GET organizers/{organizer_id}/webhooks/{webhook_id}' => ApiKeyAbility::WEBHOOKS_READ->value,
        'GET organizers/{organizer_id}/webhooks/{webhook_id}/logs' => ApiKeyAbility::WEBHOOKS_READ->value,
        'GET events/{event_id}/webhooks' => ApiKeyAbility::WEBHOOKS_READ->value,
        'GET events/{event_id}/webhooks/{webhook_id}' => ApiKeyAbility::WEBHOOKS_READ->value,
        'GET events/{event_id}/webhooks/{webhook_id}/logs' => ApiKeyAbility::WEBHOOKS_READ->value,
    ],

    // Routes an API key can never call, whatever its abilities.
    'forbidden_routes' => [
        // Auth
        'GET auth/logout',
        'POST auth/refresh',

        // Users
        'GET users/me',
        'PUT users/me',
        'POST users',
        'GET users',
        'GET users/{user_id}',
        'PUT users/{user_id}',
        'POST users/{user_id}/email-change/{changeToken}',
        'POST users/{user_id}/invitation',
        'DELETE users/{user_id}/invitation',
        'DELETE users/{user_id}/email-change',
        'POST users/{user_id}/confirm-email/{resetToken}',
        'POST users/{user_id}/resend-email-confirmation',
        'POST users/{user_id}/confirm-email-with-code',

        // Announcements
        'GET announcements/active',
        'POST announcements/{announcement_id}/dismiss',

        // Accounts
        'POST accounts/deletion-request',
        'DELETE accounts/deletion-request',
        'GET accounts/deletion-request',
        'GET accounts/{account_id?}',
        'PUT accounts/{account_id?}',

        // API keys: a key never manages keys
        'GET accounts/{account_id}/api-keys',
        'POST accounts/{account_id}/api-keys',
        'DELETE accounts/{account_id}/api-keys/{api_key_id}',

        // Organizers
        'POST organizers',
        'POST organizers/{organizer_id}',
        'PUT organizers/{organizer_id}/status',
        'DELETE organizers/{organizer_id}',
        'GET organizers/{organizer_id}/deletion-status',
        'GET organizers/{organizer_id}/settings',
        'PATCH organizers/{organizer_id}/settings',
        'PATCH organizers/{organizer_id}/location',
        'GET organizers/{organizer_id}/reports/{report_type}/export',
        'POST organizers/{organizer_id}/webhooks',
        'PUT organizers/{organizer_id}/webhooks/{webhook_id}',
        'DELETE organizers/{organizer_id}/webhooks/{webhook_id}',

        // Locations - Organizer level
        'GET organizers/{organizer_id}/locations',
        'POST organizers/{organizer_id}/locations',
        'GET geo/status',
        'GET organizers/{organizer_id}/locations/autocomplete',
        'GET organizers/{organizer_id}/locations/places/{place_id}',
        'PUT organizers/{organizer_id}/locations/{location_id}',
        'DELETE organizers/{organizer_id}/locations/{location_id}',

        // Stripe Connect - Organizer level
        'GET organizers/{organizerId}/stripe/connect_accounts',
        'POST organizers/{organizerId}/stripe/connect',
        'POST organizers/{organizerId}/stripe/copy_from/{sourceOrganizerId}',
        'DELETE organizers/{organizerId}/stripe/connect_accounts/{stripeAccountId}',

        // VAT Settings - Organizer level
        'GET organizers/{organizerId}/vat-settings',
        'POST organizers/{organizerId}/vat-settings',

        // Email Templates - Organizer level
        'GET organizers/{organizerId}/email-templates',
        'GET email-templates/defaults',
        'POST organizers/{organizerId}/email-templates',
        'PUT organizers/{organizerId}/email-templates/{templateId}',
        'DELETE organizers/{organizerId}/email-templates/{templateId}',
        'POST organizers/{organizerId}/email-templates/preview',
        'GET email-templates/tokens/{templateType}',

        // Taxes and Fees
        'POST accounts/{account_id}/taxes-and-fees',
        'PUT accounts/{account_id}/taxes-and-fees/{tax_or_fee_id}',
        'DELETE accounts/{account_id}/taxes-and-fees/{tax_or_fee_id}',

        // Events
        'POST events',
        'PUT events/{event_id}',
        'PATCH events/{event_id}/event-location',
        'PUT events/{event_id}/status',
        'DELETE events/{event_id}',
        'GET events/{event_id}/deletion-status',
        'POST events/{event_id}/duplicate',

        // Product Categories
        'POST events/{event_id}/product-categories',
        'PUT events/{event_id}/product-categories/{category_id}',
        'DELETE events/{event_id}/product-categories/{category_id}',

        // Products
        'POST events/{event_id}/products',
        'POST events/{event_id}/products/sort',
        'PUT events/{event_id}/products/{ticket_id}',
        'DELETE events/{event_id}/products/{ticket_id}',

        // Email Templates - Event level
        'GET events/{eventId}/email-templates',
        'POST events/{eventId}/email-templates',
        'PUT events/{eventId}/email-templates/{templateId}',
        'DELETE events/{eventId}/email-templates/{templateId}',
        'POST events/{eventId}/email-templates/preview',

        // Attendees
        'POST events/{event_id}/attendees',
        'PUT events/{event_id}/attendees/{attendee_id}',
        'PATCH events/{event_id}/attendees/{attendee_id}',
        'POST events/{event_id}/attendees/export',
        'POST events/{event_id}/attendees/{attendee_public_id}/resend-ticket',
        'POST events/{event_id}/attendees/{attendee_public_id}/check_in',

        // Orders
        'PUT events/{event_id}/orders/{order_id}',
        'POST events/{event_id}/orders/{order_id}/message',
        'POST events/{event_id}/orders/{order_id}/refund',
        'POST events/{event_id}/orders/{order_id}/resend_confirmation',
        'POST events/{event_id}/orders/{order_id}/cancel',
        'POST events/{event_id}/orders/{order_id}/mark-as-paid',
        'POST events/{event_id}/orders/export',
        'GET events/{event_id}/orders/{order_id}/invoice',

        // Questions
        'POST events/{event_id}/questions',
        'PUT events/{event_id}/questions/{question_id}',
        'GET events/{event_id}/questions/{question_id}',
        'DELETE events/{event_id}/questions/{question_id}',
        'GET events/{event_id}/questions',
        'POST events/{event_id}/questions/sort',
        'PUT events/{event_id}/questions/{question_id}/answers/{answer_id}',
        'GET events/{event_id}/questions/answers/export',
        'POST events/{event_id}/questions/answers/export',

        // Images
        'POST events/{event_id}/images',
        'GET events/{event_id}/images',
        'DELETE events/{event_id}/images/{image_id}',

        // Promo Codes
        'POST events/{event_id}/promo-codes',
        'PUT events/{event_id}/promo-codes/{promo_code_id}',
        'GET events/{event_id}/promo-codes',
        'GET events/{event_id}/promo-codes/{promo_code_id}',
        'DELETE events/{event_id}/promo-codes/{promo_code_id}',

        // Affiliates
        'POST events/{event_id}/affiliates',
        'PUT events/{event_id}/affiliates/{affiliate_id}',
        'GET events/{event_id}/affiliates',
        'GET events/{event_id}/affiliates/{affiliate_id}',
        'DELETE events/{event_id}/affiliates/{affiliate_id}',
        'POST events/{event_id}/affiliates/export',

        // Messages
        'POST events/{event_id}/messages',
        'GET events/{event_id}/messages',
        'POST events/{event_id}/messages/{message_id}/cancel',
        'GET events/{event_id}/messages/{message_id}/recipients',

        // Event Settings
        'PUT events/{event_id}/settings',
        'PATCH events/{event_id}/settings',
        'GET events/{event_id}/settings/platform-fee-preview',

        // Capacity Assignments
        'POST events/{event_id}/capacity-assignments',
        'PUT events/{event_id}/capacity-assignments/{capacity_assignment_id}',
        'DELETE events/{event_id}/capacity-assignments/{capacity_assignment_id}',

        // Check-In Lists
        'POST events/{event_id}/check-in-lists',
        'PUT events/{event_id}/check-in-lists/{check_in_list_id}',
        'DELETE events/{event_id}/check-in-lists/{check_in_list_id}',

        // Webhooks
        'POST events/{event_id}/webhooks',
        'PUT events/{event_id}/webhooks/{webhook_id}',
        'DELETE events/{event_id}/webhooks/{webhook_id}',

        // Waitlist
        'GET events/{event_id}/waitlist',
        'GET events/{event_id}/waitlist/stats',
        'POST events/{event_id}/waitlist/offer-next',
        'DELETE events/{event_id}/waitlist/{entry_id}',

        // Event Occurrences
        'POST events/{event_id}/occurrences/generate',
        'GET events/{event_id}/occurrences/generate/status',
        'POST events/{event_id}/occurrences/bulk-update',
        'POST events/{event_id}/occurrences',
        'PUT events/{event_id}/occurrences/{occurrence_id}',
        'DELETE events/{event_id}/occurrences/{occurrence_id}',
        'POST events/{event_id}/occurrences/{occurrence_id}/cancel',
        'POST events/{event_id}/occurrences/{occurrence_id}/reactivate',
        'PUT events/{event_id}/occurrences/{occurrence_id}/price-overrides',
        'GET events/{event_id}/occurrences/{occurrence_id}/price-overrides',
        'DELETE events/{event_id}/occurrences/{occurrence_id}/price-overrides/{override_id}',
        'GET events/{event_id}/occurrences/{occurrence_id}/product-visibility',
        'GET events/{event_id}/occurrences/{occurrence_id}/product-availability',
        'PUT events/{event_id}/occurrences/{occurrence_id}/product-visibility',

        // Images
        'POST images',
        'DELETE images/{image_id}',
    ],
];
