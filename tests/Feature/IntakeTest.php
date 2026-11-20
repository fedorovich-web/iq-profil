<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\PuzzleCatalog;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class IntakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuestionSeeder::class);
    }

    public function test_intake_requires_two_choices_and_does_not_start_timer_early(): void
    {
        $this->post('/attempts')->assertRedirect('/quiz');
        $uuid = session('active_attempt');

        $this->get('/quiz')->assertRedirect('/quiz/intake/gender');
        $this->get('/quiz/intake/gender')->assertOk()
            ->assertSee('Мужчина')->assertSee('Женщина')->assertSee('Не хочу указывать');
        $this->get('/quiz/intake/age')->assertRedirect('/quiz/intake/gender');
        $this->post('/quiz/intake/gender', ['gender' => 'other'])->assertSessionHasErrors('gender');
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'quiz_started_at' => null, 'age_group' => null]);

        $this->post('/quiz/intake/gender', ['gender' => 'unspecified'])
            ->assertRedirect('/quiz/intake/age');
        $this->get('/quiz')->assertRedirect('/quiz/intake/age');
        $this->get('/quiz/intake/age')->assertOk()->assertSee('25–34 года');
        $this->post('/quiz/intake/age', ['age_group' => '17_18'])->assertSessionHasErrors('age_group');
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'quiz_started_at' => null]);

        $this->post('/quiz/intake/age', ['age_group' => '25_34'])->assertRedirect('/quiz');
        $this->get('/quiz')->assertOk()->assertSee('Вопрос');
        $this->assertDatabaseHas('quiz_attempts', [
            'uuid' => $uuid, 'gender' => 'unspecified', 'age_group' => '25_34',
            'question_revision' => PuzzleCatalog::VERSION,
        ]);
        $this->assertNotNull(DB::table('quiz_attempts')->where('uuid', $uuid)->value('quiz_started_at'));
    }

    public function test_question_post_cannot_skip_intake(): void
    {
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->post('/quiz/answer', ['navigation' => 'next', 'answer_option_id' => 1])
            ->assertRedirect('/quiz');
        $this->assertDatabaseCount('attempt_answers', 0);
        $this->get('/quiz')->assertRedirect('/quiz/intake/gender');
    }

    public function test_seeder_rejects_mutated_published_answer_key(): void
    {
        $correct = DB::table('answer_options')->where('is_correct', true)->first();
        $this->assertNotNull($correct);

        DB::table('answer_options')->where('id', $correct->id)->update(['is_correct' => false]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Published answer options for question');
        $this->seed(QuestionSeeder::class);
    }

    public function test_published_questions_are_immutable_when_seed_is_repeated(): void
    {
        $before = DB::table('questions')->orderBy('position')->pluck('cells', 'id')->all();
        $this->seed(QuestionSeeder::class);
        $this->assertSame($before, DB::table('questions')->orderBy('position')->pluck('cells', 'id')->all());
        $this->assertSame(25, DB::table('questions')->where('bank_version', PuzzleCatalog::VERSION)->count());
        $this->assertSame(150, DB::table('answer_options')->count());
    }
}
