<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>@yield('title', 'IQ.Профиль — графические задания на мышление')</title>
<meta name="description" content="@yield('description', 'Пройдите интерактивные задания и получите персональный разбор решений.')">
@hasSection('canonical-url')
<link rel="canonical" href="@yield('canonical-url')">
@endif
@hasSection('landing-nav')
<link rel="canonical" href="{{ route('landing') }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:title" content="IQ.Профиль — графический тест онлайн">
<meta property="og:description" content="25 интерактивных задач на закономерности с персональным разбором.">
<meta property="og:url" content="{{ route('landing') }}">
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => 'IQ.Профиль', 'url' => route('landing')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@hasSection('private')<meta name="robots" content="noindex,nofollow,noarchive">@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('page-class', 'page-default')">
<a class="skip-link" href="#main">Перейти к содержимому</a>
<div class="site-shell">
<header class="topbar">
<a href="{{ route('landing') }}" class="brand" aria-label="IQ.Профиль — главная">@include('partials.brand')</a>
@hasSection('landing-nav')
<nav class="navigation" aria-label="Разделы сайта"><a href="#tests">Тесты</a><a href="#benefits">Преимущества</a><a href="#steps">Как это работает</a><a href="#faq">FAQ</a></nav>
@auth <a class="nav-login" href="{{ auth()->user()->is_admin ? route('admin.home') : route('account.home') }}">Личный кабинет</a> @else <a class="nav-login" href="{{ route('login') }}">Войти</a> @endauth
@else
<a href="{{ route('landing') }}" class="return-home"><span aria-hidden="true">←</span> На главную</a>
@endif
</header>
<main id="main">@yield('content')</main>
</div>
@yield('footer')
@include('partials.legal-footer')
</body>
</html>