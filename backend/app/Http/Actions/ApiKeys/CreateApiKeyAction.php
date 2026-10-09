<?php

namespace HiEvents\Http\Actions\ApiKeys;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\Enums\Role;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\ApiKey\CreateApiKeyRequest;
use HiEvents\Http\ResponseCodes;
use HiEvents\Resources\ApiKey\ApiKeyResourceWithToken;
use HiEvents\Services\Application\Handlers\ApiKey\CreateApiKeyHandler;
use HiEvents\Services\Application\Handlers\ApiKey\DTO\CreateApiKeyDTO;
use Illuminate\Http\JsonResponse;

class CreateApiKeyAction extends BaseAction
{
    public function __construct(
        private readonly CreateApiKeyHandler $createApiKeyHandler,
    ) {}

    public function __invoke(CreateApiKeyRequest $request, int $accountId): JsonResponse
    {
        $this->isActionAuthorized($accountId, AccountDomainObject::class, Role::ADMIN);

        $apiKey = $this->createApiKeyHandler->handle(new CreateApiKeyDTO(
            name: $request->validated('name'),
            abilities: $request->validated('abilities'),
            accountId: $this->getAuthenticatedAccountId(),
            userId: $this->getAuthenticatedUser()->getId(),
            expiresAt: $request->validated('expires_at'),
        ));

        return $this->resourceResponse(
            resource: ApiKeyResourceWithToken::class,
            data: $apiKey,
            statusCode: ResponseCodes::HTTP_CREATED,
        );
    }
}
