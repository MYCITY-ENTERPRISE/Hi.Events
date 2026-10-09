<?php

namespace HiEvents\Resources\ApiKey;

use HiEvents\DomainObjects\PersonalAccessTokenDomainObject;
use Illuminate\Http\Request;

/**
 * Only returned when the key is created: the token is never shown again.
 *
 * @mixin PersonalAccessTokenDomainObject
 */
class ApiKeyResourceWithToken extends ApiKeyResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'token' => $this->getPlainTextToken(),
        ]);
    }
}
