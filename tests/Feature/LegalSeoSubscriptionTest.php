<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LegalSeoSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(QuestionSeeder::class);
    }

    public function test_robots_contains_distinct_yandex_google_groups_and_sitemap(): void
    {
        $response = $this->get('/robots.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $content = $response->getContent();

        $this->assertStringContainsString("User-agent: Yandex\n", $content);
        $this->assertStringContainsString('Clean-param: utm_source', $content);
        $this->assertStringContainsString("User-agent: Googlebot\n", $content);
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /account', $content);
        $this->assertStringContainsString('Disallow: /admin', $content);
        $this->assertStringContainsString('Disallow: /checkout/', $content);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $content);
        $this->assertStringNotContainsString('Disallow: /privacy', $content);
    }

    public function test_sitemap_contains_only_public_routes_and_is_valid_xml(): void
    {
        $response = $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $content = $response->getContent();
        $this->assertStringContainsString('<loc>'.rtrim(route('landing'), '/').'/</loc>', $content);
        foreach (['legal.privacy', 'legal.offer', 'legal.consent'] as $name) {
            $this->assertStringNotContainsString(route($name), $content);
        }
        foreach (['/account', '/admin', '/result/', '/checkout/', '/login'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $content);
        }
        $this->assertCount(1, $xml->url);
    }

    public function test_legal_pages_are_public_and_in_footer(): void
    {
        $this->get('/')->assertOk()->assertSee('Отмена подписки')
            ->assertSee(route('legal.privacy'))
            ->assertSee(route('legal.consent'))
            ->assertSee(route('legal.offer'));
        $this->get('/privacy')->assertOk()->assertSee('Политика обработки персональных данных')
            ->assertSee('name="robots" content="noindex,nofollow,noarchive"', false);
        $this->get('/offer')->assertOk()->assertSee('Условия использования')
            ->assertSee('name="robots" content="noindex,nofollow,noarchive"', false);
        $this->get('/personal-data-consent')->assertOk()->assertSee('Согласие на обработку персональных данных')
            ->assertSee('name="robots" content="noindex,nofollow,noarchive"', false);
        $this->get('/subscription/cancel')->assertOk()->assertSee('Отмена демонстрационной подписки');
    }

    public function test_registration_requires_separate_consent_and_records_proof(): void
    {
        $email = 'consent@example.com';
        $base = [
            'email' => $email,
            'password' => 'Password1122test',
            'password_confirmation' => 'Password1122test',
            'terms_acceptance' => '1',
        ];
        $this->post('/register', $base)->assertSessionHasErrors('personal_data_consent');
        $this->assertDatabaseCount('users', 0);

        $this->post('/register', [...$base, 'personal_data_consent' => '1'])
            ->assertRedirect('/account');
        $id = User::where('email', $email)->firstOrFail()->id;
        $this->assertDatabaseHas('personal_data_consents', [
            'user_id' => $id,
            'purpose' => 'account_and_quiz',
            'document_version' => '2026-11-18',
            'withdrawn_at' => null,
        ]);
    }

    private function completeAsCurrentSession(): string
    {
        $this->post('/attempts')->assertRedirect('/quiz');
        $this->post('/quiz/intake/gender', ['gender' => 'unspecified'])->assertRedirect('/quiz/intake/age');
        $this->post('/quiz/intake/age', ['age_group' => '25_34'])->assertRedirect('/quiz');
        $this->get('/quiz')->assertOk();
        $uuid = (string) session()->get('active_attempt');
        for ($position = 1; $position <= 25; $position++) {
            $qid = DB::table('questions')->where('position', $position)->value('id');
            $optionId = DB::table('answer_options')->where('question_id', $qid)->value('id');
            $this->post('/quiz/answer', [
                'navigation' => $position === 25 ? 'finish' : 'next',
                'answer_option_id' => $optionId,
            ])->assertRedirect();
        }
        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);

        return $uuid;
    }

    public function test_guest_can_cancel_virtual_subscription_and_repeated_confirm_cannot_restore_it(): void
    {
        $uuid = $this->completeAsCurrentSession();
        $attempt = DB::table('quiz_attempts')->where('uuid', $uuid)->first();
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $attempt->id,
            'status' => 'active',
            'monthly_amount_kopecks' => 49900,
        ]);
        $this->get('/subscription/cancel')->assertOk()->assertSee('Активна (демо)');
        $this->post('/subscription/cancel', ['confirm_cancel' => 1])
            ->assertRedirect('/subscription/cancel');
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $attempt->id,
            'status' => 'cancelled',
        ]);
        $this->post('/subscription/cancel', ['confirm_cancel' => 1])
            ->assertRedirect('/subscription/cancel');
        $this->post('/checkout/'.$uuid.'/confirm')->assertRedirect('/result/'.$uuid);
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $attempt->id,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseCount('demo_subscriptions', 1);
        $this->assertDatabaseCount('demo_payments', 1);
        $this->get('/result/'.$uuid)->assertOk();
    }

    public function test_new_account_purchase_supersedes_prior_demo_subscription_without_revoking_reports(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $first = $this->completeAsCurrentSession();
        $second = $this->completeAsCurrentSession();
        $firstId = DB::table('quiz_attempts')->where('uuid', $first)->value('id');
        $secondId = DB::table('quiz_attempts')->where('uuid', $second)->value('id');

        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $firstId, 'status' => 'superseded', 'active_scope_key' => null,
        ]);
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $secondId, 'status' => 'active', 'active_scope_key' => 'u:'.$user->id,
        ]);
        $this->assertDatabaseCount('demo_payments', 2);
        $this->assertSame(1, DB::table('demo_subscriptions')->where('status', 'active')->count());
        $this->get('/subscription/cancel')->assertOk()->assertSee('Заменена новым демо-продлением');
        $this->get('/result/'.$first)->assertOk();
        $this->get('/result/'.$second)->assertOk();

        // A replayed, already-paid older attempt must not supersede the new active subscription.
        $this->post('/checkout/'.$first.'/confirm')->assertRedirect('/result/'.$first);
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $secondId, 'status' => 'active',
        ]);

        $this->post('/subscription/cancel', ['confirm_cancel' => 1])
            ->assertRedirect('/subscription/cancel');
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $secondId, 'status' => 'cancelled', 'active_scope_key' => null,
        ]);
        $this->post('/checkout/'.$second.'/confirm')->assertRedirect('/result/'.$second);
        $this->assertSame(0, DB::table('demo_subscriptions')->where('status', 'active')->count());
        $this->assertDatabaseCount('demo_payments', 2);
        $this->get('/result/'.$first)->assertOk();
        $this->get('/result/'.$second)->assertOk();
    }

    public function test_guest_scope_is_not_shared_with_other_guests(): void
    {
        $first = $this->completeAsCurrentSession();
        $second = $this->completeAsCurrentSession();

        $firstId = DB::table('quiz_attempts')->where('uuid', $first)->value('id');
        $secondId = DB::table('quiz_attempts')->where('uuid', $second)->value('id');
        $this->assertDatabaseHas('demo_subscriptions', ['attempt_id' => $firstId, 'status' => 'superseded']);
        $active = DB::table('demo_subscriptions')->where('attempt_id', $secondId)->first();
        $this->assertSame('active', $active->status);
        $this->assertStringStartsWith('g:', $active->active_scope_key);
        $this->assertSame(66, strlen($active->active_scope_key));

        // A different browser session must not see or cancel these subscriptions.
        $this->flushSession();
        $this->get('/subscription/cancel')->assertOk()->assertDontSee('Активна (демо)');
        $this->post('/subscription/cancel', ['confirm_cancel' => 1])
            ->assertRedirect('/subscription/cancel');
        $this->assertDatabaseHas('demo_subscriptions', ['attempt_id' => $secondId, 'status' => 'active']);
        $this->get('/result/'.$first)->assertNotFound();
        $this->get('/result/'.$second)->assertNotFound();
    }

    public function test_guest_subscription_is_claimed_and_rescoped_on_registration(): void
    {
        $guestUuid = $this->completeAsCurrentSession();
        $guestAttempt = DB::table('quiz_attempts')->where('uuid', $guestUuid)->first();

        $this->post('/register', [
            'email' => 'guest-claim@example.com',
            'password' => 'StrongPassword7788',
            'password_confirmation' => 'StrongPassword7788',
            'personal_data_consent' => 1,
            'terms_acceptance' => 1,
        ])->assertRedirect('/account');

        $userId = auth()->id();
        $this->assertNotNull($userId);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $guestAttempt->id, 'user_id' => $userId]);
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $guestAttempt->id,
            'status' => 'active',
            'active_scope_key' => 'u:'.$userId,
        ]);
        $this->get('/account?section=subscription')->assertOk()->assertSee('Активна (демо)');

        $next = $this->completeAsCurrentSession();
        $nextId = DB::table('quiz_attempts')->where('uuid', $next)->value('id');
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $guestAttempt->id, 'status' => 'superseded', 'active_scope_key' => null,
        ]);
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $nextId, 'status' => 'active', 'active_scope_key' => 'u:'.$userId,
        ]);
        $this->get('/result/'.$guestUuid)->assertOk();
    }

    public function test_login_merges_guest_and_existing_account_subscription_without_duplicate_active_records(): void
    {
        $user = User::factory()->create([
            'email' => 'subscription-merge@example.com',
            'password' => 'StrongPassword1122',
        ]);
        $this->actingAs($user);

        $accountUuid = $this->completeAsCurrentSession();
        $accountId = DB::table('quiz_attempts')->where('uuid', $accountUuid)->value('id');

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $guestUuid = $this->completeAsCurrentSession();
        $guestId = DB::table('quiz_attempts')->where('uuid', $guestUuid)->value('id');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'StrongPassword1122',
        ])->assertRedirect('/account');

        $this->assertDatabaseHas('quiz_attempts', ['id' => $guestId, 'user_id' => $user->id]);
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $accountId,
            'status' => 'superseded',
            'active_scope_key' => null,
        ]);
        $this->assertDatabaseHas('demo_subscriptions', [
            'attempt_id' => $guestId,
            'status' => 'active',
            'active_scope_key' => 'u:'.$user->id,
        ]);
        $this->assertSame(1, DB::table('demo_subscriptions')->where('status', 'active')->count());
        $this->get('/result/'.$accountUuid)->assertOk();
        $this->get('/result/'.$guestUuid)->assertOk();
    }

    public function test_subscription_cancellation_is_scoped_to_account_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner);
        $uuid = $this->completeAsCurrentSession();
        $attemptId = DB::table('quiz_attempts')->where('uuid', $uuid)->value('id');

        $this->actingAs($other);
        $this->get('/subscription/cancel')->assertOk()->assertDontSee('Активна (демо)');
        $this->post('/subscription/cancel', ['confirm_cancel' => 1])->assertRedirect('/subscription/cancel');
        $this->assertDatabaseHas('demo_subscriptions', ['attempt_id' => $attemptId, 'status' => 'active']);

        $this->actingAs($owner);
        $this->get('/subscription/cancel')->assertSee('Активна (демо)');
        $this->post('/subscription/cancel', ['confirm_cancel' => 1]);
        $this->assertDatabaseHas('demo_subscriptions', ['attempt_id' => $attemptId, 'status' => 'cancelled']);
    }
}
