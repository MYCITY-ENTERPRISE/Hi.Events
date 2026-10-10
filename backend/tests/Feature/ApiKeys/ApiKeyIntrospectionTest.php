<?php

namespace Tests\Feature\ApiKeys;

use HiEvents\Http\ResponseCodes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ApiKeyIntrospectionTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiKeys;

    public function test_a_key_describes_itself_and_the_instance(): void
    {
        $this->seedAccountConfiguration();
        [$admin, $accountId] = $this->makeAccountWithAdmin();

        $expiresAt = now()->addMonths(6)->startOfSecond();
        $key = $this->makeApiKey($admin, $accountId, ['events:read', 'orders:read'], $expiresAt);

        $response = $this->getJson('/api-key', $this->bearer($key));

        $response->assertStatus(ResponseCodes::HTTP_OK);
        $response->assertJsonPath('data.id', (int) explode('|', $key)[0]);
        $response->assertJsonPath('data.name', 'Test key');
        $response->assertJsonPath('data.abilities', ['events:read', 'orders:read']);
        $response->assertJsonPath('data.account_id', $accountId);
        $response->assertJsonPath('data.app_version', trim(file_get_contents(base_path('VERSION'))));
        $response->assertJsonPath('data.api_key_contract', 1);
        $response->assertJsonPath('data.features', config('api-keys.features'));
        $this->assertTrue($expiresAt->equalTo($response->json('data.expires_at')));
        $this->assertNotNull($response->json('data.last_used_at'));
        $this->assertArrayNotHasKey('token', $response->json('data'));
    }

    public function test_a_session_is_not_an_api_key(): void
    {
        $this->seedAccountConfiguration();
        [$admin, $accountId] = $this->makeAccountWithAdmin();

        $this->getJson('/api-key', $this->bearer($this->jwtFor($admin, $accountId)))
            ->assertStatus(ResponseCodes::HTTP_FORBIDDEN);
    }

    public function test_the_route_requires_authentication(): void
    {
        $this->getJson('/api-key')->assertStatus(ResponseCodes::HTTP_UNAUTHORIZED);
    }
}
