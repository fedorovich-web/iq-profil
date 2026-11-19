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
        Schema::table('quiz_attempts', function (Blueprint $table): void {
            // The catalog can grow beyond 255 questions without overflowing
            // a MySQL/MariaDB unsigned TINYINT (SQLite ignores that range).
            $table->unsignedInteger('current_position')->default(1)->change();
            $table->unsignedInteger('correct_count')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('quiz_attempts')
            ->where('current_position', '>', 255)
            ->orWhere('correct_count', '>', 255)->exists()) {
            throw new LogicException('Cannot narrow quiz counters: some saved attempts contain values over 255.');
        }

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->unsignedTinyInteger('current_position')->default(1)->change();
            $table->unsignedTinyInteger('correct_count')->nullable()->change();
        });
    }
};
