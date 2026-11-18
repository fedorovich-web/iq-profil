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
@include('partials.site-header')
<main id="main">@yield('content')</main>
</div>
@include('partials.site-footer')
</body>
</html>