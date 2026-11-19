<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>@yield('title', 'Личный кабинет — IQ.Профиль')</title>
<link rel="preload" href="{{ asset('fonts/onest/onest-cyrillic-wght-v1.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/onest/onest-latin-wght-v1.woff2') }}" as="font" type="font/woff2" crossorigin>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="portal-page">
<a class="skip-link" href="#portal-main">Перейти к содержимому</a>
<header class="portal-header">
 <a href="{{ route('landing') }}" class="portal-logo" aria-label="IQ.Профиль — главная">@include('partials.brand')</a>
 <span class="portal-header-divider"></span>
 <strong class="portal-header-title">@yield('portal-heading', 'Личный кабинет')</strong>
 <span class="portal-header-spacer"></span>
 <span class="portal-user"><span class="portal-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span><span>{{ auth()->user()->name }}</span></span>
 <form action="{{ route('logout') }}" method="post"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="portal-logout" aria-label="Выйти из аккаунта" title="Выйти из аккаунта"><img src="{{ asset('assets/icons/client/logout.avif') }}" width="20" height="20" alt="" aria-hidden="true"><span>Выйти</span></button></form>
</header>
<div class="portal-grid">
 <aside class="portal-sidebar">
  @yield('sidebar')
 </aside>
 <main id="portal-main" class="portal-main">
 @if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
 @yield('content')
 </main>
</div>
@include('partials.legal-footer')
@yield('mobile-nav')
</body>
</html>