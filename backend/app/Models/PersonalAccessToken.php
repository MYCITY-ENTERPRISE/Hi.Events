<?php

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Account API keys, as listed and managed by the application. Authentication itself goes through
 * Laravel Sanctum's own model for this table (Laravel\Sanctum\PersonalAccessToken).
 */
class PersonalAccessToken extends BaseModel
{
    protected function getCastMap(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tokenable_id');
    }
}
