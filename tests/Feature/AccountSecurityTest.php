<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_real_user_and_no_admin_role(): void
    {
        $this->get('/register')->assertOk()->assertSee('Регистрация');
        $this->post('/register', [
            'email' => 'User@EXAMPLE.com',
            'password' => 'StrongPassword7788',
            'password_confirmation' => 'StrongPassword7788',
            'personal_data_consent' => 1,
            'terms_acceptance' => 1,
            'is_admin' => true,
        ])->assertRedirect('/account');
        $this->assertAuthenticated();

        $user = User::where('email', 'user@example.com')->firstOrFail();
        $this->assertFalse($user->is_admin);
        $this->assertTrue(Hash::check('StrongPassword7788', $user->password));
        $this->assertNotSame('StrongPassword7788', $user->password);
        $this->get('/admin')->assertForbidden();
    }

    public function test_guest_cannot_open_private_cabinet_and_admin_dashboard(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Вход в аккаунт');
    }

    public function test_password_authentication_and_logout_work(): void
    {
        $user = User::factory()->create(['email' => 'account@example.com', 'password' => 'StrongPassword1122']);
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'StrongPassword1122'])->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);
        $this->get('/account')->assertOk();
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_standard_user_cannot_become_admin_through_profile(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);
        $this->patch('/account/profile', ['name' => 'Новый пользователь', 'email' => 'safe@example.com', 'is_admin' => true])->assertRedirect();
        $user->refresh();
        $this->assertFalse($user->is_admin);
        $this->get('/admin')->assertForbidden();
    }

    public function test_admin_only_access_is_based_on_persisted_role(): void
    {
        $admin = User::factory()->create();
        $this->artisan('iq:make-admin', ['email' => $admin->email])->assertExitCode(0);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_admin' => 1]);
        $this->assertDatabaseHas('admin_audit_logs', ['admin_id' => $admin->id, 'action' => 'granted_administrator_role_by_cli']);
        $this->actingAs($admin->fresh());
        $this->get('/admin')->assertOk()->assertSee('Обзор системы');
        $this->get('/admin?section=users')->assertOk()->assertSee($admin->email);
        $this->get('/admin?section=attempts')->assertOk();
        $this->get('/admin?section=transactions')->assertOk();
        $this->get('/admin?section=reports')->assertOk();
        $this->get('/admin?section=audit')->assertOk();
        $this->get('/admin?section=nonexistent')->assertNotFound();
    }

    public function test_anonymous_attempt_is_claimed_by_newly_registered_user(): void
    {
        $this->seed(QuestionSeeder::class);
        $this->post('/attempts')->assertRedirect('/quiz');
        $uuid = (string) session('active_attempt');
        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'user_id' => null]);

        $this->post('/register', [
            'email' => 'claimed@example.com',
            'password' => 'StrongPassword7788',
            'password_confirmation' => 'StrongPassword7788',
            'personal_data_consent' => 1,
            'terms_acceptance' => 1,
        ])->assertRedirect('/account');

        $this->assertDatabaseHas('quiz_attempts', ['uuid' => $uuid, 'user_id' => auth()->id()]);
        $this->get('/account?section=history')->assertOk()->assertSee('Графический тест');
        $this->get('/attempts/'.$uuid.'/resume')->assertRedirect('/quiz');
    }

    public function test_change_password_checks_current_password_and_persists_new_hash(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword12345']);
        $this->actingAs($user);
        $this->patch('/account/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword12345',
            'password_confirmation' => 'NewPassword12345',
        ])->assertSessionHasErrors('current_password');

        $this->patch('/account/password', [
            'current_password' => 'OldPassword12345',
            'password' => 'NewPassword12345',
            'password_confirmation' => 'NewPassword12345',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('NewPassword12345', $user->fresh()->password));
        $this->assertFalse(Hash::check('OldPassword12345', $user->fresh()->password));
    }

    public function test_new_yandex_account_requires_separate_confirmations(): void
    {
        $pending = ['id' => 'test-yandex-identifier', 'email' => 'yandex-user@example.com', 'name' => 'Тестовый пользователь'];
        $this->withSession(['pending_yandex' => $pending]);
        $this->get('/auth/yandex/consent')->assertOk()
            ->assertSee('Подтверждение регистрации');
        $this->post('/auth/yandex/consent', ['terms_acceptance' => 1])
            ->assertSessionHasErrors('personal_data_consent');
        $this->assertDatabaseCount('users', 0);

        $this->post('/auth/yandex/consent', [
            'terms_acceptance' => 1,
            'personal_data_consent' => 1,
        ])->assertRedirect('/account');
        $user = User::where('yandex_id', $pending['id'])->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('personal_data_consents', [
            'user_id' => $user->id, 'purpose' => 'account_and_quiz',
        ]);
    }

    public function test_prepared_avif_icon_assets_are_rendered_in_public_and_private_views(): void
    {
        $assets = [
            'client/history.avif',
            'client/download.avif',
            'client/layers.avif',
            'client/reports.avif',
            'client/security.avif',
            'admin/analytics.avif',
            'admin/lock.avif',
            'admin/subscription.avif',
            'admin/users.avif',
            'admin/test-attempts.avif',
            'admin/demo-transactions.avif',
        ];

        foreach ($assets as $asset) {
            $this->assertFileExists(public_path('assets/icons/'.$asset));
        }

        $account = User::factory()->create(['is_admin' => true]);
        $this->actingAs($account);

        $this->get('/account')->assertOk()
            ->assertSee('/assets/icons/client/layers.avif')
            ->assertSee('/assets/icons/client/reports.avif');

        $this->get('/admin')->assertOk()
            ->assertSee('/assets/icons/admin/users.avif')
            ->assertSee('/assets/icons/admin/test-attempts.avif')
            ->assertSee('/assets/icons/admin/analytics.avif')
            ->assertSee('/assets/icons/admin/demo-transactions.avif');

        $this->seed(QuestionSeeder::class);
        $this->post('/attempts')->assertRedirect('/quiz');
        $uuid = (string) session('active_attempt');
        $this->get('/quiz')->assertOk()->assertSee('/assets/icons/client/history.avif');
        DB::table('quiz_attempts')->where('uuid', $uuid)->update([
            'status' => 'completed',
            'correct_count' => 0,
            'elapsed_seconds' => 12,
            'completed_at' => now(),
        ]);

        $this->get('/paywall/'.$uuid)->assertOk()
            ->assertSee('/assets/icons/admin/analytics.avif')
            ->assertSee('/assets/icons/client/layers.avif')
            ->assertSee('/assets/icons/client/history.avif')
            ->assertSee('/assets/icons/client/reports.avif')
            ->assertSee('/assets/icons/admin/lock.avif')
            ->assertSee('/assets/icons/admin/subscription.avif');

        $this->get('/checkout/'.$uuid)->assertOk()
            ->assertSee('/assets/icons/client/security.avif');
        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->get('/account')->assertOk()->assertSee('/assets/icons/client/download.avif');
        $this->get('/account?section=reports')->assertOk()->assertSee('/assets/icons/client/download.avif');
    }

    public function test_account_shows_active_and_cancelled_demo_subscription_states(): void
    {
        $this->seed(QuestionSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/account')->assertOk()
            ->assertSee('Нет активных демо-продлений')
            ->assertSee('aria-label="Выйти из аккаунта"', false)
            ->assertSee('assets/icons/client/logout.avif');
        $this->get('/account?section=subscription')->assertOk()
            ->assertSee('Нет активных демо-продлений')
            ->assertDontSee('Отменить демо-продление');

        $this->post('/attempts')->assertRedirect('/quiz');
        $uuid = (string) session('active_attempt');
        $attemptId = DB::table('quiz_attempts')->where('uuid', $uuid)->value('id');
        DB::table('quiz_attempts')->where('id', $attemptId)->update([
            'status' => 'completed',
            'correct_count' => 0,
            'elapsed_seconds' => 10,
            'completed_at' => now(),
        ]);

        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->get('/account')->assertOk()
            ->assertSee('Активное демо-продление')
            ->assertDontSee('Нет активных демо-продлений');
        $this->get('/account?section=subscription')->assertOk()
            ->assertSee('Активна (демо)')
            ->assertSee('Отменить демо-продление')
            ->assertSee('499 ₽');

        $this->post('/subscription/cancel', ['confirm_cancel' => 1])
            ->assertRedirect('/subscription/cancel');
        $this->assertDatabaseHas('demo_subscriptions', ['attempt_id' => $attemptId, 'status' => 'cancelled']);
        $this->get('/account')->assertOk()
            ->assertSee('Нет активных демо-продлений')
            ->assertDontSee('Активное демо-продление');
        $this->get('/account?section=subscription')->assertOk()
            ->assertSee('Нет активных демо-продлений')
            ->assertDontSee('Отменить демо-продление');
        $this->get('/result/'.$uuid)->assertOk();
        $this->get('/checkout/'.$uuid)->assertRedirect('/result/'.$uuid);
    }

    public function test_attempts_and_exports_are_isolated_by_authenticated_owner(): void
    {
        $this->seed(QuestionSeeder::class);
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $this->actingAs($owner);
        $this->post('/attempts')->assertRedirect('/quiz');
        $uuid = (string) session('active_attempt');
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->first();

        for ($position = 1; $position <= 25; $position++) {
            $questionId = DB::table('questions')->where('position', $position)->value('id');
            $choice = DB::table('answer_options')->where('question_id', $questionId)->first();
            $this->post('/quiz/answer', [
                'navigation' => $position === 25 ? 'finish' : 'next',
                'answer_option_id' => $choice->id,
            ])->assertRedirect();
        }
        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->get('/account/export/'.$uuid)->assertOk()->assertDownload('iq-profil-'.$uuid.'.csv');
        $this->get('/result/'.$uuid)->assertOk();

        $this->actingAs($stranger);
        $this->get('/result/'.$uuid)->assertNotFound();
        $this->get('/account/export/'.$uuid)->assertNotFound();
        $this->get('/attempts/'.$uuid.'/resume')->assertNotFound();
        $this->get('/account')->assertOk()->assertDontSee($uuid);
    }
}
