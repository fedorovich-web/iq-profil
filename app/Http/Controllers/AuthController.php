<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DemoSubscriptionLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
            'personal_data_consent' => ['required', 'accepted'],
            'terms_acceptance' => ['required', 'accepted'],
        ]);
        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => explode('@', $data['email'])[0],
                'email' => Str::lower($data['email']),
                'password' => $data['password'],
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
        Auth::login($user);
        $request->session()->regenerate();
        $this->claimAttempts($request, $user);

        return redirect()->route('account.home');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::attempt(['email' => Str::lower($data['email']), 'password' => $data['password']], (bool) ($data['remember'] ?? false))) {
            throw ValidationException::withMessages(['email' => 'Неверная почта или пароль.']);
        }
        $request->session()->regenerate();
        $this->claimAttempts($request, Auth::user());

        return redirect()->intended(Auth::user()->is_admin ? route('admin.home') : route('account.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }

    public function profile(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ]);
        $request->user()->update(['name' => $data['name'], 'email' => Str::lower($data['email'])]);

        return back()->with('status', 'Личные данные обновлены');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);
        $request->user()->update(['password' => $data['password']]);
        $request->session()->regenerate();

        return back()->with('status', 'Пароль изменён');
    }

    public function resetRequestForm(): View
    {
        return view('auth.forgot');
    }

    public function resetRequest(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Если такой адрес зарегистрирован, письмо для восстановления отправлено.');
    }

    public function resetForm(string $token, Request $request): View
    {
        return view('auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()],
        ]);
        $status = Password::reset($data, function (User $user, string $password): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Ссылка восстановления недействительна или устарела.']);
        }

        return redirect()->route('login')->with('status', 'Пароль обновлён. Войдите.');
    }

    private function claimAttempts(Request $request, User $user): void
    {
        DemoSubscriptionLifecycle::claimAttempts($request, $user);
    }
}
