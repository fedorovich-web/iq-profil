@extends('layouts.auth')
@section('title', 'Подтверждение данных — IQ.Профиль')
@section('content')
<h1>Подтверждение регистрации</h1>
<p class="auth-description">Вы авторизовались через Яндекс как {{ $email }}. Для создания аккаунта IQ.Профиль ознакомьтесь с условиями и подтвердите согласие отдельно.</p>
<form action="{{ route('auth.yandex.consent.perform') }}" method="POST" class="auth-form">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
<label class="auth-consent">
<input type="checkbox" name="personal_data_consent" value="1" required>
<span>Даю отдельное <a href="{{ route('legal.consent') }}" target="_blank" rel="noopener">согласие на обработку персональных данных</a>.</span></label>
@error('personal_data_consent')<p role="alert" class="form-error">{{ $message }}</p>@enderror
<label class="auth-consent">
<input type="checkbox" name="terms_acceptance" value="1" required>
<span>Ознакомлен с <a href="{{ route('legal.offer') }}" target="_blank" rel="noopener">демонстрационной офертой</a>.</span></label>
@error('terms_acceptance')<p role="alert" class="form-error">{{ $message }}</p>@enderror
<button class="btn btn-primary btn-block" type="submit">Создать аккаунт</button>
</form>
<p class="auth-bottom"><a href="{{ route('login') }}">Вернуться ко входу</a></p>
@endsection