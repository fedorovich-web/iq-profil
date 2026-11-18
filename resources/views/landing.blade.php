@extends('layouts.app')
@section('title', 'IQ-тест онлайн — 25 вопросов на логику и закономерности | IQ.Профиль')
@section('description', 'Пройдите IQ-тест онлайн: 25 визуальных заданий, удобное прохождение без ограничения времени и персональный отчёт после демонстрационной оплаты.')
@section('page-class','landing-page')
@section('landing-nav','yes')
@section('content')
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-copy">
    <span class="eyebrow">Тесты на мышление и логику</span>
    <h1 id="hero-title">Узнайте больше<br><span>о себе</span></h1>
    <p>Интерактивные задания на логику, абстрактное мышление и поиск закономерностей.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="#tests">Выбрать тест <span aria-hidden="true">→</span></a>
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="btn btn-secondary" type="submit"><span class="play-symbol" aria-hidden="true"><svg viewBox="0 0 26 26" focusable="false"><circle cx="13" cy="13" r="10.5"></circle><path d="m10.5 8.4 7.3 4.6-7.3 4.6V8.4Z"></path></svg></span> Пройти IQ-тест</button>
      </form>
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
  <p class="section-subtitle">Удобный формат и понятные результаты.</p>
  <div class="feature-grid">
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-science.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-science.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">◇</div>@endif
      <h3>Прозрачный подход</h3><p>Оригинальные графические задания с проверяемыми решениями.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-format.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-format.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▤</div>@endif
      <h3>Удобный формат</h3><p>Отвечайте в своём темпе с компьютера или телефона.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-results.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-results.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▥</div>@endif
      <h3>Персональный результат</h3><p>Отчёт по вашей попытке с фактическими показателями.</p>
    </article>
  </div>
</section>
<section class="section" id="tests" aria-labelledby="tests-title">
  <h2 id="tests-title">Доступные <span>тесты</span></h2>
  <p class="section-subtitle">Сейчас доступен IQ-тест. Другие направления находятся в разработке.</p>
  <div class="test-grid">
    <article class="test-card">
      @if(file_exists(public_path('assets/test-iq.avif')))<img class="test-asset" src="{{ asset('assets/test-iq.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true"><span>■</span><span>◢</span><span>●</span></div>@endif
      <h3>IQ-тест</h3><p>25 визуальных заданий на поиск закономерностей и логическое мышление.</p>
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="card-link" type="submit">Пройти IQ-тест <span aria-hidden="true">→</span></button>
      </form>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-logic.avif')))<img class="test-asset" src="{{ asset('assets/test-logic.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">✥</div>@endif
      <h3>Логическое мышление</h3><p>Отдельный тест пока не доступен. Мы ещё работаем над его заданиями.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-memory.avif')))<img class="test-asset" src="{{ asset('assets/test-memory.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">▱</div>@endif
      <h3>Внимание к деталям</h3><p>Отдельный тест пока не доступен. Запуск будет объявлен позднее.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
  </div>
</section>
<section class="section" id="steps" aria-labelledby="steps-title">
  <h2 id="steps-title">Как это <span>работает</span></h2>
  <p class="section-subtitle">От первого вопроса до персонального результата — три простых шага.</p>
  <div class="steps-grid">
    <article class="step">
      @if(file_exists(public_path('assets/step-select.avif')))<img class="step-asset" src="{{ asset('assets/step-select.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☷</div>@endif
      <div class="step-label"><b>01</b><h3>Выберите тест</h3></div><p>Начните с доступного IQ-теста.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-complete.avif')))<img class="step-asset" src="{{ asset('assets/step-complete.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☑</div>@endif
      <div class="step-label"><b>02</b><h3>Ответьте на вопросы</h3></div><p>Проходите задания без ограничения времени.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-results.avif')))<img class="step-asset" src="{{ asset('assets/step-results.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">▥</div>@endif
      <div class="step-label"><b>03</b><h3>Получите результат</h3></div><p>Откройте персональный отчёт после демо-оплаты.</p>
    </article>
  </div>
</section>
<section class="section faq-section" id="faq" aria-labelledby="faq-title">
  <h2 id="faq-title"><span>FAQ</span> — частые вопросы</h2>
  <p class="section-subtitle">Предварительные ответы: вопросы будут окончательно утверждены перед разметкой FAQPage.</p>
  <div class="faq-list">
    <details><summary>Какие тесты сейчас доступны?</summary><p>Пока доступен один IQ-тест из 25 визуальных заданий. Тесты по логике и вниманию находятся в разработке.</p></details>
    <details><summary>Есть ли ограничение по времени?</summary><p>Нет. Отвечайте в удобном темпе — секундомер показывает продолжительность прохождения.</p></details>
    <details><summary>Нужна ли регистрация для теста?</summary><p>Нет. Прохождение доступно без создания аккаунта. Для сохранения истории в кабинете можно зарегистрироваться.</p></details>
    <details><summary>Как получить персональный результат?</summary><p>После завершения вы увидите предложение доступа. В демоверсии подтверждение оплаты не списывает средства и открывает отчёт.</p></details>
    <details><summary>Можно ли пройти тест с телефона?</summary><p>Да. Матрица вопроса и варианты ответов адаптированы к мобильным экранам.</p></details>
    <details><summary>Показывает ли этот тест официальный IQ?</summary><p>Нет. Это оригинальные тренировочные задачи без статистической нормировки. Отчёт отражает результат выполнения, а не стандартизированный IQ-балл.</p></details>
  </div>
</section>
<section class="bottom-cta">
  <div><h2>Пройдите <span>IQ-тест</span></h2><p>25 визуальных заданий в удобном формате.</p></div>
  <form action="{{ route('attempt.start') }}" method="POST">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <button class="btn btn-primary" type="submit">Пройти IQ-тест</button>
  </form>
</section>
@endsection

@section('footer')
<footer class="footer" aria-label="Информация о сервисе">
  <div class="footer-about">
    <span class="footer-brand">@include('partials.brand')</span>
    <p>Онлайн-задания на логику и поиск закономерностей.</p>
  </div>
  <details class="footer-group">
    <summary>Тесты</summary>
    <div class="footer-links"><a href="#tests">IQ-тест</a><span>Логическое мышление — скоро</span><span>Внимание к деталям — скоро</span></div>
  </details>
  <details class="footer-group">
    <summary>О сервисе</summary>
    <div class="footer-links"><a href="#benefits">Преимущества</a><a href="#steps">Как это работает</a><a href="#faq">FAQ</a></div>
  </details>
  <details class="footer-group">
    <summary>Информация</summary>
    <div class="footer-links">
      <a href="{{ route('legal.privacy') }}">Политика конфиденциальности</a>
      <a href="{{ route('legal.offer') }}">Публичная оферта (демо)</a>
      <a href="{{ route('legal.consent') }}">Согласие на обработку персональных данных</a>
      <a href="{{ route('subscription.cancel') }}">Отменить подписку</a>
    </div>
  </details>
  <p class="footer-copyright">© {{ date('Y') }} IQ.Профиль. Демо-оплата без реальных списаний.</p>
</footer>
@endsection