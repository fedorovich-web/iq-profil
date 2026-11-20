<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CodeSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_selection_and_private_storage_routes_are_unavailable(): void
    {
        $this->assertFalse(Route::has('quiz.selection'));
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));

        $this->post('/quiz/selection')->assertNotFound();
        $this->get('/storage/private-file.txt')->assertNotFound();
        $this->put('/storage/private-file.txt')->assertNotFound();
    }

    public function test_stale_quiz_submission_cannot_modify_another_question(): void
    {
        $this->seed(QuestionSeeder::class);
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->post('/quiz/intake/gender', ['gender' => 'unspecified'])->assertRedirect('/quiz/intake/age');
        $this->post('/quiz/intake/age', ['age_group' => '25_34'])->assertRedirect('/quiz');
        $this->get('/quiz')->assertOk();

        $firstQuestionId = (int) DB::table('questions')->where('position', 1)->value('id');
        $secondQuestionId = (int) DB::table('questions')->where('position', 2)->value('id');
        $firstAnswerId = (int) DB::table('answer_options')->where('question_id', $firstQuestionId)->value('id');

        $this->post('/quiz/answer', [
            'navigation' => 'next',
            'question_id' => $firstQuestionId,
            'answer_option_id' => $firstAnswerId,
        ])->assertRedirect('/quiz');

        $this->post('/quiz/answer', [
            'navigation' => 'back',
            'question_id' => $firstQuestionId,
        ])->assertStatus(409);

        $this->assertDatabaseHas('quiz_attempts', [
            'uuid' => session('active_attempt'),
            'current_position' => 2,
        ]);
        $this->assertDatabaseMissing('attempt_answers', [
            'question_id' => $secondQuestionId,
        ]);
    }

    public function test_future_question_banks_can_store_attempt_counters_above_255(): void
    {
        $this->seed(QuestionSeeder::class);
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->post('/quiz/intake/gender', ['gender' => 'unspecified'])->assertRedirect('/quiz/intake/age');
        $this->post('/quiz/intake/age', ['age_group' => '25_34'])->assertRedirect('/quiz');
        $this->get('/quiz')->assertOk();
        $uuid = (string) session('active_attempt');

        DB::table('quiz_attempts')->where('uuid', $uuid)->update([
            'current_position' => 300,
            'correct_count' => 275,
        ]);

        $this->assertDatabaseHas('quiz_attempts', [
            'uuid' => $uuid,
            'current_position' => 300,
            'correct_count' => 275,
        ]);
    }

    public function test_password_change_rotates_remember_token_without_logging_out_current_session(): void
    {
        $user = User::factory()->create([
            'password' => 'Password772255',
            'remember_token' => Str::random(60),
        ]);
        $oldToken = $user->remember_token;

        $this->actingAs($user)->patch('/account/password', [
            'current_password' => 'Password772255',
            'password' => 'DifferentPassword882244',
            'password_confirmation' => 'DifferentPassword882244',
        ])->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('DifferentPassword882244', $user->password));
        $this->assertNotSame($oldToken, $user->remember_token);
        $this->get('/account')->assertOk();
    }
}
