<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>@yield('title', 'Аккаунт — IQ.Профиль')</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="auth-page">
<main class="auth-main">
  <a href="{{ route('landing') }}" class="auth-brand" aria-label="IQ.Профиль — на главную">@include('partials.brand')</a>
  <div class="auth-decor" aria-hidden="true"></div>
  @if(session('status'))<p class="form-success" role="status">{{ session('status') }}</p>@endif
  @yield('content')
  <div class="auth-legal-links">
    <a href="{{ route('legal.privacy') }}">Политика конфиденциальности</a>
    <a href="{{ route('legal.offer') }}">Оферта</a>
    <a href="{{ route('legal.consent') }}">Согласие на обработку данных</a>
    <a href="{{ route('subscription.cancel') }}">Отмена подписки</a>
  </div>
</main>
</body>
</html>