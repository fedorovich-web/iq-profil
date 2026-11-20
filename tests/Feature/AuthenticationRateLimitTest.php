<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthenticationRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_exhausting_login_budget_does_not_block_a_valid_registration(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'absent@example.test',
                'password' => 'incorrect',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => 'absent@example.test',
            'password' => 'incorrect',
        ])->assertStatus(429);

        $this->post('/register', [
            'email' => 'new-account@example.test',
            'password' => 'StrongPassword123',
            'password_confirmation' => 'StrongPassword123',
            'personal_data_consent' => '1',
            'terms_acceptance' => '1',
        ])->assertRedirect('/account');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'new-account@example.test']);
    }

    public function test_registration_stops_ninth_attempt_for_one_session_but_not_another(): void
    {
        // The HTTP test client does not preserve response cookies automatically.
        // Explicitly set a stable encrypted session cookie like a real browser.
        $this->withCookie(config('session.cookie'), str_repeat('a', 40));

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $this->post('/register', ['email' => 'invalid'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/register', ['email' => 'invalid'])->assertStatus(429);

        // New browser session on the same public IP gets its own small budget.
        // The broad IP cap still protects against unlimited session rotation.
        $this->withCookie(config('session.cookie'), str_repeat('b', 40));
        $this->get('/register')->assertOk();
        $this->post('/register', ['email' => 'invalid'])->assertSessionHasErrors('email');
    }

    public function test_changing_sessions_cannot_bypass_broad_ip_registration_limit(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.11']);

        for ($attempt = 0; $attempt < 40; $attempt++) {
            $this->withCookie(config('session.cookie'), str_pad((string) $attempt, 40, 'x'));
            $this->post('/register', ['email' => 'invalid'])
                ->assertSessionHasErrors('email');
        }

        $this->withCookie(config('session.cookie'), str_repeat('z', 40));
        $this->post('/register', ['email' => 'invalid'])->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.12']);
        $this->post('/register', ['email' => 'invalid'])->assertSessionHasErrors('email');
    }
}
