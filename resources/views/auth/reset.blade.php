@extends('layouts.auth')
@section('title','Новый пароль — IQ.Профиль')
@section('content')
<h1>Новый пароль</h1>
<p class="auth-description">Придумайте новый пароль: не менее 10 символов, буквы и цифры.</p>
<form method="POST" action="{{ route('password.update') }}" class="auth-form">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
<input type="hidden" name="token" value="{{ $token }}">
<label class="auth-field"><span>E-mail</span><input type="email" name="email" value="{{ old('email',$email) }}" required></label>
<label class="auth-field"><span>Новый пароль</span><input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
<label class="auth-field"><span>Повторите пароль</span><input type="password" name="password_confirmation" minlength="10" required autocomplete="new-password"></label>
@foreach($errors->all() as $error)<p class="form-error" role="alert">{{ $error }}</p>@endforeach
<button type="submit" class="btn btn-primary btn-block">Сохранить новый пароль</button>
</form>
@endsection