<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\QuestionSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CodeAuditRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_scalar_registration_email_is_a_validation_error_not_a_server_error(): void
    {
        $this->post('/register', [
            'email' => ['unexpected' => 'name@example.test'],
            'password' => 'StrongPassword7788',
            'password_confirmation' => 'StrongPassword7788',
            'personal_data_consent' => '1',
            'terms_acceptance' => '1',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_non_scalar_profile_email_does_not_modify_user(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.test']);
        $this->actingAs($user);

        $this->patch('/account/profile', [
            'name' => 'Owner',
            'email' => ['unexpected' => 'other@example.test'],
        ])->assertSessionHasErrors('email');

        $this->assertSame('owner@example.test', $user->fresh()->email);
    }

    public function test_password_reset_accepts_normalized_case_insensitive_email(): void
    {
        $user = User::factory()->create(['email' => 'reset-owner@example.test']);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => '  RESET-OWNER@EXAMPLE.TEST  ',
            'password' => 'NewStrongPassword2233',
            'password_confirmation' => 'NewStrongPassword2233',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('NewStrongPassword2233', $user->fresh()->password));
    }

    public function test_admin_search_rejects_malformed_queries_without_php_warnings(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin?section=users&search%5B%5D=ignored')->assertStatus(422);
    }

    public function test_admin_reports_do_not_query_unrelated_paginated_lists(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $queries = [];

        DB::listen(static function (QueryExecuted $query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->get('/admin?section=reports')->assertOk()->assertSee('Операционные метрики');

        $sql = implode("\n", $queries);
        $this->assertStringNotContainsString('admin_audit_logs', $sql);
        $this->assertStringNotContainsString('left join', $sql);
        $this->assertStringNotContainsString('order by', $sql);
    }

    public function test_account_lists_are_paginated_without_exposing_another_users_attempts(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        foreach (range(1, 17) as $position) {
            $id = DB::table('quiz_attempts')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'user_id' => $owner->id,
                'status' => 'completed',
                'current_position' => 25,
                'correct_count' => 1,
                'question_count' => 25,
                'question_revision' => 8,
                'test_code' => 'matrix-reasoning',
                'started_at' => now()->subMinutes($position),
                'completed_at' => now()->subMinutes($position),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('demo_payments')->insert([
                'attempt_id' => $id,
                'status' => 'succeeded',
                'amount_kopecks' => 2000,
                'currency' => 'RUB',
                'future_monthly_kopecks' => 49900,
                'confirmed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $strangerUuid = (string) Str::uuid();
        DB::table('quiz_attempts')->insert([
            'uuid' => $strangerUuid,
            'user_id' => $stranger->id,
            'status' => 'in_progress',
            'current_position' => 1,
            'question_revision' => 8,
            'test_code' => 'matrix-reasoning',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner);
        $history = $this->get('/account?section=history')->assertOk()->getContent();
        $this->assertSame(15, substr_count($history, 'data-label="Дата"'));
        $this->assertStringNotContainsString($strangerUuid, $history);
        $historyNext = $this->get('/account?section=history&historyPage=2')->assertOk()->getContent();
        $this->assertSame(2, substr_count($historyNext, 'data-label="Дата"'));
        $this->assertStringNotContainsString($strangerUuid, $historyNext);

        $reports = $this->get('/account?section=reports')->assertOk()->getContent();
        $this->assertSame(15, substr_count($reports, 'class="portal-report-row"'));
        $reportsNext = $this->get('/account?section=reports&reportsPage=2')->assertOk()->getContent();
        $this->assertSame(2, substr_count($reportsNext, 'class="portal-report-row"'));

        $overview = $this->get('/account')->assertOk()->getContent();
        $this->assertSame(3, substr_count($overview, 'class="portal-report-row"'));
    }

    public function test_account_percentage_uses_the_frozen_question_count_not_saved_answer_count(): void
    {
        $this->seed(QuestionSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->post('/attempts')->assertRedirect('/quiz');

        $attempt = DB::table('quiz_attempts')->where('user_id', $user->id)->firstOrFail();
        DB::table('quiz_attempts')->where('id', $attempt->id)->update([
            'status' => 'completed', 'correct_count' => 1, 'question_count' => 25,
            'completed_at' => now(),
        ]);

        $question = DB::table('questions')->where('test_code', $attempt->test_code)
            ->where('bank_version', $attempt->question_revision)->orderBy('position')->firstOrFail();
        $option = DB::table('answer_options')->where('question_id', $question->id)->firstOrFail();
        DB::table('attempt_answers')->insert([
            'attempt_id' => $attempt->id, 'question_id' => $question->id,
            'answer_option_id' => $option->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->post('/checkout/'.$attempt->uuid.'/confirm')
            ->assertRedirect('/result/'.$attempt->uuid);

        $this->get('/account')->assertOk()->assertSee('4% правильных ответов')
            ->assertDontSee('100% правильных ответов');

        DB::table('quiz_attempts')->where('id', $attempt->id)->update(['question_count' => null]);
        $this->get('/account')->assertOk()
            ->assertSee('Процент для этой архивной попытки недоступен');
    }
}
