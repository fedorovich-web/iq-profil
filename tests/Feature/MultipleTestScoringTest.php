<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\AttemptScoring;
use App\Support\PilotItemAnalysis;
use App\Support\PuzzleCatalog;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class MultipleTestScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuestionSeeder::class);
    }

    private function createAttemptWithAnswers(string $code, int $revision, int $total, int $correct): object
    {
        $now = now();
        $attemptId = DB::table('quiz_attempts')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'test_code' => $code,
            'question_revision' => $revision,
            'question_count' => $total,
            'status' => 'in_progress',
            'current_position' => $total,
            'started_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        for ($position = 1; $position <= $total; $position++) {
            $questionId = DB::table('questions')->insertGetId([
                'test_code' => $code,
                'bank_version' => $revision,
                'position' => $position,
                'kind' => 'synthetic-item',
                'prompt' => 'Synthetic question',
                'explanation' => 'Test fixture',
                'cells' => json_encode(array_fill(0, 9, 0), JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $correctOption = DB::table('answer_options')->insertGetId([
                'question_id' => $questionId,
                'code' => 'A',
                'position' => 1,
                'graphic_value' => 1,
                'is_correct' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $wrongOption = DB::table('answer_options')->insertGetId([
                'question_id' => $questionId,
                'code' => 'B',
                'position' => 2,
                'graphic_value' => 2,
                'is_correct' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('attempt_answers')->insert([
                'attempt_id' => $attemptId,
                'question_id' => $questionId,
                'answer_option_id' => $position <= $correct ? $correctOption : $wrongOption,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return DB::table('quiz_attempts')->where('id', $attemptId)->first();
    }

    public function test_scoring_isolated_by_test_identity_and_revision_with_37_questions(): void
    {
        $attempt = $this->createAttemptWithAnswers('attention-series', 5, 37, 19);
        $this->assertSame(19, AttemptScoring::grade($attempt));
        $this->assertSame(25, DB::table('questions')
            ->where('test_code', PuzzleCatalog::TEST_CODE)->where('bank_version', PuzzleCatalog::VERSION)->count());
        $this->assertSame(37, DB::table('questions')
            ->where('test_code', 'attention-series')->where('bank_version', 5)->count());

        // Reseeding the existing matrix bank must ignore the unrelated bank.
        $this->seed(QuestionSeeder::class);
        $this->assertSame(19, AttemptScoring::grade($attempt));

        DB::table('quiz_attempts')->where('id', $attempt->id)->update([
            'status' => 'completed', 'correct_count' => 19, 'completed_at' => now(),
        ]);
        $pilot = PilotItemAnalysis::forRevision(5, 'attention-series');
        $this->assertSame(37, $pilot['total_questions']);
        $this->assertSame(1, $pilot['valid_complete_attempts']);
        $this->assertCount(37, $pilot['items']);
    }

    public function test_more_than_255_questions_and_new_revision_are_supported(): void
    {
        $attempt = $this->createAttemptWithAnswers('attention-series', 6, 300, 299);
        $this->assertSame(299, AttemptScoring::grade($attempt));
        $this->assertSame(300, (int) $attempt->question_count);
    }

    public function test_cross_test_question_is_not_accepted_as_an_answer(): void
    {
        $attempt = $this->createAttemptWithAnswers('attention-series', 5, 3, 3);
        $foreign = DB::table('questions')->where('test_code', PuzzleCatalog::TEST_CODE)->first();
        $foreignOption = DB::table('answer_options')->where('question_id', $foreign->id)->value('id');
        $target = DB::table('attempt_answers')->where('attempt_id', $attempt->id)->first();

        DB::table('attempt_answers')->where('id', $target->id)
            ->update(['question_id' => $foreign->id, 'answer_option_id' => $foreignOption]);

        $this->expectException(ValidationException::class);
        AttemptScoring::grade($attempt);
    }

    public function test_bank_size_mutation_is_rejected_for_fixed_attempt_snapshot(): void
    {
        $attempt = $this->createAttemptWithAnswers('attention-series', 5, 3, 2);
        $newQuestion = (array) DB::table('questions')->where('test_code', 'attention-series')
            ->where('bank_version', 5)->where('position', 3)->first();
        unset($newQuestion['id']);
        $newQuestion['position'] = 4;
        DB::table('questions')->insert($newQuestion);
        $this->expectException(HttpException::class);
        AttemptScoring::grade($attempt);
    }
}
