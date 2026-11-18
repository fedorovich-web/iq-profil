@extends('layouts.auth')
@section('title','Регистрация — IQ.Профиль')
@section('content')
<h1>Регистрация</h1>
<p class="auth-description">Сохраняйте историю прохождений, открывайте доступные отчёты и управляйте профилем. Сам тест можно начать без регистрации.</p>
<form method="POST" action="{{ route('register.perform') }}" class="auth-form">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
<label class="auth-field"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" placeholder="E-mail" autocomplete="email" required autofocus></label>
@error('email')<div role="alert" class="form-error">{{ $message }}</div>@enderror
<label class="auth-field"><span>Пароль (от 10 символов, буквы и цифры)</span><input type="password" name="password" placeholder="Пароль" autocomplete="new-password" minlength="10" required></label>
<label class="auth-field"><span>Повторите пароль</span><input type="password" name="password_confirmation" placeholder="Повторите пароль" autocomplete="new-password" minlength="10" required></label>
@error('password')<div role="alert" class="form-error">{{ $message }}</div>@enderror
<label class="auth-consent"><input type="checkbox" name="personal_data_consent" value="1" required {{ old('personal_data_consent') ? 'checked' : '' }}> <span>Даю отдельное <a href="{{ route('legal.consent') }}" target="_blank" rel="noopener">согласие на обработку персональных данных</a>.</span></label>
@error('personal_data_consent')<p class="form-error" role="alert">{{ $message }}</p>@enderror
<label class="auth-consent"><input type="checkbox" name="terms_acceptance" value="1" required {{ old('terms_acceptance') ? 'checked' : '' }}> <span>Ознакомлен с <a href="{{ route('legal.offer') }}" target="_blank" rel="noopener">условиями использования и демо-офертой</a>.</span></label>
@error('terms_acceptance')<p class="form-error" role="alert">{{ $message }}</p>@enderror
<button type="submit" class="btn btn-primary btn-block">Зарегистрироваться <span aria-hidden="true">→</span></button>
</form>
<div class="auth-divider"><span>или</span></div>
@if(config('services.yandex.client_id') && config('services.yandex.client_secret'))
<a class="btn btn-outline btn-block auth-yandex" href="{{ route('auth.yandex.redirect') }}"><b>Я</b> Зарегистрироваться через Яндекс</a>
@else
<button type="button" class="btn btn-outline btn-block auth-yandex" disabled title="Требуется конфигурация OAuth"><b>Я</b> Зарегистрироваться через Яндекс</button>
<p class="auth-config-note">Для входа через Яндекс требуется настройка OAuth-приложения.</p>
@endif
<p class="auth-bottom">Уже есть аккаунт? <a href="{{ route('login') }}">Войти</a></p>
@endsection