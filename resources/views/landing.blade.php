@extends('layouts.app')
@section('title', 'Графический IQ-тест онлайн — IQ.Профиль')
@section('description', 'Пройдите 25 графических заданий на закономерности, сохраните прогресс и получите подробный индивидуальный разбор.')
@section('page-class', 'landing-page')
@section('landing-nav', 'yes')
@section('content')
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-copy">
    <span class="eyebrow">Тесты на мышление и логику</span>
    <h1 id="hero-title">Узнайте больше<br><span>о себе</span></h1>
    <p>Интерактивные задания на логику, абстрактное мышление и поиск закономерностей.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="#tests">Выбрать тест <span aria-hidden="true">→</span></a>
      <form action="{{ route('attempt.start') }}" method="POST"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="btn btn-secondary" type="submit"><span class="play-symbol" aria-hidden="true">▷</span> Пройти IQ-тест</button></form>
    </div>
  </div>
  <div class="hero-visual" aria-hidden="true">
    @if(file_exists(public_path('assets/iq_profile_hero_logic_matrix_desktop.avif')))
    <picture class="hero-picture">
      <source media="(max-width: 690px)" srcset="{{ asset('assets/iq_profile_hero_logic_matrix_mobile.avif') }}" type="image/avif">
      <img src="{{ asset('assets/iq_profile_hero_logic_matrix_desktop.avif') }}" width="700" height="600" alt="" fetchpriority="high" decoding="async">
    </picture>
    @else
    <div class="hero-shape hero-shape-one"></div><div class="hero-shape hero-shape-two"></div>
    <div class="hero-matrix">
      <div class="hero-symbol solid">▲</div><div class="hero-symbol solid">■</div><div class="hero-symbol solid">●</div>
      <div class="hero-symbol outline">△</div><div class="hero-symbol outline">□</div><div class="hero-symbol outline">○</div>
      <div class="hero-symbol solid">▲</div><div class="hero-symbol solid">■</div><div class="hero-symbol unknown">?</div>
    </div>
    <div class="hero-ring"></div>
    @endif
  </div>
</section>
<section class="section" id="benefits" aria-labelledby="benefits-title">
  <h2 id="benefits-title">Наши <span>преимущества</span></h2>
  <p class="section-subtitle">Простое прохождение и понятные результаты.</p>
  <div class="feature-grid">
    <article class="feature-card">@if(file_exists(public_path('assets/benefit-science.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-science.avif') }}" alt="" width="120" height="120" loading="lazy">@else<div class="feature-icon" aria-hidden="true">◇</div>@endif<h3>Прозрачный подход</h3><p>Оригинальные графические задания с однозначными проверяемыми ответами.</p></article>
    <article class="feature-card">@if(file_exists(public_path('assets/benefit-format.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-format.avif') }}" alt="" width="120" height="120" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▤</div>@endif<h3>Удобный формат</h3><p>Проходите без ограничения времени с любого устройства.</p></article>
    <article class="feature-card">@if(file_exists(public_path('assets/benefit-results.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-results.avif') }}" alt="" width="120" height="120" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▥</div>@endif<h3>Понятные результаты</h3><p>Персональная статистика и объяснение каждой задачи.</p></article>
  </div>
</section>
<section class="section" id="tests" aria-labelledby="tests-title">
  <h2 id="tests-title">Доступные <span>тесты</span></h2>
  <p class="section-subtitle">Начните с заданий на абстрактное и логическое мышление.</p>
  <div class="test-grid">
    <article class="test-card">@if(file_exists(public_path('assets/test-iq.avif')))<img class="test-asset" src="{{ asset('assets/test-iq.avif') }}" alt="" width="120" height="120" loading="lazy">@else<div class="test-graphic" aria-hidden="true"><span>■</span><span>◢</span><span>●</span></div>@endif<h3>Графический IQ-тест</h3><p>25 заданий на поиск закономерностей в матрицах из фигур.</p><form action="{{ route('attempt.start') }}" method="POST"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="card-link">Пройти тест <span aria-hidden="true">→</span></button></form></article>
    <article class="test-card">@if(file_exists(public_path('assets/test-logic.avif')))<img class="test-asset" src="{{ asset('assets/test-logic.avif') }}" alt="" width="120" height="120" loading="lazy">@else<div class="test-graphic" aria-hidden="true">✥</div>@endif<h3>Логическое мышление</h3><p>Последовательности, отношения и изменения визуальных элементов.</p><form action="{{ route('attempt.start') }}" method="POST"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="card-link">Пройти тест <span aria-hidden="true">→</span></button></form></article>
    <article class="test-card">@if(file_exists(public_path('assets/test-memory.avif')))<img class="test-asset" src="{{ asset('assets/test-memory.avif') }}" alt="" width="120" height="120" loading="lazy">@else<div class="test-graphic" aria-hidden="true">▱</div>@endif<h3>Внимание к деталям</h3><p>Определите, какая фигура продолжает визуальную последовательность.</p><form action="{{ route('attempt.start') }}" method="POST"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="card-link">Пройти тест <span aria-hidden="true">→</span></button></form></article>
  </div>
