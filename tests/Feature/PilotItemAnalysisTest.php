<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\PilotItemAnalysis;
use App\Support\PuzzleCatalog;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PilotItemAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuestionSeeder::class);
    }

    public function test_without_completed_attempts_no_fake_iq_or_norms_are_reported(): void
    {
        $report = PilotItemAnalysis::forRevision(PuzzleCatalog::VERSION);

        $this->assertSame(25, $report['total_questions']);
        $this->assertSame(0, $report['completed_attempts']);
        $this->assertSame(0, $report['valid_complete_attempts']);
        $this->assertNull($report['iq_score']);
        $this->assertFalse($report['population_norms']);
        $this->assertCount(25, $report['items']);
        $this->assertNull($report['items'][0]['observed_correct_rate']);
        $this->assertNull($report['items'][0]['corrected_item_total_correlation']);
    }

    public function test_raw_correct_rate_and_option_counts_are_derived_from_real_saved_answers(): void
    {
        $attemptId = DB::table('quiz_attempts')->insertGetId([
            'uuid' => 'a7a18c62-ae66-43ac-8d45-010101010101',
            'status' => 'completed',
            'current_position' => 25,
            'correct_count' => 25,
            'elapsed_seconds' => 320,
            'started_at' => now()->subMinutes(6),
            'completed_at' => now(),
            'question_revision' => PuzzleCatalog::VERSION,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)->get() as $question) {
            $option = DB::table('answer_options')
                ->where('question_id', $question->id)->where('is_correct', true)->first();
            DB::table('attempt_answers')->insert([
                'attempt_id' => $attemptId,
                'question_id' => $question->id,
                'answer_option_id' => $option->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $report = PilotItemAnalysis::forRevision(PuzzleCatalog::VERSION);

        $this->assertSame(1, $report['completed_attempts']);
        $this->assertSame(1, $report['valid_complete_attempts']);
        $this->assertSame(0, $report['invalid_or_inconsistent_attempts']);
        $this->assertNull($report['iq_score']);
        foreach ($report['items'] as $item) {
            $this->assertSame(1, $item['sample_size']);
            $this->assertSame(1, $item['correct_count']);
            $this->assertSame(1.0, $item['observed_correct_rate']);
            $this->assertSame(1, array_sum($item['option_counts']));
            $this->assertNull($item['corrected_item_total_correlation']);
        }

        DB::table('quiz_attempts')->where('id', $attemptId)->update(['correct_count' => 0]);
        $invalid = PilotItemAnalysis::forRevision(PuzzleCatalog::VERSION);
        $this->assertSame(0, $invalid['valid_complete_attempts']);
        $this->assertSame(1, $invalid['invalid_or_inconsistent_attempts']);
    }
}
