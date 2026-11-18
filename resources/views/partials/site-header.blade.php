<header class="topbar">
  @if(request()->routeIs('landing'))
    <span class="brand" aria-label="IQ.Профиль — текущая страница">@include('partials.brand')</span>
  @else
    <a href="{{ route('landing') }}" class="brand" aria-label="IQ.Профиль — главная">@include('partials.brand')</a>
  @endif
  <nav class="navigation" aria-label="Разделы сайта">
    <a href="{{ route('landing') }}#tests">Тесты</a>
    <a href="{{ route('landing') }}#benefits">Преимущества</a>
    <a href="{{ route('landing') }}#steps">Как это работает</a>
    <a href="{{ route('landing') }}#faq">FAQ</a>
  </nav>
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
<div class="mobile-menu-overlay" data-menu-overlay aria-hidden="true"></div>
<div id="mobile-site-menu" class="mobile-site-menu" role="dialog" aria-modal="true" aria-label="Мобильное меню" inert>
  <div class="mobile-menu-head">
    <strong>Меню</strong>
    <button type="button" class="mobile-menu-close" aria-label="Закрыть меню">
      <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 5l14 14M19 5 5 19"/></svg>
    </button>
  </div>
  <nav class="mobile-menu-links" aria-label="Разделы сайта">
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
</div>