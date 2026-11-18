<?php

declare(strict_types=1);

namespace Tests\Feature;

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
        $this->get('/quiz')->assertOk()->assertSee('Вопрос')->assertSee('Какой фигуры', false);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'current_position' => 1, 'status' => 'in_progress']);
    }

    public function test_selection_is_saved_immediately_and_restored_on_reload(): void
    {
        $uuid = $this->startAttempt();
        $optionId = (int) DB::table('answer_options')->where('question_id', DB::table('questions')->where('position', 1)->value('id'))->value('id');
        $this->postJson('/quiz/selection', ['answer_option_id' => $optionId])->assertOk()->assertJson(['saved' => true]);
        $attemptId = DB::table('quiz_attempts')->where('uuid', $uuid)->value('id');
        $this->assertDatabaseHas('attempt_answers', ['attempt_id' => $attemptId, 'answer_option_id' => $optionId]);
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
        $this->post('/quiz/selection', ['answer_option_id' => $options[1]])->assertOk();
        $this->get('/quiz')->assertSee('value="'.$options[1].'" checked', false);
        $this->assertDatabaseCount('attempt_answers', 1);
    }

    public function test_selection_cannot_use_option_from_other_question(): void
    {
        $this->startAttempt();
        $foreignOption = (int) DB::table('answer_options')->where('question_id', DB::table('questions')->where('position', 2)->value('id'))->value('id');
        $this->postJson('/quiz/selection', ['answer_option_id' => $foreignOption])->assertUnprocessable();
        $this->assertDatabaseCount('attempt_answers', 0);
    }

    public function test_no_answer_cannot_advance(): void
    {
        $this->startAttempt();
        $this->post('/quiz/answer', ['navigation' => 'next'])->assertSessionHasErrors('answer_option_id');
        $this->assertDatabaseHas('quiz_attempts', ['current_position' => 1]);
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
            ->assertSee('Результаты графического теста')
            ->assertSee('Результаты по типам заданий')
            ->assertSee('Вопрос 25')
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

    public function test_a_new_attempt_does_not_modify_the_previous_one(): void
    {
        $old = $this->startAttempt();
        $new = $this->startAttempt();
        $this->assertNotSame($old, $new);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $old, 'current_position' => 1]);
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $new, 'current_position' => 1]);
        $this->assertDatabaseCount('quiz_attempts', 2);
    }
}
