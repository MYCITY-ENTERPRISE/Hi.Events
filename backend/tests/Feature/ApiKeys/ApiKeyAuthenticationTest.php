<?php

namespace Tests\Feature\ApiKeys;

use HiEvents\DomainObjects\Enums\AccountDeletionInitiator;
use HiEvents\DomainObjects\Enums\Role;
use HiEvents\DomainObjects\Status\AccountDeletionRequestStatus;
use HiEvents\DomainObjects\Status\UserStatus;
use HiEvents\Http\ResponseCodes;
use HiEvents\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiKeyAuthenticationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiKeys;

    private User $admin;

    private int $accountId;

    private int $eventId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccountConfiguration();

        [$this->admin, $this->accountId] = $this->makeAccountWithAdmin();
        $this->eventId = $this->makeEvent($this->accountId, $this->admin->id, $this->makeOrganizer($this->accountId));
    }

    public function test_a_read_key_reads_orders_but_cannot_refund_or_edit_them(): void
    {
        $key = $this->makeApiKey($this->admin, $this->accountId, ['orders:read']);

        $this->getJson("/events/{$this->eventId}/orders", $this->bearer($key))
            ->assertStatus(ResponseCodes::HTTP_OK);

        $this->postJson("/events/{$this->eventId}/orders/1/refund", ['amount' => 10], $this->bearer($key))
            ->assertStatus(ResponseCodes::HTTP_FORBIDDEN)
            ->assertJsonPath('error_code', 'API_KEY_SCOPE_DENIED');

        $this->putJson("/events/{$this->eventId}/orders/1", ['first_name' => 'X'], $this->bearer($key))
            ->assertStatus(ResponseCodes::HTTP_FORBIDDEN)
            ->assertJsonPath('error_code', 'API_KEY_SCOPE_DENIED');
    }

    public function test_a_key_without_the_ability_is_refused_with_the_missing_ability_named(): void
    {
        $key = $this->makeApiKey($this->admin, $this->accountId, ['events:read']);

        $this->getJson("/events/{$this->eventId}/orders", $this->bearer($key))
            ->assertStatus(ResponseCodes::HTTP_FORBIDDEN)
            ->assertJsonPath('error_code', 'API_KEY_SCOPE_DENIED')
            ->assertJsonPath('ability', 'orders:read');
    }

    public function test_a_key_cannot_read_an_event_of_another_account(): void
    {
        [$otherAdmin, $otherAccountId] = $this->makeAccountWithAdmin();
        $otherEventId = $this->makeEvent($otherAccountId, $otherAdmin->id, $this->makeOrganizer($otherAccountId));

        $key = $this->makeApiKey($this->admin, $this->accountId, ['orders:read']);

        $response = $this->getJson("/events/{$otherEventId}/orders", $this->bearer($key));

        $response->assertStatus(ResponseCodes::HTTP_FORBIDDEN);
        $this->assertNull($response->json('error_code'));
    }

    public function test_a_revoked_expired_or_malformed_key_is_unauthenticated(): void
    {
        $revoked = $this->makeApiKey($this->admin, $this->accountId, ['events:read']);
        DB::table('personal_access_tokens')->where('id', (int) explode('|', $revoked)[0])->delete();

        $expired = $this->makeApiKey($this->admin, $this->accountId, ['events:read'], now()->subMinute());

        $valid = $this->makeApiKey($this->admin, $this->accountId, ['events:read']);
        [$validId] = explode('|', $valid);

        foreach ([$revoked, $expired, 'not-a-key', $validId.'|hie_wrongsecret', '|'] as $token) {
            $this->getJson('/organizers', $this->bearer($token))
                ->assertStatus(ResponseCodes::HTTP_UNAUTHORIZED);
        }

        $this->getJson('/organizers', $this->bearer($valid))->assertStatus(ResponseCodes::HTTP_OK);
    }

    public function test_a_key_whose_creator_was_deactivated_is_unauthenticated(): void
    {
        $creator = $this->addUserToAccount($this->accountId, Role::ADMIN);
        $key = $this->makeApiKey($creator, $this->accountId, ['events:read']);

        $this->getJson('/api-key', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_OK);

        DB::table('account_users')
            ->where(['account_id' => $this->accountId, 'user_id' => $creator->id])
            ->update(['status' => UserStatus::INACTIVE->name]);

        $this->getJson('/api-key', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_UNAUTHORIZED);
        $this->getJson('/organizers', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_UNAUTHORIZED);
    }

    public function test_a_key_whose_creator_was_removed_from_the_account_is_unauthenticated(): void
    {
        $creator = $this->addUserToAccount($this->accountId, Role::ADMIN);
        $key = $this->makeApiKey($creator, $this->accountId, ['events:read']);

        DB::table('account_users')
            ->where(['account_id' => $this->accountId, 'user_id' => $creator->id])
            ->update(['deleted_at' => now()]);

        $this->getJson('/api-key', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_UNAUTHORIZED);
        $this->getJson('/organizers', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_UNAUTHORIZED);
    }

    public function test_a_key_never_reaches_logout_or_token_refresh(): void
    {
        $key = $this->makeApiKey($this->admin, $this->accountId, ['events:read', 'orders:read']);

        $this->getJson('/auth/logout', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_FORBIDDEN);
        $this->postJson('/auth/refresh', [], $this->bearer($key))->assertStatus(ResponseCodes::HTTP_FORBIDDEN);
    }

    public function test_the_admin_routes_only_accept_a_session(): void
    {
        $key = $this->makeApiKey($this->admin, $this->accountId, ['events:read', 'orders:read']);

        $this->getJson('/admin/system-info', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_UNAUTHORIZED);
    }

    public function test_settings_and_taxes_are_readable_with_their_abilities(): void
    {
        DB::table('event_settings')->insert([
            'event_id' => $this->eventId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $eventsKey = $this->makeApiKey($this->admin, $this->accountId, ['events:read']);
        $this->getJson("/events/{$this->eventId}/settings", $this->bearer($eventsKey))
            ->assertStatus(ResponseCodes::HTTP_OK);

        $productsKey = $this->makeApiKey($this->admin, $this->accountId, ['products:read']);
        $this->getJson("/accounts/{$this->accountId}/taxes-and-fees", $this->bearer($productsKey))
            ->assertStatus(ResponseCodes::HTTP_OK);
    }

    public function test_a_key_and_its_creator_have_separate_rate_limits(): void
    {
        config(['app.api_rate_limit_per_minute' => 2]);

        $key = $this->makeApiKey($this->admin, $this->accountId, ['events:read']);
        $jwt = $this->jwtFor($this->admin, $this->accountId);

        $this->getJson('/organizers', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_OK);
        $this->getJson('/organizers', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_OK);
        $this->getJson('/organizers', $this->bearer($key))->assertStatus(ResponseCodes::HTTP_TOO_MANY_REQUESTS);

        $this->getJson('/organizers', $this->bearer($jwt))->assertStatus(ResponseCodes::HTTP_OK);
        $this->getJson('/organizers', $this->bearer($jwt))->assertStatus(ResponseCodes::HTTP_OK);
        $this->getJson('/organizers', $this->bearer($jwt))->assertStatus(ResponseCodes::HTTP_TOO_MANY_REQUESTS);
    }

    public function test_a_key_of_an_account_pending_deletion_is_blocked(): void
    {
        DB::table('account_deletion_requests')->insert([
            'account_id' => $this->accountId,
            'requested_by_user_id' => $this->admin->id,
            'initiated_by' => AccountDeletionInitiator::cases()[0]->name,
            'status' => AccountDeletionRequestStatus::REQUESTED->name,
            'scheduled_deletion_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $key = $this->makeApiKey($this->admin, $this->accountId, ['events:read']);

        $this->getJson('/organizers', $this->bearer($key))
            ->assertStatus(ResponseCodes::HTTP_FORBIDDEN)
            ->assertJsonPath('error_code', 'ACCOUNT_PENDING_DELETION');
    }

    public function test_jwt_sessions_are_not_restricted_by_api_key_scopes(): void
    {
        $jwt = $this->jwtFor($this->admin, $this->accountId);

        $this->getJson("/events/{$this->eventId}/orders", $this->bearer($jwt))
            ->assertStatus(ResponseCodes::HTTP_OK);
        $this->getJson("/accounts/{$this->accountId}/api-keys", $this->bearer($jwt))
            ->assertStatus(ResponseCodes::HTTP_OK);
    }
}
