<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DemoSubscriptionLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

final class YandexController extends Controller
{
    private function enabled(): bool
    {
        return filled(config('services.yandex.client_id'))
            && filled(config('services.yandex.client_secret'));
    }

    public function redirect(): RedirectResponse
    {
        abort_unless($this->enabled(), 503, 'Вход через Яндекс пока не настроен.');

        return Socialite::driver('yandex')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->enabled(), 503);

        if ($request->has('error')) {
            return redirect()->route('login')->withErrors(['email' => 'Авторизация через Яндекс отменена.']);
        }

        try {
            $profile = Socialite::driver('yandex')->user();
        } catch (Throwable $error) {
            Log::warning('Yandex OAuth callback failed', ['exception' => $error::class]);

            return redirect()->route('login')->withErrors(['email' => 'Не удалось подтвердить вход через Яндекс.']);
        }

        $identifier = (string) $profile->getId();
        $email = Str::lower((string) $profile->getEmail());
        if ($identifier === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('login')->withErrors(['email' => 'Яндекс не передал адрес электронной почты.']);
        }

        $existing = User::where('yandex_id', $identifier)->first();
        if ($existing) {
            return $this->finishLogin($request, $existing);
        }

        // Never auto-link OAuth identity to an existing account by e-mail.
        if (User::where('email', $email)->exists()) {
            return redirect()->route('login')->withErrors(['email' => 'Аккаунт с таким e-mail уже существует. Для него используйте вход по паролю.']);
        }

        $request->session()->put('pending_yandex', [
            'id' => $identifier,
            'email' => $email,
            'name' => mb_substr((string) ($profile->getName() ?: explode('@', $email)[0]), 0, 100),
        ]);

        return redirect()->route('auth.yandex.consent');
    }

    public function consentForm(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('pending_yandex');
        if (! is_array($pending) || empty($pending['id']) || empty($pending['email'])) {
            return redirect()->route('login');
        }

        return view('auth.yandex-consent', ['email' => $pending['email']]);
    }

    public function consentComplete(Request $request): RedirectResponse
    {
        $request->validate([
            'personal_data_consent' => ['required', 'accepted'],
            'terms_acceptance' => ['required', 'accepted'],
        ]);
        $pending = $request->session()->get('pending_yandex');
        abort_unless(is_array($pending) && ! empty($pending['id']) && ! empty($pending['email']), 403);

        $user = DB::transaction(function () use ($pending): ?User {
            $existing = User::where('yandex_id', $pending['id'])->first();
            if ($existing) {
                return $existing;
            }
            if (User::where('email', $pending['email'])->exists()) {
                return null;
            }

            $user = User::create([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'password' => Str::random(64),
                'yandex_id' => $pending['id'],
            ]);

            DB::table('personal_data_consents')->insert([
                'user_id' => $user->id,
                'purpose' => 'account_and_quiz',
                'document_version' => '2026-11-18',
                'granted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $user;
        });

        if ($user === null) {
            $request->session()->forget('pending_yandex');

            return redirect()->route('login')->withErrors(['email' => 'Аккаунт с таким e-mail уже существует.']);
        }

        $request->session()->forget('pending_yandex');

        return $this->finishLogin($request, $user);
    }

    private function finishLogin(Request $request, User $user): RedirectResponse
    {
        Auth::login($user, true);
        $request->session()->regenerate();
        $request->session()->forget('pending_yandex');

        DemoSubscriptionLifecycle::claimAttempts($request, $user);

        return redirect()->route($user->is_admin ? 'admin.home' : 'account.home');
    }
}
