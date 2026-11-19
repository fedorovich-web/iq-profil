@extends('layouts.app')
@section('title', 'Тест на IQ онлайн без регистрации — графические матрицы | IQ.Профиль')
@section('description', 'Пройдите IQ-тест с графическими матрицами: находите закономерности и выбирайте недостающие элементы. Без регистрации и ограничения по времени, с персональным отчётом.')
@section('page-class','landing-page')
@section('landing-nav','yes')
@section('content')
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-copy">
    <h1 id="hero-title">Тест на IQ онлайн:<br><span>графические матрицы</span><br><span>без регистрации</span></h1>
    <p>Найдите закономерность в графических матрицах и выберите недостающий элемент. Ответы сохраняются автоматически: к любому вопросу можно вернуться до завершения теста.</p>
    <div class="hero-actions">
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="btn btn-primary" type="submit">Начать тест <span aria-hidden="true">→</span></button>
      </form>
      <a class="btn btn-secondary" href="#steps">Как проходит тест</a>
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
  <h2 id="benefits-title">Преимущества IQ-теста: <span>формат и результаты</span></h2>
  <p class="section-subtitle">Невербальные задачи на поиск закономерностей, свободный темп прохождения и сохранение ответов. После завершения можно открыть персональный отчёт с подробными результатами.</p>
  <div class="feature-grid">
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-science.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-science.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">◇</div>@endif
      <h3>Невербальные задачи на закономерности</h3><p>Вместо вопросов на эрудицию — графические матрицы. Сравнивайте форму, расположение и количество элементов, чтобы определить правило и выбрать недостающий вариант.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-format.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-format.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▤</div>@endif
      <h3>Свой темп и сохранение ответов</h3><p>Таймер показывает продолжительность, но не прерывает тест. Можно вернуться к предыдущим заданиям, изменить ответ и продолжить незавершённую попытку в том же браузере.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-results.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-results.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▥</div>@endif
      <h3>Подробный персональный отчёт</h3><p>После прохождения откройте отчёт с числом верных ответов, процентом выполнения, временем и результатами по категориям. В личном кабинете можно возвращаться к ранее открытым отчётам.</p>
    </article>
  </div>
</section>
<section class="section" id="tests" aria-labelledby="tests-title">
  <h2 id="tests-title">Тесты на <span>интеллект и логику</span></h2>
  <p class="section-subtitle">Сейчас доступны графические матрицы. Новые форматы заданий появятся по мере разработки.</p>
  <div class="test-grid">
    <article class="test-card">
      @if(file_exists(public_path('assets/test-iq.avif')))<img class="test-asset" src="{{ asset('assets/test-iq.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true"><span>■</span><span>◢</span><span>●</span></div>@endif
      <h3>Графические матрицы</h3><p>Невербальный тест: сопоставляйте фигуры, повороты и сочетания признаков, чтобы найти пропущенный элемент.</p>
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="card-link" type="submit">Начать тест <span aria-hidden="true">→</span></button>
      </form>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-logic.avif')))<img class="test-asset" src="{{ asset('assets/test-logic.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">✥</div>@endif
      <h3>Логические связи</h3><p>Отдельные задания на рассуждение и установление связей. Формат в разработке.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-memory.avif')))<img class="test-asset" src="{{ asset('assets/test-memory.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">▱</div>@endif
      <h3>Внимание к деталям</h3><p>Задания на наблюдательность и точность восприятия. Формат в разработке.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
  </div>
</section>
<section class="section" id="steps" aria-labelledby="steps-title">
  <h2 id="steps-title">Как пройти тест <span>и открыть отчёт</span></h2>
  <p class="section-subtitle">Начните в браузере, решайте задания без ограничения времени, затем откройте результаты.</p>
  <div class="steps-grid">
    <article class="step">
      @if(file_exists(public_path('assets/step-select.avif')))<img class="step-asset" src="{{ asset('assets/step-select.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☷</div>@endif
      <div class="step-label"><b>01</b><h3>Начните без регистрации</h3></div><p>Нажмите «Начать тест». Для прохождения не нужно создавать аккаунт или указывать электронную почту.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-complete.avif')))<img class="step-asset" src="{{ asset('assets/step-complete.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☑</div>@endif
      <div class="step-label"><b>02</b><h3>Найдите закономерности</h3></div><p>Сравнивайте элементы матриц и выбирайте недостающий вариант. До завершения ответы можно менять.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-results.avif')))<img class="step-asset" src="{{ asset('assets/step-results.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">▥</div>@endif
      <div class="step-label"><b>03</b><h3>Откройте персональный отчёт</h3></div><p>После завершения перейдите к оформлению доступа к результатам и персональному отчёту.</p>
    </article>
  </div>
</section>
<section class="section faq-section" id="faq" aria-labelledby="faq-title">
  <h2 id="faq-title">Частые вопросы <span>об IQ-тесте</span></h2>
  <p class="section-subtitle">О заданиях, регистрации, сохранении ответов, результатах и доступе к отчёту.</p>
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
  <div class="bottom-cta-copy"><h2>Проверьте <span>логическое мышление</span></h2><p>Начните без регистрации. Персональный отчёт — после прохождения.</p></div>
  <form action="{{ route('attempt.start') }}" method="POST">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <button class="btn btn-primary" type="submit">Начать тест</button>
  </form>
</section>
@endsection
