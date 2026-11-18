<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('position')->unique();
            $table->string('kind', 32);
            $table->string('prompt');
            $table->text('explanation');
            $table->json('cells');
            $table->string('source')->default('Original deterministic puzzle, IQ.Profil');
            $table->timestamps();
        });

        Schema::create('answer_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('code', 1);
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('graphic_value');
            $table->boolean('is_correct');
            $table->timestamps();
            $table->unique(['question_id', 'code']);
            $table->unique(['question_id', 'position']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status', 16)->default('in_progress')->index();
            $table->unsignedTinyInteger('current_position')->default(1);
            $table->unsignedTinyInteger('correct_count')->nullable();
            $table->unsignedInteger('elapsed_seconds')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attempt_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->foreignId('answer_option_id')->constrained('answer_options')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['attempt_id', 'question_id']);
        });

        Schema::create('demo_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attempt_id')->unique()->constrained('quiz_attempts')->cascadeOnDelete();
            $table->string('status', 16);
            $table->unsignedInteger('amount_kopecks')->default(2000);
            $table->char('currency', 3)->default('RUB');
            $table->unsignedInteger('future_monthly_kopecks')->default(49900);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_payments');
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('answer_options');
        Schema::dropIfExists('questions');
    }
};
