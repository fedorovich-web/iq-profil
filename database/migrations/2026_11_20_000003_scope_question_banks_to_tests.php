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
        Schema::table('questions', function (Blueprint $table): void {
            $table->string('test_code', 64)->default('matrix-reasoning');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropUnique('questions_bank_version_position_unique');
            $table->unique(['test_code', 'bank_version', 'position'], 'questions_test_revision_position_unique');
        });

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->string('test_code', 64)->default('matrix-reasoning');
            $table->index(['test_code', 'question_revision'], 'quiz_attempts_test_revision_index');
        });
    }

    public function down(): void
    {
        if (DB::table('questions')->where('test_code', '!=', 'matrix-reasoning')->exists()
            || DB::table('quiz_attempts')->where('test_code', '!=', 'matrix-reasoning')->exists()) {
            throw new LogicException('Cannot remove test scopes when other test banks or attempts exist.');
        }

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropIndex('quiz_attempts_test_revision_index');
            $table->dropColumn('test_code');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropUnique('questions_test_revision_position_unique');
            $table->unique(['bank_version', 'position'], 'questions_bank_version_position_unique');
            $table->dropColumn('test_code');
        });
    }
};
