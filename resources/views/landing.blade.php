@extends('layouts.app')
@section('title', 'Тест на IQ онлайн — графические матрицы и логика | IQ.Профиль')
@section('description', 'Пройдите тест на IQ с графическими матрицами и задачами на логику. Находите закономерности, сохраняйте прогресс и откройте персональный отчёт с разбором ответов.')
@section('page-class','landing-page')
@section('landing-nav','yes')
@section('content')
<section class="hero" aria-labelledby="hero-title">
  <div class="hero-copy">
    <h1 id="hero-title">Тест на IQ онлайн:<br><span>графические матрицы</span><br><span>и логические задачи</span></h1>
    <p>Сравнивайте фигуры, находите закономерности и выбирайте недостающий элемент в графической матрице. Решайте в своём темпе: ответы сохраняются, а к задачам можно возвращаться.</p>
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
  <h2 id="benefits-title">Преимущества <span>IQ-теста онлайн</span></h2>
  <p class="section-subtitle">Графические задания на закономерности и пространственное рассуждение. Разбирайтесь в правилах, следите за прогрессом и откройте отчёт с объяснениями решений.</p>
  <div class="feature-grid">
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-science.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-science.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">◇</div>@endif
      <h3>Невербальные задания на логику</h3><p>Сравнивайте строки и столбцы графических матриц, чтобы определить недостающий элемент. Задачи используют форму, положение, повороты и сочетания логических правил.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-format.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-format.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▤</div>@endif
      <h3>Без ограничения времени</h3><p>Таймер отсчитывает время с первого задания, но не ограничивает прохождение. Возвращайтесь к задачам, меняйте ответы и продолжайте попытку в том же браузере.</p>
    </article>
    <article class="feature-card">
      @if(file_exists(public_path('assets/benefit-results.avif')))<img class="feature-asset" src="{{ asset('assets/benefit-results.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="feature-icon" aria-hidden="true">▥</div>@endif
      <h3>Результат с разбором решений</h3><p>В отчёте — верные ответы, процент выполнения, затраченное время и результаты по направлениям задач. Для каждого задания предусмотрено объяснение закономерности.</p>
    </article>
  </div>
</section>
<section class="section" id="tests" aria-labelledby="tests-title">
  <h2 id="tests-title">Тесты на IQ <span>и логическое мышление</span></h2>
  <p class="section-subtitle">Начните с графических матриц на логику и поиск закономерностей. Разделы с другими видами заданий появятся по мере разработки.</p>
  <div class="test-grid">
    <article class="test-card">
      @if(file_exists(public_path('assets/test-iq.avif')))<img class="test-asset" src="{{ asset('assets/test-iq.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true"><span>■</span><span>◢</span><span>●</span></div>@endif
      <h3>IQ-тест: графические матрицы</h3><p>Находите правила изменения фигур и выбирайте недостающие элементы. Каждая задача проверяет умение замечать закономерности, а не знание готовых ответов.</p>
      <form action="{{ route('attempt.start') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button class="card-link" type="submit">Начать тест <span aria-hidden="true">→</span></button>
      </form>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-logic.avif')))<img class="test-asset" src="{{ asset('assets/test-logic.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">✥</div>@endif
      <h3>Логические связи</h3><p>Задания на рассуждение и установление взаимосвязей между элементами. Направление в разработке.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
    <article class="test-card test-card-coming">
      @if(file_exists(public_path('assets/test-memory.avif')))<img class="test-asset" src="{{ asset('assets/test-memory.avif') }}" width="120" height="120" alt="" loading="lazy">@else<div class="test-graphic" aria-hidden="true">▱</div>@endif
      <h3>Тест на внимание</h3><p>Задания на наблюдательность, сравнение и различение деталей. Направление в разработке.</p>
      <span class="card-coming-label">Скоро</span>
    </article>
  </div>
</section>
<section class="section" id="steps" aria-labelledby="steps-title">
  <h2 id="steps-title">Как пройти тест <span>и открыть отчёт</span></h2>
  <p class="section-subtitle">Укажите основные параметры прохождения, решайте графические задачи и изучайте закономерности. После завершения откройте отчёт с результатом и объяснениями.</p>
  <div class="steps-grid">
    <article class="step">
      @if(file_exists(public_path('assets/step-select.avif')))<img class="step-asset" src="{{ asset('assets/step-select.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☷</div>@endif
      <div class="step-label"><b>01</b><h3>Подготовьтесь к тесту</h3></div><p>Выберите возрастную группу и при желании укажите пол. Эти данные не меняют результат; отсчёт времени начнётся с первого задания.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-complete.avif')))<img class="step-asset" src="{{ asset('assets/step-complete.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">☑</div>@endif
      <div class="step-label"><b>02</b><h3>Найдите закономерности</h3></div><p>Сравнивайте фигуры по строкам и столбцам, выбирайте подходящий ответ и отслеживайте прогресс. Вернуться к вопросу можно до завершения.</p>
    </article>
    <article class="step">
      @if(file_exists(public_path('assets/step-results.avif')))<img class="step-asset" src="{{ asset('assets/step-results.avif') }}" width="110" height="110" alt="" loading="lazy">@else<div class="step-art" aria-hidden="true">▥</div>@endif
      <div class="step-label"><b>03</b><h3>Разберите свой результат</h3></div><p>В отчёте вы увидите правильные ответы, результаты по направлениям и объяснения решений. Доступ в текущей версии оформляется в деморежиме.</p>
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
      <div class="faq-answer"><div class="faq-answer-inner">
        <p>{{ $item['answer'] }}</p>
      </div></div>
    </details>
    @endforeach
  </div>
</section>
<section class="bottom-cta">
  <div class="bottom-cta-copy"><h2>Проверьте <span>логическое мышление</span></h2><p>Решайте графические задачи, находите закономерности и откройте подробный отчёт после завершения.</p></div>
  <form action="{{ route('attempt.start') }}" method="POST">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <button class="btn btn-primary" type="submit">Начать тест</button>
  </form>
</section>
@endsection
