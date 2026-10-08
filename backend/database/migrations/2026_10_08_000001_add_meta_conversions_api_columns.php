<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizer_settings', function (Blueprint $table) {
            $table->text('meta_conversions_api_access_token')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->jsonb('ad_tracking_context')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organizer_settings', function (Blueprint $table) {
            $table->dropColumn('meta_conversions_api_access_token');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('ad_tracking_context');
        });
    }
};
