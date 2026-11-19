<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_subscription_scopes', function (Blueprint $table): void {
            $table->string('scope_key', 96)->primary();
            $table->timestamps();
        });

        Schema::table('demo_subscriptions', function (Blueprint $table): void {
            // Null for historical records that cannot reliably be tied to a guest session.
            $table->string('active_scope_key', 96)->nullable()->unique();
            $table->timestamp('superseded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('demo_subscriptions', function (Blueprint $table): void {
            $table->dropUnique(['active_scope_key']);
            $table->dropColumn(['active_scope_key', 'superseded_at']);
        });

        Schema::dropIfExists('demo_subscription_scopes');
    }
};
