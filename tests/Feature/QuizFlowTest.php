<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\PuzzleCatalog;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class QuizFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuestionSeeder::class);
    }

    private function startAttempt(): string
    {
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->post('/quiz/intake/gender', ['gender' => 'unspecified'])
            ->assertRedirect('/quiz/intake/age');
        $this->post('/quiz/intake/age', ['age_group' => '25_34'])
            ->assertRedirect('/quiz');
        $this->get('/quiz')->assertOk();

        return (string) session()->get('active_attempt');
    }

    public function test_question_bank_contains_25_deterministic_graphical_questions(): void
    {
        $this->assertSame(25, DB::table('questions')->count());
        $this->assertSame(150, DB::table('answer_options')->count());

        foreach (DB::table('questions')->get() as $question) {
            $options = DB::table('answer_options')->where('question_id', $question->id)->get();
            $cells = json_decode($question->cells, true, 512, JSON_THROW_ON_ERROR);
            $this->assertCount(9, $cells);
            $this->assertCount(6, $options);
            $this->assertSame(1, $options->where('is_correct', 1)->count());
            $this->assertSame((int) $cells[8], (int) $options->firstWhere('is_correct', 1)->graphic_value);
        }
    }

    public function test_start_saves_session_and_shows_first_question(): void
    {
        $uuid = $this->startAttempt();
        $this->assertNotSame('', $uuid);
        $this->get('/quiz')->assertOk()
            ->assertSee('Вопрос')
            ->assertSee('Какой фигуры', false)
            ->assertSee('Матрица три на три', false)
            ->assertSee('Строка 1, столбец 1: Количество синих точек: 1', false)
            ->assertSee('Строка 3, столбец 3: пропущенная фигура', false);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'current_position' => 1, 'status' => 'in_progress']);
    }

    public function test_progress_uses_saved_answers_and_milestones_are_shown_once(): void
    {
        $this->startAttempt();
        $total = DB::table('questions')->count();
        $firstMilestone = (int) ceil($total / 3);
        $secondMilestone = (int) ceil($total * 2 / 3);

        $this->get('/quiz')->assertOk()
            ->assertSee('aria-valuenow="0"', false)
            ->assertDontSee('data-quiz-milestone', false);

        for ($position = 1; $position <= $firstMilestone; $position++) {
            $questionId = DB::table('questions')->where('position', $position)->value('id');
            $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');

            $this->post('/quiz/answer', [
                'navigation' => 'next',
                'answer_option_id' => $optionId,
            ])->assertRedirect('/quiz');
        }

        $firstPercent = (int) round($firstMilestone * 100 / $total);
        $this->get('/quiz')->assertOk()
            ->assertSee('aria-valuenow="'.$firstMilestone.'"', false)
            ->assertSee('data-quiz-milestone', false)
            ->assertSee('Треть теста позади')
            ->assertSee($firstPercent.'%');
        $this->get('/quiz')->assertOk()->assertDontSee('data-quiz-milestone', false);

        // Revisiting a saved question cannot repeat a notification or rewind progress.
        $this->post('/quiz/answer', ['navigation' => 'back'])->assertRedirect('/quiz');
        $this->get('/quiz')->assertOk()
            ->assertSee('aria-valuenow="'.$firstMilestone.'"', false)
            ->assertDontSee('data-quiz-milestone', false);
        $this->post('/quiz/answer', ['navigation' => 'next'])->assertRedirect('/quiz');

        for ($position = $firstMilestone + 1; $position <= $secondMilestone; $position++) {
            $questionId = DB::table('questions')->where('position', $position)->value('id');
            $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');

            $this->post('/quiz/answer', [
                'navigation' => 'next',
                'answer_option_id' => $optionId,
            ])->assertRedirect('/quiz');
        }

        $secondPercent = (int) round($secondMilestone * 100 / $total);
        $this->get('/quiz')->assertOk()
            ->assertSee('aria-valuenow="'.$secondMilestone.'"', false)
            ->assertSee('data-quiz-milestone', false)
            ->assertSee('Большая часть теста пройдена')
            ->assertSee($secondPercent.'%');
        $this->get('/quiz')->assertOk()->assertDontSee('data-quiz-milestone', false);
    }

    public function test_selection_is_saved_immediately_and_restored_on_reload(): void
    {
        $uuid = $this->startAttempt();
        $optionId = (int) DB::table('answer_options')->where('question_id', DB::table('questions')->where('position', 1)->value('id'))->value('id');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => $optionId])
            ->assertRedirect('/quiz');
        $attemptId = DB::table('quiz_attempts')->where('uuid', $uuid)->value('id');
        $this->assertDatabaseHas('attempt_answers', ['attempt_id' => $attemptId, 'answer_option_id' => $optionId]);
        $this->post('/quiz/answer', ['navigation' => 'back'])->assertRedirect('/quiz');
        $this->get('/quiz')->assertOk()->assertSee('value="'.$optionId.'" checked', false);
    }

    public function test_back_navigation_and_answer_revision_are_persistent(): void
    {
        $uuid = $this->startAttempt();
        $questionId = DB::table('questions')->where('position', 1)->value('id');
        $options = DB::table('answer_options')->where('question_id', $questionId)->orderBy('position')->limit(2)->pluck('id');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => $options[0]])->assertRedirect('/quiz');
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'current_position' => 2]);
        $this->post('/quiz/answer', ['navigation' => 'back'])->assertRedirect('/quiz');
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'current_position' => 1]);
        $this->post('/quiz/answer', ['navigation' => 'back', 'answer_option_id' => $options[1]])
            ->assertRedirect('/quiz');
        $this->get('/quiz')->assertSee('value="'.$options[1].'" checked', false);
        $this->assertDatabaseCount('attempt_answers', 1);
    }

    public function test_selection_cannot_use_option_from_other_question(): void
    {
        $this->startAttempt();
        $foreignOption = (int) DB::table('answer_options')->where('question_id', DB::table('questions')->where('position', 2)->value('id'))->value('id');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => $foreignOption])
            ->assertSessionHasErrors('answer_option_id');
        $this->assertDatabaseCount('attempt_answers', 0);
    }

    public function test_no_answer_cannot_advance(): void
    {
        $this->startAttempt();
        $this->post('/quiz/answer', ['navigation' => 'next'])->assertSessionHasErrors('answer_option_id');
        $this->assertDatabaseHas('quiz_attempts', ['current_position' => 1]);
    }

    public function test_exactly_25_questions_are_required_for_a_published_bank(): void
    {
        $source = (array) DB::table('questions')->where('position', 25)->first();
        unset($source['id']);
        $source['position'] = 26;
        DB::table('questions')->insert($source);

        $this->post('/attempts')->assertStatus(503);
        $this->assertDatabaseCount('quiz_attempts', 0);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('exactly 25 questions');
        $this->seed(QuestionSeeder::class);
    }

    public function test_existing_attempt_refuses_bank_with_unexpected_extra_question(): void
    {
        $this->startAttempt();
        $source = (array) DB::table('questions')->where('position', 25)->first();
        unset($source['id']);
        $source['position'] = 26;
        DB::table('questions')->insert($source);

        $this->get('/quiz')->assertStatus(503);
        $this->post('/quiz/answer', ['navigation' => 'next'])->assertStatus(503);
        $this->assertDatabaseCount('attempt_answers', 0);
    }

    public function test_completed_attempt_skips_repeat_payment_when_report_is_already_opened(): void
    {
        $uuid = $this->startAttempt();
        DB::table('quiz_attempts')->where('uuid', $uuid)->update([
            'status' => 'completed',
            'correct_count' => 0,
            'elapsed_seconds' => 12,
            'completed_at' => now(),
        ]);

        $this->get('/paywall/'.$uuid)->assertOk();
        $this->get('/checkout/'.$uuid)->assertOk();
        $this->get('/result/'.$uuid)->assertRedirect('/paywall/'.$uuid);

        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->get('/result/'.$uuid)->assertOk();
        $this->get('/paywall/'.$uuid)->assertRedirect('/result/'.$uuid);
        $this->get('/checkout/'.$uuid)->assertRedirect('/result/'.$uuid);
        $this->get('/attempts/'.$uuid.'/resume')->assertRedirect('/result/'.$uuid);
        $this->get('/quiz')->assertRedirect('/result/'.$uuid);

        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->assertDatabaseCount('demo_payments', 1);
        $this->assertDatabaseCount('demo_subscriptions', 1);
    }

    public function test_entire_25_question_funnel_and_idempotent_checkout(): void
    {
        $uuid = $this->startAttempt();
        $correctAnswers = 0;

        for ($position = 1; $position <= 25; $position++) {
            $questionId = DB::table('questions')->where('position', $position)->value('id');
            $option = DB::table('answer_options')
                ->where('question_id', $questionId)
                ->where('position', $position % 6 + 1)
                ->first();

            if ($option->is_correct) {
                $correctAnswers++;
            }

            $this->post('/quiz/answer', [
                'navigation' => $position === 25 ? 'finish' : 'next',
                'answer_option_id' => $option->id,
            ])->assertRedirect($position === 25 ? '/paywall/'.$uuid : '/quiz');
        }

        $this->assertDatabaseCount('attempt_answers', 25);
        $this->assertDatabaseHas('quiz_attempts', [
            'uuid' => $uuid,
            'status' => 'completed',
            'correct_count' => $correctAnswers,
        ]);

        $this->get('/paywall/'.$uuid)->assertOk();
        $this->get('/result/'.$uuid)->assertRedirect('/paywall/'.$uuid);
        $this->get('/checkout/'.$uuid)->assertOk()->assertSee('Демонстрационная оплата');
        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->assertDatabaseCount('demo_payments', 1);
        $this->assertDatabaseHas('demo_payments', [
            'status' => 'succeeded',
            'amount_kopecks' => 2000,
            'future_monthly_kopecks' => 49900,
        ]);

        $report = $this->get('/result/'.$uuid)->assertOk()
            ->assertSee('Ваш результат')
            ->assertSee('По направлениям заданий')
            ->assertDontSee('Подробный разбор 25 вопросов')
            ->assertSee((string) $correctAnswers);

        $breakdown = $report->viewData('categoryBreakdown');
        $this->assertCount(5, $breakdown);
        $this->assertSame(25, array_sum(array_column($breakdown, 'total')));
        $this->assertSame($correctAnswers, array_sum(array_column($breakdown, 'correct')));
        foreach ($breakdown as $category) {
            $this->assertSame(5, $category['total']);
            $this->assertSame($category['correct'] * 20, $category['percentage']);
        }
    }

    public function test_report_uses_persisted_score_for_all_correct_and_all_incorrect_answers(): void
    {
        foreach ([true => 25, false => 0] as $correct => $expected) {
            $uuid = $this->startAttempt();
            for ($position = 1; $position <= 25; $position++) {
                $questionId = (int) DB::table('questions')->where('position', $position)->value('id');
                $optionId = (int) DB::table('answer_options')
                    ->where('question_id', $questionId)
                    ->where('is_correct', (bool) $correct)
                    ->orderBy('id')->value('id');
                $this->post('/quiz/answer', [
                    'navigation' => $position === 25 ? 'finish' : 'next',
                    'answer_option_id' => $optionId,
                ])->assertRedirect($position === 25 ? '/paywall/'.$uuid : '/quiz');
            }
            $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'correct_count' => $expected]);
            $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
            $report = $this->get('/result/'.$uuid)->assertOk()
                ->assertSee($expected === 25 ? '100%' : '0%')
                ->assertDontSee('Подробный разбор')
                ->assertDontSee('Правильно:');
            $categories = $report->viewData('categoryBreakdown');
            $this->assertSame($expected, array_sum(array_column($categories, 'correct')));
        }
    }

    public function test_legacy_in_progress_attempt_starts_new_questions_without_erasing_old_attempt(): void
    {
        $old = $this->startAttempt();
        DB::table('quiz_attempts')->where('uuid', $old)->update(['question_revision' => 1]);
        $this->get('/quiz')->assertRedirect('/quiz');
        $new = (string) session()->get('active_attempt');
        $this->assertNotSame($old, $new);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $old, 'question_revision' => 1]);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $new, 'question_revision' => PuzzleCatalog::VERSION]);
    }

    public function test_completed_previous_revision_keeps_its_saved_score_without_new_category_breakdown(): void
    {
        $uuid = $this->startAttempt();
        DB::table('quiz_attempts')->where('uuid', $uuid)->update([
            'status' => 'completed',
            'question_revision' => PuzzleCatalog::VERSION - 1,
            'bank_fingerprint' => null,
            'option_shuffle_seed' => null,
            'correct_count' => 7,
            'elapsed_seconds' => 58,
            'completed_at' => now(),
        ]);

        $this->get('/attempts/'.$uuid.'/resume')->assertRedirect('/paywall/'.$uuid);
        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);

        $report = $this->get('/result/'.$uuid)->assertOk()
            ->assertSee('Ваш результат')
            ->assertSee('до обновления графических заданий')
            ->assertDontSee('По направлениям заданий');

        $this->assertSame([], $report->viewData('categoryBreakdown'));
        $this->assertDatabaseHas('quiz_attempts', [
            'uuid' => $uuid,
            'question_revision' => PuzzleCatalog::VERSION - 1,
            'correct_count' => 7,
        ]);
    }

    public function test_repeated_start_resumes_the_same_unfinished_attempt_without_resetting_answers(): void
    {
        $uuid = $this->startAttempt();
        $questionId = DB::table('questions')->where('position', 1)->value('id');
        $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => $optionId])
            ->assertRedirect('/quiz');

        $this->post('/attempts')->assertRedirect('/quiz');
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->assertSame($uuid, session('active_attempt'));
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'current_position' => 2]);
        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertDatabaseCount('attempt_answers', 1);
    }

    public function test_abandoned_attempt_starts_a_fresh_timer_and_preserves_history(): void
    {
        $this->actingAs(User::factory()->create());
        $old = $this->startAttempt();
        $questionId = DB::table('questions')->where('position', 1)->value('id');
        $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => $optionId])
            ->assertRedirect('/quiz');

        $this->travel(4)->days();
        // An older deployment may have touched updated_at on a stale attempt.
        DB::table('quiz_attempts')->where('uuid', $old)->update(['updated_at' => now()]);
        $this->post('/attempts')->assertRedirect('/quiz');
        $new = (string) session('active_attempt');
        $this->assertNotSame($old, $new);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $old, 'status' => 'expired', 'current_position' => 2]);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $new, 'current_position' => 1, 'quiz_started_at' => null]);
        $this->assertDatabaseCount('attempt_answers', 1);

        $this->post('/quiz/intake/gender', ['gender' => 'unspecified'])->assertRedirect('/quiz/intake/age');
        $this->post('/quiz/intake/age', ['age_group' => '25_34'])->assertRedirect('/quiz');
        $view = $this->get('/quiz')->assertOk();
        $this->assertEqualsWithDelta($view->viewData('serverTimestamp'), $view->viewData('startedTimestamp'), 1);
        $this->assertEqualsWithDelta(7200, $view->viewData('expiresTimestamp') - $view->viewData('serverTimestamp'), 1);

        $this->post('/quiz/answer', [
            'navigation' => 'next', 'answer_option_id' => $optionId, 'attempt_uuid' => $old,
        ])->assertStatus(409);
        $this->assertDatabaseCount('attempt_answers', 1);
        $this->travelBack();
    }

    public function test_short_resume_keeps_timer_and_answers_do_not_extend_fixed_deadline(): void
    {
        $uuid = $this->startAttempt();
        $start = $this->get('/quiz')->viewData('startedTimestamp');
        $this->travel(15)->minutes();
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->assertSame($uuid, session('active_attempt'));
        $view = $this->get('/quiz')->assertOk();
        $this->assertSame($start, $view->viewData('startedTimestamp'));
        $this->assertEqualsWithDelta(900, $view->viewData('serverTimestamp') - $start, 1);

        $questionId = DB::table('questions')->where('position', 1)->value('id');
        $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => $optionId])
            ->assertRedirect('/quiz');
        $this->travel(104)->minutes();
        $view = $this->get('/quiz')->assertOk();
        $this->assertSame($start, $view->viewData('startedTimestamp'));
        $this->assertSame($start + 7200, $view->viewData('expiresTimestamp'));
        $this->assertSame($uuid, session('active_attempt'));

        $this->travel(1)->minutes();
        $this->get('/quiz')->assertRedirect('/quiz');
        $this->assertNotSame($uuid, session('active_attempt'));
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'status' => 'expired']);
        $this->travelBack();
    }

    public function test_recently_updated_four_day_old_attempt_is_replaced_on_direct_quiz_reload(): void
    {
        $this->actingAs(User::factory()->create());
        $old = $this->startAttempt();
        $questionId = DB::table('questions')->where('position', 1)->value('id');
        $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => $optionId])
            ->assertRedirect('/quiz');

        DB::table('quiz_attempts')->where('uuid', $old)->update([
            'quiz_started_at' => now()->subHours(92),
            'updated_at' => now(),
        ]);

        $this->get('/quiz')->assertRedirect('/quiz');
        $new = (string) session('active_attempt');
        $this->assertNotSame($old, $new);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $old, 'status' => 'expired', 'current_position' => 2]);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $new, 'current_position' => 1, 'quiz_started_at' => null]);
        $this->assertDatabaseCount('attempt_answers', 1);
        $this->get('/quiz')->assertRedirect('/quiz/intake/gender');
        $this->post('/quiz/intake/gender', ['gender' => 'unspecified'])->assertRedirect('/quiz/intake/age');
        $this->post('/quiz/intake/age', ['age_group' => '25_34'])->assertRedirect('/quiz');
        $view = $this->get('/quiz')->assertOk();
        $this->assertEqualsWithDelta($view->viewData('serverTimestamp'), $view->viewData('startedTimestamp'), 1);
    }

    public function test_expired_attempt_rejects_answer_even_when_recently_updated(): void
    {
        $old = $this->startAttempt();
        $questionId = DB::table('questions')->where('position', 1)->value('id');
        $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');
        DB::table('quiz_attempts')->where('uuid', $old)->update([
            'quiz_started_at' => now()->subHours(92),
            'updated_at' => now(),
        ]);

        $this->post('/quiz/answer', [
            'navigation' => 'next',
            'question_id' => $questionId,
            'answer_option_id' => $optionId,
            'attempt_uuid' => $old,
        ])->assertRedirect('/quiz');
        $this->assertDatabaseCount('attempt_answers', 0);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $old, 'status' => 'expired']);
        $this->assertNotSame($old, session('active_attempt'));
    }

    public function test_unpaid_completed_attempt_blocks_new_attempt_until_demo_confirmation(): void
    {
        $uuid = $this->startAttempt();
        DB::table('quiz_attempts')->where('uuid', $uuid)->update([
            'status' => 'completed',
            'correct_count' => 0,
            'elapsed_seconds' => 10,
            'completed_at' => now(),
        ]);

        $this->post('/attempts')->assertRedirect('/paywall/'.$uuid)
            ->assertSessionHas('quiz_gate_notice');
        $this->get('/paywall/'.$uuid)->assertOk()->assertSee('Предыдущая попытка завершена.');
        $this->post('/attempts')->assertRedirect('/paywall/'.$uuid);
        $this->assertDatabaseCount('quiz_attempts', 1);

        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->assertNotSame($uuid, session('active_attempt'));
        $this->assertDatabaseCount('quiz_attempts', 2);
    }

    public function test_authenticated_user_can_save_selection_after_resuming_from_new_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $uuid = $this->startAttempt();
        $this->withSession(['active_attempt' => null, 'owned_attempts' => []]);
        $this->get('/attempts/'.$uuid.'/resume')->assertRedirect('/quiz');

        $questionId = DB::table('questions')->where('position', 1)->value('id');
        $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => $optionId])
            ->assertRedirect('/quiz');

        $attemptId = DB::table('quiz_attempts')->where('uuid', $uuid)->value('id');
        $this->assertDatabaseHas('attempt_answers', [
            'attempt_id' => $attemptId,
            'answer_option_id' => $optionId,
        ]);
    }

    public function test_logged_in_user_resumes_in_progress_attempt_after_session_is_recreated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $uuid = $this->startAttempt();
        $questionId = DB::table('questions')->where('position', 1)->value('id');
        $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => $optionId])
            ->assertRedirect('/quiz');

        $this->withSession(['active_attempt' => null, 'owned_attempts' => []]);
        $this->post('/attempts')->assertRedirect('/quiz');

        $this->assertSame($uuid, session('active_attempt'));
        $this->assertDatabaseHas('quiz_attempts', [
            'uuid' => $uuid, 'user_id' => $user->id, 'current_position' => 2,
        ]);
        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->get('/quiz')->assertOk()->assertSee('из '.DB::table('questions')->count());
    }

    public function test_user_cannot_bypass_unpaid_gate_by_losing_the_browser_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $uuid = $this->startAttempt();
        DB::table('quiz_attempts')->where('uuid', $uuid)->update([
            'status' => 'completed',
            'correct_count' => 0,
            'elapsed_seconds' => 10,
            'completed_at' => now(),
        ]);
        $this->withSession(['active_attempt' => null, 'owned_attempts' => []]);
        $this->post('/attempts')->assertRedirect('/paywall/'.$uuid);
        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->assertDatabaseCount('quiz_attempts', 2);
    }
}