</section>
<section class="section" id="steps" aria-labelledby="steps-title">
  <h2 id="steps-title">Как это <span>работает</span></h2>
  <p class="section-subtitle">От выбора теста до персонального результата — три шага.</p>
  <div class="steps-grid">
    <article class="step">@if(file_exists(public_path('assets/step-select.avif')))<img class="step-asset" src="{{ asset('assets/step-select.avif') }}" alt="" width="110" height="110" loading="lazy">@else<div class="step-art" aria-hidden="true">☷</div>@endif<div class="step-label"><b>01</b><h3>Выберите тест</h3></div><p>Откройте графические задания.</p></article>
    <article class="step">@if(file_exists(public_path('assets/step-complete.avif')))<img class="step-asset" src="{{ asset('assets/step-complete.avif') }}" alt="" width="110" height="110" loading="lazy">@else<div class="step-art" aria-hidden="true">☑</div>@endif<div class="step-label"><b>02</b><h3>Пройдите задания</h3></div><p>Отвечайте в удобном темпе.</p></article>
    <article class="step">@if(file_exists(public_path('assets/step-results.avif')))<img class="step-asset" src="{{ asset('assets/step-results.avif') }}" alt="" width="110" height="110" loading="lazy">@else<div class="step-art" aria-hidden="true">▥</div>@endif<div class="step-label"><b>03</b><h3>Получите результат</h3></div><p>Откройте индивидуальный разбор решений.</p></article>
  </div>
</section>
<section class="section faq-section" id="faq" aria-labelledby="faq-title">
  <h2 id="faq-title"><span>FAQ</span> — частые вопросы</h2>
  <p class="section-subtitle">Всё важное о прохождении и результате.</p>
  <div class="faq-list">
    <details><summary>Какие тесты доступны на платформе?</summary><p>Сейчас доступна одна батарея из 25 оригинальных графических заданий. Карточки на странице описывают типы задач внутри этого теста.</p></details>
    <details><summary>Сколько времени занимает прохождение?</summary><p>Ограничения по времени нет: можете отвечать в собственном темпе.</p></details>
    <details><summary>Нужна ли регистрация?</summary><p>Нет. Прохождение привязано к текущей сессии браузера.</p></details>
    <details><summary>Как я получу результат?</summary><p>После завершения откроется предложение доступа, затем демонстрационная оплата без списания денег и индивидуальный отчёт.</p></details>
    <details><summary>Можно ли пройти тест с телефона?</summary><p>Да, интерфейс адаптирован для небольших экранов.</p></details>
    <details><summary>Определяет ли тест официальный IQ?</summary><p>Нет. Задачи не проходили психометрическую нормировку, поэтому результат показывается как число верных ответов и процент, а не как стандартизированный IQ-балл.</p></details>
  </div>
</section>
<section class="bottom-cta"><div><h2>Пройдите <span>IQ-тест</span></h2><p>25 графических задач с подробным разбором.</p></div><form action="{{ route('attempt.start') }}" method="POST"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="btn btn-primary">▷ Пройти IQ-тест</button></form></section>
@endsection
@section('footer')
<footer class="footer">
  <div><strong class="footer-brand">IQ.Профиль</strong><p>Интерактивные задания на мышление.</p></div>
  <div><strong>Тесты</strong><a href="#tests">Графический тест</a><a href="#steps">Как это работает</a></div>
  <div><strong>О сервисе</strong><a href="#benefits">Преимущества</a><a href="#faq">FAQ</a></div>
  <div><strong>Информация</strong><p>Учебный сервис. Оплата только демонстрационная.</p></div>
</footer>
@endsection