<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\PuzzleCatalog;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ScoreIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuestionSeeder::class);
    }

    private function begin(): string
    {
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->post('/quiz/intake/gender', ['gender' => 'unspecified']);
        $this->post('/quiz/intake/age', ['age_group' => '25_34']);
        $this->get('/quiz')->assertOk();

        return (string) session('active_attempt');
    }

    private function submit(int $position, bool $correct, string $navigation = 'next'): void
    {
        $question = DB::table('questions')
            ->where('bank_version', PuzzleCatalog::VERSION)
            ->where('position', $position)->first();

        $optionId = DB::table('answer_options')
            ->where('question_id', $question->id)->where('is_correct', $correct)
            ->orderBy('id')->value('id');

        $this->post('/quiz/answer', [
            'question_id' => $question->id,
            'answer_option_id' => $optionId,
            'navigation' => $navigation,
        ])->assertRedirect($navigation === 'finish' ? '/paywall/'.session('active_attempt') : '/quiz');
    }

    public function test_every_possible_score_from_zero_through_question_count_is_correct(): void
    {
        // This batch intentionally exceeds the production start rate limit.
        $this->withoutMiddleware(ThrottleRequests::class);

        foreach (range(0, PuzzleCatalog::COUNT) as $target) {
            $uuid = $this->begin();
            $total = PuzzleCatalog::COUNT;

            for ($position = 1; $position <= $total; $position++) {
                $this->submit($position, $position <= $target, $position === $total ? 'finish' : 'next');
            }

            $this->assertDatabaseHas('quiz_attempts', [
                'uuid' => $uuid, 'status' => 'completed',
                'question_count' => $total, 'correct_count' => $target,
            ]);

            $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
            $report = $this->get('/result/'.$uuid)->assertOk();
            $this->assertSame($total, $report->viewData('totalQuestions'));
            $this->assertSame((int) round($target * 100 / $total), $report->viewData('percentage'));
            $categories = $report->viewData('categoryBreakdown');
            $this->assertSame($target, array_sum(array_column($categories, 'correct')));
            $this->assertSame($total, array_sum(array_column($categories, 'total')));
            $this->assertCount($total, $report->viewData('answerReview'));

            // An opened report does not lock the user out of starting another attempt.
            $this->post('/attempts')->assertRedirect('/quiz');
        }
    }

    public function test_archived_revision_keeps_snapshot_denominator_instead_of_current_catalog(): void
    {
        $uuid = $this->begin();
        DB::table('quiz_attempts')->where('uuid', $uuid)->update([
            'status' => 'completed', 'question_revision' => 4,
            'bank_fingerprint' => null, 'option_shuffle_seed' => null,
            'question_count' => 17, 'correct_count' => 9,
            'elapsed_seconds' => 32, 'completed_at' => now(),
        ]);

        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $report = $this->get('/result/'.$uuid)->assertOk();
        $this->assertSame(17, $report->viewData('totalQuestions'));
        $this->assertSame(53, $report->viewData('percentage'));
        $this->assertSame([], $report->viewData('categoryBreakdown'));
    }

    public function test_unknown_historical_denominator_does_not_invent_a_percentage(): void
    {
        $uuid = $this->begin();
        DB::table('quiz_attempts')->where('uuid', $uuid)->update([
            'status' => 'completed', 'question_revision' => 2,
            'bank_fingerprint' => null, 'option_shuffle_seed' => null,
            'question_count' => null, 'correct_count' => 3,
            'elapsed_seconds' => 32, 'completed_at' => now(),
        ]);
        $this->post('/checkout/'.$uuid.'/confirm');
        $report = $this->get('/result/'.$uuid)->assertOk()
            ->assertSee('Процент недоступен');
        $this->assertNull($report->viewData('totalQuestions'));
        $this->assertNull($report->viewData('percentage'));
    }

    public function test_wrong_question_option_link_cannot_be_counted_or_finish_quiz(): void
    {
        $uuid = $this->begin();
        for ($position = 1; $position < PuzzleCatalog::COUNT; $position++) {
            $this->submit($position, true);
        }

        $first = DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)
            ->where('position', 1)->first();
        $second = DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)
            ->where('position', 2)->first();
        $foreignOption = DB::table('answer_options')->where('question_id', $second->id)->value('id');
        $attemptId = DB::table('quiz_attempts')->where('uuid', $uuid)->value('id');
        DB::table('attempt_answers')->where('attempt_id', $attemptId)
            ->where('question_id', $first->id)->update(['answer_option_id' => $foreignOption]);

        $last = DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)
            ->where('position', PuzzleCatalog::COUNT)->first();
        $lastOption = DB::table('answer_options')->where('question_id', $last->id)
            ->where('is_correct', true)->value('id');
        $this->post('/quiz/answer', [
            'question_id' => $last->id,
            'answer_option_id' => $lastOption,
            'navigation' => 'finish',
        ])->assertSessionHasErrors('answer_option_id');

        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'status' => 'in_progress', 'correct_count' => null]);
    }

    public function test_completed_attempt_rejects_late_answer_mutation_and_repeated_finish(): void
    {
        $uuid = $this->begin();
        for ($position = 1; $position <= PuzzleCatalog::COUNT; $position++) {
            $this->submit($position, true, $position === PuzzleCatalog::COUNT ? 'finish' : 'next');
        }

        $attemptId = DB::table('quiz_attempts')->where('uuid', $uuid)->value('id');
        $saved = DB::table('attempt_answers')->where('attempt_id', $attemptId)->orderBy('question_id')->pluck('answer_option_id')->all();
        $firstWrong = DB::table('answer_options')->where('question_id', DB::table('questions')
            ->where('bank_version', PuzzleCatalog::VERSION)->where('position', 1)->value('id'))
            ->where('is_correct', false)->value('id');
        $this->post('/quiz/answer', ['navigation' => 'finish', 'answer_option_id' => $firstWrong])
            ->assertRedirect('/paywall/'.$uuid);
        $this->assertSame($saved, DB::table('attempt_answers')->where('attempt_id', $attemptId)->orderBy('question_id')->pluck('answer_option_id')->all());
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attemptId, 'correct_count' => PuzzleCatalog::COUNT]);
    }

    public function test_future_server_start_timestamp_does_not_produce_negative_or_absurd_duration(): void
    {
        $uuid = $this->begin();
        for ($position = 1; $position < PuzzleCatalog::COUNT; $position++) {
            $this->submit($position, true);
        }
        DB::table('quiz_attempts')->where('uuid', $uuid)
            ->update(['quiz_started_at' => now()->addMinutes(5)]);
        $this->submit(PuzzleCatalog::COUNT, true, 'finish');
        $this->assertDatabaseHas('quiz_attempts', [
            'uuid' => $uuid, 'status' => 'completed',
            'elapsed_seconds' => 0, 'correct_count' => PuzzleCatalog::COUNT,
        ]);
    }

    public function test_stale_question_submission_is_rejected(): void
    {
        $this->begin();
        $second = DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)
            ->where('position', 2)->first();
        $first = DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)
            ->where('position', 1)->first();
        $option = DB::table('answer_options')->where('question_id', $first->id)->value('id');
        $this->post('/quiz/answer', [
            'question_id' => $second->id, 'answer_option_id' => $option, 'navigation' => 'next',
        ])->assertStatus(409);
        $this->assertDatabaseCount('attempt_answers', 0);
    }
}
