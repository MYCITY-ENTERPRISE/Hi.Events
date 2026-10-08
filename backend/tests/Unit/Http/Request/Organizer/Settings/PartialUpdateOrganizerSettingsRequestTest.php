<?php

namespace Tests\Unit\Http\Request\Organizer\Settings;

use HiEvents\Http\Request\Organizer\Settings\PartialUpdateOrganizerSettingsRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PartialUpdateOrganizerSettingsRequestTest extends TestCase
{
    public function test_consent_must_be_acknowledged_when_pixels_are_enabled(): void
    {
        $request = new PartialUpdateOrganizerSettingsRequest;
        $request->merge([
            'tracking_pixels' => [
                ['provider' => 'facebook_pixel', 'pixel_id' => '1234567890', 'enabled' => true],
            ],
            'tracking_consent_acknowledged' => false,
        ]);

        $validator = Validator::make(
            $request->all(),
            PartialUpdateOrganizerSettingsRequest::rules()
        );

        foreach ($request->after() as $callback) {
            $callback($validator);
        }

        $this->assertTrue($validator->errors()->has('tracking_consent_acknowledged'));
    }

    public function test_consent_not_required_when_pixels_are_disabled(): void
    {
        $request = new PartialUpdateOrganizerSettingsRequest;
        $request->merge([
            'tracking_pixels' => [
                ['provider' => 'facebook_pixel', 'pixel_id' => '1234567890', 'enabled' => false],
            ],
            'tracking_consent_acknowledged' => false,
        ]);

        $validator = Validator::make(
            $request->all(),
            PartialUpdateOrganizerSettingsRequest::rules()
        );

        foreach ($request->after() as $callback) {
            $callback($validator);
        }

        $this->assertFalse($validator->errors()->has('tracking_consent_acknowledged'));
    }

    public function test_consent_not_required_when_no_pixels(): void
    {
        $request = new PartialUpdateOrganizerSettingsRequest;
        $request->merge([
            'tracking_pixels' => [],
            'tracking_consent_acknowledged' => false,
        ]);

        $validator = Validator::make(
            $request->all(),
            PartialUpdateOrganizerSettingsRequest::rules()
        );

        foreach ($request->after() as $callback) {
            $callback($validator);
        }

        $this->assertFalse($validator->errors()->has('tracking_consent_acknowledged'));
    }

    public function test_invalid_pixel_id_is_rejected(): void
    {
        $request = new PartialUpdateOrganizerSettingsRequest;
        $request->merge([
            'tracking_pixels' => [
                ['provider' => 'facebook_pixel', 'pixel_id' => 'not-a-valid-id', 'enabled' => true],
            ],
            'tracking_consent_acknowledged' => true,
        ]);

        $validator = Validator::make(
            $request->all(),
            PartialUpdateOrganizerSettingsRequest::rules()
        );

        foreach ($request->after() as $callback) {
            $callback($validator);
        }

        $this->assertTrue($validator->errors()->has('tracking_pixels.0.pixel_id'));
    }

    public function test_gtm_blocked_in_saas_mode(): void
    {
        config(['app.saas_mode_enabled' => true]);

        $request = new PartialUpdateOrganizerSettingsRequest;
        $request->merge([
            'tracking_pixels' => [
                ['provider' => 'google_tag_manager', 'pixel_id' => 'GTM-ABCDEF', 'enabled' => true],
            ],
            'tracking_consent_acknowledged' => true,
        ]);

        $validator = Validator::make(
            $request->all(),
            PartialUpdateOrganizerSettingsRequest::rules()
        );

        foreach ($request->after() as $callback) {
            $callback($validator);
        }

        $this->assertTrue($validator->errors()->has('tracking_pixels.0.provider'));
    }

    public function test_gtm_allowed_in_self_hosted_mode(): void
    {
        config(['app.saas_mode_enabled' => false]);

        $request = new PartialUpdateOrganizerSettingsRequest;
        $request->merge([
            'tracking_pixels' => [
                ['provider' => 'google_tag_manager', 'pixel_id' => 'GTM-ABCDEF', 'enabled' => true],
            ],
            'tracking_consent_acknowledged' => true,
        ]);

        $validator = Validator::make(
            $request->all(),
            PartialUpdateOrganizerSettingsRequest::rules()
        );

        foreach ($request->after() as $callback) {
            $callback($validator);
        }

        $this->assertFalse($validator->errors()->has('tracking_pixels.0.provider'));
    }

    public function test_meta_conversions_api_access_token_accepts_a_meta_token(): void
    {
        $validator = Validator::make(
            ['meta_conversions_api_access_token' => 'EAAGm0PX4ZCpsBAKZCZBabc123'],
            PartialUpdateOrganizerSettingsRequest::rules()
        );

        $this->assertFalse($validator->errors()->has('meta_conversions_api_access_token'));
    }

    public function test_meta_conversions_api_access_token_rejects_unexpected_characters(): void
    {
        $validator = Validator::make(
            ['meta_conversions_api_access_token' => 'EAA token<script>'],
            PartialUpdateOrganizerSettingsRequest::rules()
        );

        $this->assertTrue($validator->errors()->has('meta_conversions_api_access_token'));
    }
}
