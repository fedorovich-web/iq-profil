@extends('layouts.auth')
@section('title','Восстановление пароля — IQ.Профиль')
@section('content')
<h1>Забыли пароль?</h1>
<p class="auth-description">Укажите почту аккаунта, и мы отправим ссылку для восстановления. Доставка писем требует настройки SMTP.</p>
<form method="POST" action="{{ route('password.email') }}" class="auth-form">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
<label class="auth-field"><span>E-mail</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></label>
@error('email')<div class="form-error" role="alert">{{ $message }}</div>@enderror
<button type="submit" class="btn btn-primary btn-block">Отправить ссылку</button>
</form>
<p class="auth-bottom"><a href="{{ route('login') }}">← Вернуться ко входу</a></p>
@endsection