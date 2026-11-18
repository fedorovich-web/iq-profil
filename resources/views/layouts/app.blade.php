<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>@yield('title', 'IQ.Профиль — тест на IQ онлайн')</title>
<meta name="description" content="@yield('description', 'Пройдите IQ-тест онлайн: 25 визуальных заданий на логику и закономерности.')">
@hasSection('canonical-url')<link rel="canonical" href="@yield('canonical-url')">@endif
@hasSection('landing-nav')
<link rel="canonical" href="{{ route('landing') }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:title" content="IQ.Профиль — тест на IQ онлайн">
<meta property="og:description" content="25 визуальных заданий на логику и поиск закономерностей.">
<meta property="og:url" content="{{ route('landing') }}">
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'WebSite','name'=>'IQ.Профиль','url'=>route('landing')], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@hasSection('private')<meta name="robots" content="noindex,nofollow,noarchive">@endif
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" sizes="any" href="{{ asset('favicon.ico') }}">
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="@yield('page-class','page-default')">
<a class="skip-link" href="#main">Перейти к содержимому</a>
<div class="site-shell">
<header class="topbar">
  @hasSection('landing-nav')
  <span class="brand" aria-label="IQ.Профиль — текущая страница">@include('partials.brand')</span>
  @else
  <a href="{{ route('landing') }}" class="brand" aria-label="IQ.Профиль — главная">@include('partials.brand')</a>
  @endif
  @hasSection('landing-nav')
  <nav class="navigation" aria-label="Разделы сайта">
    <a href="#tests">Тесты</a><a href="#benefits">Преимущества</a>
    <a href="#steps">Как это работает</a><a href="#faq">FAQ</a>
  </nav>
  @else
  <a href="{{ route('landing') }}" class="return-home">← На главную</a>
  @endif
  <div class="header-actions">
    @auth
    <a class="nav-login" href="{{ auth()->user()->is_admin ? route('admin.home') : route('account.home') }}">Кабинет</a>
    @else
    <a class="nav-login" href="{{ route('login') }}">Войти</a>
    @endauth
    <button type="button" class="mobile-menu-toggle" aria-label="Открыть меню" aria-expanded="false" aria-controls="mobile-site-menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
<nav id="mobile-site-menu" class="mobile-site-menu" aria-label="Мобильное меню" inert>
  <a href="{{ route('landing') }}#tests">Тесты</a>
  <a href="{{ route('landing') }}#benefits">Преимущества</a>
  <a href="{{ route('landing') }}#steps">Как это работает</a>
  <a href="{{ route('landing') }}#faq">FAQ</a>
  @auth
  <a href="{{ auth()->user()->is_admin ? route('admin.home') : route('account.home') }}">Личный кабинет</a>
  @else
  <a href="{{ route('register') }}">Регистрация</a>
  @endauth
</nav>
<main id="main">@yield('content')</main>
</div>
@yield('footer')
</body>
</html>