<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->unique()->constrained('quiz_attempts')->cascadeOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedInteger('monthly_amount_kopecks')->default(49900);
            $table->timestamp('activated_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_data_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 80);
            $table->string('document_version', 20);
            $table->timestamp('granted_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_data_consents');
        Schema::dropIfExists('demo_subscriptions');
    }
};
