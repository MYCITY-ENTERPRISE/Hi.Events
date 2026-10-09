<?php

namespace HiEvents\Services\Domain\ApiKey;

use HiEvents\DomainObjects\Generated\AccountUserDomainObjectAbstract;
use HiEvents\DomainObjects\Status\UserStatus;
use HiEvents\Repository\Interfaces\AccountUserRepositoryInterface;
use Laravel\Sanctum\PersonalAccessToken;

class ApiKeyAuthenticationService
{
    public function __construct(
        private readonly AccountUserRepositoryInterface $accountUserRepository,
    ) {}

    /**
     * Called by Sanctum for every request made with an API key, after its own checks (hash, expiry).
     * A key belongs to an account, and stops working as soon as the user who created it is no longer an
     * active member of that account: the request is then unauthenticated (401), like a revoked key.
     */
    public function isValid(PersonalAccessToken $accessToken, bool $isValid): bool
    {
        if (! $isValid || $accessToken->account_id === null) {
            return false;
        }

        return $this->accountUserRepository->findFirstWhere([
            AccountUserDomainObjectAbstract::ACCOUNT_ID => $accessToken->account_id,
            AccountUserDomainObjectAbstract::USER_ID => $accessToken->tokenable_id,
            AccountUserDomainObjectAbstract::STATUS => UserStatus::ACTIVE->name,
        ]) !== null;
    }
}
