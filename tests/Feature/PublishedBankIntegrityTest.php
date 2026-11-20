<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\PuzzleCatalog;
use App\Support\QuestionBankIntegrity;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PublishedBankIntegrityTest extends TestCase
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

    private function chooseCorrect(int $position): void
    {
        $question = DB::table('questions')
            ->where('test_code', PuzzleCatalog::TEST_CODE)
            ->where('bank_version', PuzzleCatalog::VERSION)
            ->where('position', $position)->first();
        $option = DB::table('answer_options')->where('question_id', $question->id)
            ->where('is_correct', true)->value('id');

        $this->post('/quiz/answer', [
            'question_id' => $question->id,
            'answer_option_id' => $option,
            'navigation' => $position === PuzzleCatalog::COUNT ? 'finish' : 'next',
        ])->assertRedirect($position === PuzzleCatalog::COUNT
            ? '/paywall/'.session('active_attempt') : '/quiz');
    }

    private function swapKey(int $position): void
    {
        $questionId = DB::table('questions')
            ->where('test_code', PuzzleCatalog::TEST_CODE)
            ->where('bank_version', PuzzleCatalog::VERSION)
            ->where('position', $position)->value('id');
        $correctId = DB::table('answer_options')->where('question_id', $questionId)
            ->where('is_correct', true)->value('id');
        $wrongId = DB::table('answer_options')->where('question_id', $questionId)
            ->where('is_correct', false)->orderBy('id')->value('id');

        DB::table('answer_options')->where('id', $correctId)->update(['is_correct' => false]);
        DB::table('answer_options')->where('id', $wrongId)->update(['is_correct' => true]);
    }

    public function test_new_attempt_pins_the_full_bank_including_correct_keys(): void
    {
        $uuid = $this->begin();
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->first();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/D', $attempt->bank_fingerprint);
        $this->assertSame(
            QuestionBankIntegrity::fingerprint(PuzzleCatalog::TEST_CODE, PuzzleCatalog::VERSION),
            $attempt->bank_fingerprint
        );
        $this->assertTrue(QuestionBankIntegrity::matches($attempt));

        $this->seed(QuestionSeeder::class);
        $this->assertTrue(QuestionBankIntegrity::matches($attempt));
    }

    public function test_same_size_answer_key_mutation_blocks_finishing_without_changing_score(): void
    {
        $uuid = $this->begin();
        for ($position = 1; $position < PuzzleCatalog::COUNT; $position++) {
            $this->chooseCorrect($position);
        }

        $last = DB::table('questions')
            ->where('bank_version', PuzzleCatalog::VERSION)
            ->where('test_code', PuzzleCatalog::TEST_CODE)
            ->where('position', PuzzleCatalog::COUNT)->first();
        $lastOption = DB::table('answer_options')->where('question_id', $last->id)
            ->where('is_correct', true)->value('id');

        $this->swapKey(PuzzleCatalog::COUNT);
        $this->post('/quiz/answer', [
            'question_id' => $last->id,
            'answer_option_id' => $lastOption,
            'navigation' => 'finish',
        ])->assertStatus(409);

        $this->assertDatabaseHas('quiz_attempts', [
            'uuid' => $uuid, 'status' => 'in_progress', 'correct_count' => null,
        ]);
        $attemptId = DB::table('quiz_attempts')->where('uuid', $uuid)->value('id');
        $this->assertSame(PuzzleCatalog::COUNT - 1, DB::table('attempt_answers')
            ->where('attempt_id', $attemptId)->count());
    }

    public function test_same_size_prompt_mutation_blocks_quiz_render_and_answer(): void
    {
        $uuid = $this->begin();
        $questionId = DB::table('questions')->where('test_code', PuzzleCatalog::TEST_CODE)
            ->where('bank_version', PuzzleCatalog::VERSION)->where('position', 1)->value('id');
        $optionId = DB::table('answer_options')->where('question_id', $questionId)->value('id');

        DB::table('questions')->where('id', $questionId)
            ->update(['prompt' => 'Unexpected rewritten prompt']);

        $this->get('/quiz')->assertStatus(409);
        $this->post('/quiz/answer', [
            'navigation' => 'next', 'question_id' => $questionId, 'answer_option_id' => $optionId,
        ])->assertStatus(409);

        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'status' => 'in_progress']);
        $this->assertDatabaseCount('attempt_answers', 0);
    }

    public function test_completed_score_stays_frozen_but_tampered_review_is_not_shown(): void
    {
        $uuid = $this->begin();
        for ($position = 1; $position <= PuzzleCatalog::COUNT; $position++) {
            $this->chooseCorrect($position);
        }
        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $before = $this->get('/result/'.$uuid)->assertOk();
        $this->assertCount(PuzzleCatalog::COUNT, $before->viewData('answerReview'));
        $this->assertCount(5, $before->viewData('categoryBreakdown'));

        $this->swapKey(1);
        $after = $this->get('/result/'.$uuid)->assertOk()
            ->assertSee('100%');
        $this->assertSame([], $after->viewData('categoryBreakdown'));
        $this->assertTrue($after->viewData('answerReview')->isEmpty());
        $this->assertDatabaseHas('quiz_attempts', [
            'uuid' => $uuid, 'status' => 'completed', 'correct_count' => PuzzleCatalog::COUNT,
        ]);
    }

    public function test_adding_an_unrelated_test_cannot_invalidate_existing_snapshot(): void
    {
        $uuid = $this->begin();
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->first();
        DB::table('questions')->insert([
            'test_code' => 'future-attention',
            'bank_version' => PuzzleCatalog::VERSION,
            'position' => 1,
            'kind' => 'future-exercise',
            'prompt' => 'Independent test',
            'explanation' => 'Independent rule',
            'cells' => json_encode(array_fill(0, 9, 1), JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertTrue(QuestionBankIntegrity::matches($attempt));
        $this->get('/quiz')->assertOk();
    }
}
