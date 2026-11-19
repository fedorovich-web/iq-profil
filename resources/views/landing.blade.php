@extends('layouts.app')
@section('title', 'Тест на IQ онлайн без регистрации — задачи на логику')
@section('description', 'Пройти тест на IQ онлайн без регистрации: графические задания на логику, матрицы и поиск закономерностей. Результаты и персональный отчёт после прохождения.')
@section('page-class','landing-page')
@section('landing-nav','yes')
@section('content')
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-copy">
    <h1 id="hero-title">Тест на IQ онлайн:<br><span>задачи на логику</span></h1>
    <p>Пройдите IQ-тест без регистрации: находите закономерности в графических матрицах и выбирайте недостающие фигуры. После прохождения доступен персональный отчёт.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="#tests">Посмотреть тесты <span aria-hidden="true">→</span></a>
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="btn btn-secondary" type="submit"><span class="play-symbol" aria-hidden="true"><svg viewBox="0 0 26 26" focusable="false"><circle cx="13" cy="13" r="10.5"></circle><path d="m10.5 8.4 7.3 4.6-7.3 4.6V8.4Z"></path></svg></span> Начать IQ-тест</button>
      </form>
    </div>
    <p class="hero-disclosure">Начать можно бесплатно и без регистрации. Отчёт в демоверсии открывается без карты и реальных списаний.</p>
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
  <h2 id="benefits-title">Преимущества <span>онлайн-теста на IQ</span></h2>
  <p class="section-subtitle">Графические задания на логику, прохождение без ограничения времени и персональный отчёт с результатами тестирования.</p>
  <div class="feature-grid">
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-science.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-science.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">◇</div>@endif
      <h3>Задания на логическое мышление</h3><p>Находите закономерности в графических матрицах, сравнивайте фигуры и выбирайте недостающий элемент.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-format.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-format.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▤</div>@endif
      <h3>Без ограничения времени</h3><p>Решайте задания с телефона или компьютера в удобном темпе. До завершения теста можно изменить ответ.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-results.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-results.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▥</div>@endif
      <h3>Персональный отчёт с результатами</h3><p>Узнайте количество верных ответов, процент выполнения, время прохождения и результаты по направлениям заданий.</p>
    </article>
  </div>
</section>
<section class="section" id="tests" aria-labelledby="tests-title">
  <h2 id="tests-title">Онлайн-тесты <span>на интеллект и логику</span></h2>
  <p class="section-subtitle">Выберите доступный тест на логическое мышление. Другие виды заданий появятся после разработки.</p>
  <div class="test-grid">
    <article class="test-card">
      @if(file_exists(public_path('assets/test-iq.avif')))<img class="test-asset" src="{{ asset('assets/test-iq.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true"><span>■</span><span>◢</span><span>●</span></div>@endif
      <h3>Тест на IQ — логические матрицы</h3><p>Пройдите невербальные задания: найдите недостающие фигуры и продолжите графические закономерности. Регистрация не нужна.</p>
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="card-link" type="submit">Пройти IQ-тест <span aria-hidden="true">→</span></button>
      </form>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-logic.avif')))<img class="test-asset" src="{{ asset('assets/test-logic.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">✥</div>@endif
      <h3>Логическое мышление</h3><p>Отдельное направление для задач на рассуждение и логические связи. Пока в разработке.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-memory.avif')))<img class="test-asset" src="{{ asset('assets/test-memory.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">▱</div>@endif
      <h3>Внимание к деталям</h3><p>Будущие задания на наблюдательность и точность восприятия. Пока в разработке.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
  </div>
</section>
<section class="section" id="steps" aria-labelledby="steps-title">
  <h2 id="steps-title">Как пройти <span>тест на IQ онлайн</span></h2>
  <p class="section-subtitle">Начните тест без регистрации, выберите ответы на графические задачи и откройте отчёт о прохождении.</p>
  <div class="steps-grid">
    <article class="step">
      @if(file_exists(public_path('assets/step-select.avif')))<img class="step-asset" src="{{ asset('assets/step-select.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☷</div>@endif
      <div class="step-label"><b>01</b><h3>Начните IQ-тест</h3></div><p>Откройте тест на логику в браузере. Для начала не нужен аккаунт.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-complete.avif')))<img class="step-asset" src="{{ asset('assets/step-complete.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☑</div>@endif
      <div class="step-label"><b>02</b><h3>Найдите закономерности</h3></div><p>Сравнивайте фигуры и выбирайте недостающий элемент. До завершения можно менять ответы.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-results.avif')))<img class="step-asset" src="{{ asset('assets/step-results.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">▥</div>@endif
      <div class="step-label"><b>03</b><h3>Откройте результат теста</h3></div><p>После прохождения откройте персональный отчёт через демонстрационный платёжный сценарий.</p>
    </article>
  </div>
</section>
<section class="section faq-section" id="faq" aria-labelledby="faq-title">
  <h2 id="faq-title">Частые вопросы <span>о тесте на IQ</span></h2>
  <p class="section-subtitle">Нужна ли регистрация, как оцениваются ответы, сколько стоит отчёт и насколько точен онлайн-тест.</p>
  <div class="faq-list">
    @foreach(config('iq_faq') as $item)
    <details>
      <summary>{{ $item['question'] }}</summary>
      <p>{{ $item['answer'] }}</p>
    </details>
    @endforeach
  </div>
</section>
<section class="bottom-cta">
  <div class="bottom-cta-copy"><h2>Проверьте логику — <span>пройдите тест на IQ</span></h2><p>Без регистрации. Результаты — в персональном отчёте.</p></div>
  <form action="{{ route('attempt.start') }}" method="POST">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <button class="btn btn-primary" type="submit">Пройти IQ-тест</button>
  </form>
</section>
@endsection
