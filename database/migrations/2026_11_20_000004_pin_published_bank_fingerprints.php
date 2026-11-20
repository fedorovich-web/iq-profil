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
            $table->char('bank_fingerprint', 64)->nullable();
        });

        // Do not fabricate a historical fingerprint from potentially changed content.
        // New attempts capture it at creation; older attempts retain null.
    }

    public function down(): void
    {
        if (DB::table('quiz_attempts')->whereNotNull('bank_fingerprint')->exists()) {
            throw new LogicException('Cannot discard published bank fingerprints from saved attempts.');
        }

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropColumn('bank_fingerprint');
        });
    }
};
