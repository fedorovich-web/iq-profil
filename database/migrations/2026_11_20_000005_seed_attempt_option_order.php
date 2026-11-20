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
            $table->char('option_shuffle_seed', 32)->nullable();
        });

        // Null is intentional for archived attempts: their answer letters remain fixed.
    }

    public function down(): void
    {
        if (DB::table('quiz_attempts')->whereNotNull('option_shuffle_seed')->exists()) {
            throw new LogicException('Cannot remove per-attempt answer order for saved tests.');
        }

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropColumn('option_shuffle_seed');
        });
    }
};
