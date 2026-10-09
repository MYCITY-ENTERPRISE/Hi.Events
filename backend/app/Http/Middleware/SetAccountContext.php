<?php

namespace HiEvents\Http\Middleware;

use Closure;
use HiEvents\Models\User;
use HiEvents\Services\Domain\Auth\AuthUserService;
use Illuminate\Support\Facades\Auth;

class SetAccountContext
{
    public function __construct(
        private readonly AuthUserService $authUserService,
    ) {}

    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            // An API key carries its own account; a session (JWT) carries it in its payload.
            $accountId = $this->authUserService->isApiKeyRequest()
                ? $this->authUserService->getAuthenticatedAccountId()
                : Auth::payload()->get('account_id');

            if ($accountId) {
                User::setCurrentAccountId($accountId);
            }
        }

        return $next($request);
    }
}
