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
            $table->unsignedInteger('question_count')->nullable();
        });

        // Historical attempts can be backfilled only from a known archived bank.
        // Never infer their denominator from the currently published catalog.
        $banks = DB::table('questions')
            ->select('bank_version')
            ->selectRaw('COUNT(*) AS question_count')
            ->groupBy('bank_version')
            ->get();

        foreach ($banks as $bank) {
            DB::table('quiz_attempts')
                ->where('question_revision', $bank->bank_version)
                ->whereNull('question_count')
                ->update(['question_count' => (int) $bank->question_count]);
        }
    }

    public function down(): void
    {
        if (DB::table('quiz_attempts')->whereNotNull('question_count')->exists()) {
            throw new LogicException('Cannot discard immutable question counts for saved attempts.');
        }

        Schema::table('quiz_attempts', function (Blueprint $table): void {
            $table->dropColumn('question_count');
        });
    }
};
