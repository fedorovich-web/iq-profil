@extends('layouts.app')
@section('title', 'IQ-тест онлайн на логику и мышление | IQ.Профиль')
@section('description', 'Пройдите IQ-тест онлайн: задания на логику, визуальное мышление и поиск закономерностей. Начните без регистрации, решайте в своём темпе и откройте персональный отчёт.')
@section('page-class','landing-page')
@section('landing-nav','yes')
@section('content')
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-copy">
    <span class="eyebrow">Задания на логику и визуальное мышление</span>
    <h1 id="hero-title">IQ-тест онлайн:<br><span>проверьте логику</span></h1>
    <p>Находите связи между фигурами, проверяйте свои решения и узнавайте, как справляетесь с логическими задачами. Начать можно без регистрации.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="#tests">Посмотреть тесты <span aria-hidden="true">→</span></a>
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="btn btn-secondary" type="submit"><span class="play-symbol" aria-hidden="true"><svg viewBox="0 0 26 26" focusable="false"><circle cx="13" cy="13" r="10.5"></circle><path d="m10.5 8.4 7.3 4.6-7.3 4.6V8.4Z"></path></svg></span> Начать IQ-тест</button>
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
  <h2 id="benefits-title">Почему выбирают <span>IQ.Профиль</span></h2>
  <p class="section-subtitle">Понятные задания, удобное прохождение и персональные результаты без необоснованных обещаний.</p>
  <div class="feature-grid">
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-science.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-science.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">◇</div>@endif
      <h3>Логика без сложных инструкций</h3><p>Ищите закономерности в фигурах, поворотах и визуальных последовательностях.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-format.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-format.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▤</div>@endif
      <h3>Удобно на любом устройстве</h3><p>Проходите тест в своём темпе на смартфоне или компьютере. Ответы можно изменить до завершения.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-results.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-results.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▥</div>@endif
      <h3>Отчёт о вашем прохождении</h3><p>Посмотрите долю правильных решений, время и результаты по направлениям заданий.</p>
    </article>
  </div>
</section>
<section class="section" id="tests" aria-labelledby="tests-title">
  <h2 id="tests-title">Доступные <span>тесты</span></h2>
  <p class="section-subtitle">Начните с теста на логику и закономерности. Новые направления появятся по мере готовности.</p>
  <div class="test-grid">
    <article class="test-card">
      @if(file_exists(public_path('assets/test-iq.avif')))<img class="test-asset" src="{{ asset('assets/test-iq.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true"><span>■</span><span>◢</span><span>●</span></div>@endif
      <h3>IQ-тест на логику</h3><p>Графические задачи на поиск правил, сравнение фигур и визуальные закономерности. Без регистрации.</p>
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
  <h2 id="steps-title">Как это <span>работает</span></h2>
  <p class="section-subtitle">Всё просто: начните тест, решите задания и посмотрите персональную сводку.</p>
  <div class="steps-grid">
    <article class="step">
      @if(file_exists(public_path('assets/step-select.avif')))<img class="step-asset" src="{{ asset('assets/step-select.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☷</div>@endif
      <div class="step-label"><b>01</b><h3>Выберите тест</h3></div><p>Откройте доступное направление и приступайте без регистрации.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-complete.avif')))<img class="step-asset" src="{{ asset('assets/step-complete.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☑</div>@endif
      <div class="step-label"><b>02</b><h3>Решайте задачи</h3></div><p>Выбирайте подходящие варианты и при необходимости возвращайтесь к предыдущим ответам.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-results.avif')))<img class="step-asset" src="{{ asset('assets/step-results.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">▥</div>@endif
      <div class="step-label"><b>03</b><h3>Посмотрите результат</h3></div><p>Получите персональный отчёт после подтверждения демонстрационного доступа.</p>
    </article>
  </div>
</section>
<section class="section faq-section" id="faq" aria-labelledby="faq-title">
  <h2 id="faq-title">Ответы на <span>частые вопросы</span></h2>
  <p class="section-subtitle">Прохождение, результаты, стоимость доступа и подписка — всё, что важно знать перед стартом.</p>
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
  <div><h2>Проверьте <span>свою логику</span></h2><p>Попробуйте графические задания и узнайте свой результат. Начать можно без регистрации.</p></div>
  <form action="{{ route('attempt.start') }}" method="POST">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <button class="btn btn-primary" type="submit">Пройти IQ-тест</button>
  </form>
</section>
@endsection
