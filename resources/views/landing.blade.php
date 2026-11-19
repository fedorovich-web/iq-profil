@extends('layouts.app')
@section('title', 'Тест на IQ онлайн без регистрации — логические задания | IQ.Профиль')
@section('description', 'Онлайн-тест с графическими матрицами: найдите закономерности, выберите недостающие элементы и получите персональный отчёт. Начните без регистрации.')
@section('page-class','landing-page')
@section('landing-nav','yes')
@section('content')
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-copy">
    <h1 id="hero-title">Тест на IQ онлайн:<br><span>задачи на логику</span><br><span>без регистрации</span></h1>
    <p>В графических матрицах нужно найти закономерность и выбрать недостающий элемент. Ответы сохраняются: до завершения теста можно вернуться к предыдущим вопросам.</p>
    <div class="hero-actions">
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="btn btn-primary" type="submit">Начать тест <span aria-hidden="true">→</span></button>
      </form>
      <a class="btn btn-secondary" href="#steps">Как проходит тест</a>
    </div>
    <p class="hero-disclosure">Это тренировочный тест, а не стандартизированная оценка IQ. Отчёт в демоверсии доступен без карты и реальных списаний.</p>
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
  <h2 id="benefits-title">Что входит <span>в тест на логику</span></h2>
  <p class="section-subtitle">Графические матрицы, свободный темп прохождения и подробные итоги по категориям заданий.</p>
  <div class="feature-grid">
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-science.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-science.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">◇</div>@endif
      <h3>Графические матрицы</h3><p>Сравнивайте форму, расположение и количество элементов, чтобы определить правило и выбрать ответ.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-format.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-format.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▤</div>@endif
      <h3>Без ограничения времени</h3><p>Решайте задачи в своём темпе. Можно вернуться к предыдущему вопросу и изменить ответ до завершения попытки.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-results.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-results.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▥</div>@endif
      <h3>Персональный отчёт</h3><p>После прохождения доступны число верных ответов, процент выполнения, затраченное время и результаты по категориям заданий.</p>
    </article>
  </div>
</section>
<section class="section" id="tests" aria-labelledby="tests-title">
  <h2 id="tests-title">Доступный тест <span>и новые направления</span></h2>
  <p class="section-subtitle">Сейчас доступен тест с графическими матрицами. Другие направления пока находятся в разработке.</p>
  <div class="test-grid">
    <article class="test-card">
      @if(file_exists(public_path('assets/test-iq.avif')))<img class="test-asset" src="{{ asset('assets/test-iq.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true"><span>■</span><span>◢</span><span>●</span></div>@endif
      <h3>Графические матрицы и логика</h3><p>Невербальные задания на поиск закономерностей, повороты и сочетание признаков. Для начала не нужна регистрация.</p>
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="card-link" type="submit">Начать тест <span aria-hidden="true">→</span></button>
      </form>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-logic.avif')))<img class="test-asset" src="{{ asset('assets/test-logic.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">✥</div>@endif
      <h3>Логические связи</h3><p>Отдельный формат заданий на рассуждение. Раздел ещё не доступен.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-memory.avif')))<img class="test-asset" src="{{ asset('assets/test-memory.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">▱</div>@endif
      <h3>Внимание к деталям</h3><p>Задания на наблюдательность и точность восприятия. Раздел в разработке.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
  </div>
</section>
<section class="section" id="steps" aria-labelledby="steps-title">
  <h2 id="steps-title">Как пройти тест <span>и получить отчёт</span></h2>
  <p class="section-subtitle">Три шага — от запуска теста до просмотра результатов. Аккаунт для прохождения не требуется.</p>
  <div class="steps-grid">
    <article class="step">
      @if(file_exists(public_path('assets/step-select.avif')))<img class="step-asset" src="{{ asset('assets/step-select.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☷</div>@endif
      <div class="step-label"><b>01</b><h3>Начните без регистрации</h3></div><p>Откройте задания в браузере. Указывать почту или создавать аккаунт не нужно.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-complete.avif')))<img class="step-asset" src="{{ asset('assets/step-complete.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☑</div>@endif
      <div class="step-label"><b>02</b><h3>Решите графические матрицы</h3></div><p>Сравнивайте элементы, выбирайте недостающий вариант и при необходимости возвращайтесь к предыдущим вопросам.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-results.avif')))<img class="step-asset" src="{{ asset('assets/step-results.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">▥</div>@endif
      <div class="step-label"><b>03</b><h3>Посмотрите результаты</h3></div><p>После завершения подтвердите демодоступ к отчёту: реальных платежей и списаний нет.</p>
    </article>
  </div>
</section>
<section class="section faq-section" id="faq" aria-labelledby="faq-title">
  <h2 id="faq-title">Вопросы о тесте <span>и результатах</span></h2>
  <p class="section-subtitle">О формате заданий, сохранении ответов, точности результатов и условиях демоверсии.</p>
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
  <div class="bottom-cta-copy"><h2>Тест на <span>логическое мышление</span></h2><p>Графические матрицы без регистрации и ограничения по времени.</p></div>
  <form action="{{ route('attempt.start') }}" method="POST">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <button class="btn btn-primary" type="submit">Начать тест</button>
  </form>
</section>
@endsection
