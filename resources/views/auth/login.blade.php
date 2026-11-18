@extends('layouts.auth')
@section('title','Вход в аккаунт — IQ.Профиль')
@section('content')
<h1>Вход в аккаунт</h1>
<p class="auth-description">Войдите в IQ.Профиль, чтобы вернуться к своим результатам и управлять учётной записью.</p>
<form method="POST" action="{{ route('login.perform') }}" class="auth-form">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
<label class="auth-field"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" placeholder="E-mail" autocomplete="email" required autofocus></label>
@error('email')<div role="alert" class="form-error">{{ $message }}</div>@enderror
<label class="auth-field"><span>Пароль</span><input type="password" name="password" placeholder="Пароль" autocomplete="current-password" required></label>
@error('password')<div role="alert" class="form-error">{{ $message }}</div>@enderror
<div class="auth-options"><label><input name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}> Запомнить меня</label><a href="{{ route('password.request') }}">Забыли пароль?</a></div>
<button type="submit" class="btn btn-primary btn-block">Войти <span aria-hidden="true">→</span></button>
</form>
<div class="auth-divider"><span>или</span></div>
@if(config('services.yandex.client_id') && config('services.yandex.client_secret'))
<a class="btn btn-outline btn-block auth-yandex" href="{{ route('auth.yandex.redirect') }}"><b>Я</b> Войти через Яндекс</a>
@else
<button type="button" class="btn btn-outline btn-block auth-yandex" disabled title="Для подключения требуется OAuth-приложение Яндекса"><b>Я</b> Войти через Яндекс</button>
<p class="auth-config-note">Вход через Яндекс будет доступен после подключения OAuth-приложения.</p>
@endif
<p class="auth-bottom">Нет аккаунта? <a href="{{ route('register') }}">Зарегистрироваться</a></p>
@endsection