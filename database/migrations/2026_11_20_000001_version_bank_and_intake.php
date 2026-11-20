<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The old position index prevents two immutable banks from coexisting.
        // Existing rows are revision 3; do not change their IDs or answer keys.
        Schema::table('questions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('bank_version')->default(3);
        });
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropUnique('questions_position_unique');
            $table->unique(['bank_version', 'position'], 'questions_bank_version_position_unique');
        });
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->string('gender', 16)->nullable();
            $table->string('age_group', 16)->nullable();
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('quiz_started_at')->nullable();
        });
    }

    public function down(): void
    {
        // Rolling back must never remove a bank when multiple revisions exist.
        if (DB::table('questions')->distinct()->count('bank_version') > 1) {
            throw new LogicException('Cannot drop question bank versions with archived attempts.');
        }
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropColumn(['gender', 'age_group', 'profile_completed_at', 'quiz_started_at']);
        });
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropUnique('questions_bank_version_position_unique');
            $table->unique('position');
            $table->dropColumn('bank_version');
        });
    }
};
