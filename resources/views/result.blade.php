@extends('layouts.app')
@section('title', 'Ваш персональный отчёт — IQ.Профиль')
@section('private', 'yes')
@section('page-class', 'result-page')

@section('content')
<section class="result-container" aria-labelledby="result-title">
  <header class="result-head">
    <span class="pill">Ваш персональный отчёт</span>
    <h1 id="result-title">Результаты графического теста</h1>
    <p class="lead">25 задач на поиск закономерностей. Все показатели рассчитаны по вашим сохранённым ответам.</p>
    <p class="result-report-date">Дата завершения: {{ \Carbon\Carbon::parse($attempt->completed_at)->format('d.m.Y, H:i') }}</p>
  </header>

  <div class="stat-grid" role="group" aria-label="Итоги прохождения">
    <article class="stat-card">
      <span>Правильных ответов</span>
      <strong>{{ $attempt->correct_count }} <small>/ 25</small></strong>
    </article>
    <article class="stat-card">
      <span>Доля верных ответов</span>
      <strong>{{ $percentage }}<small>%</small></strong>
    </article>
    <article class="stat-card">
      <span>Время прохождения</span>
      <strong>{{ sprintf('%02d:%02d', intdiv($attempt->elapsed_seconds, 60), $attempt->elapsed_seconds % 60) }}</strong>
    </article>
    <article class="stat-card">
      <span>Неверных ответов</span>
      <strong>{{ 25 - $attempt->correct_count }}</strong>
    </article>
  </div>

  <section class="result-graph-card" aria-labelledby="result-chart-title">
    <div>
      <h2 id="result-chart-title">Ваш результат: {{ $attempt->correct_count }} из 25</h2>
      <p>Диаграмма показывает правильные и неправильные ответы только в этой попытке.</p>
    </div>
    <div class="result-chart">
      <div class="chart-track" role="progressbar" aria-label="Доля правильно решённых заданий" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percentage }}">
        <div style="width: {{ $percentage }}%"></div>
      </div>
      <div class="chart-legend">
        <span><span class="legend-dot legend-correct" aria-hidden="true"></span> Верно: {{ $attempt->correct_count }}</span>
        <span><span class="legend-dot legend-incorrect" aria-hidden="true"></span> Ошибки: {{ 25 - $attempt->correct_count }}</span>
      </div>
    </div>
  </section>

  <section class="result-category-section" aria-labelledby="category-title">
    <div class="result-section-intro">
      <h2 id="category-title">Результаты по типам заданий</h2>
      <p>Разбивка по пяти группам графических задач. В каждой группе — по пять вопросов.</p>
    </div>
    <div class="result-category-grid">
      @foreach ($categoryBreakdown as $category)
        <article class="category-result-card">
          <div class="category-result-heading">
            <h3>{{ $category['label'] }}</h3>
            <strong>{{ $category['correct'] }} / {{ $category['total'] }}</strong>
          </div>
          <div class="category-progress" role="progressbar" aria-label="{{ $category['label'] }} — {{ $category['correct'] }} из {{ $category['total'] }}" aria-valuemin="0" aria-valuemax="{{ $category['total'] }}" aria-valuenow="{{ $category['correct'] }}">
            <span style="width: {{ $category['percentage'] }}%"></span>
          </div>
          <small>Верно {{ $category['percentage'] }}% вопросов этой группы</small>
        </article>
      @endforeach
    </div>
    <p class="category-explanation">Эти группы отражают только выполнение конкретных упражнений. Пять вопросов на одну группу недостаточны, чтобы диагностировать отдельные когнитивные способности или делать выводы о ваших устойчивых сильных и слабых сторонах.</p>
  </section>

  <section class="result-interpretation" aria-labelledby="interpretation-title">
    <h2 id="interpretation-title">Как правильно понимать результат</h2>
    <div class="interpretation-grid">
      <div>
        <h3>Что показывает отчёт</h3>
        <p>Ваши 25 ответов, долю правильных решений, фактическое время и объяснения закономерностей.</p>
      </div>
      <div>
        <h3>Чего он не показывает</h3>
        <p>Нормативный IQ, процентиль относительно населения, диагноз или прогноз интеллектуальных способностей.</p>
      </div>
      <div>
        <h3>Как рассчитывается оценка</h3>
        <p>Каждый правильный ответ = 1 балл, неправильный = 0. Итог — сумма из 25, а процент — эта сумма, делённая на 25 и умноженная на 100.</p>
      </div>
    </div>
    <p class="result-interpretation-note">Тест не стандартизирован на репрезентативной выборке. Время не влияет на количество баллов: ограничений по времени нет. Повторное прохождение может улучшить результат за счёт знакомства с типами задач.</p>
  </section>

  <section class="answer-review" aria-labelledby="review-title">
    <h2 id="review-title">Подробный разбор 25 вопросов</h2>
    <p>Каждое объяснение относится к конкретному заданию и вашему ответу.</p>
    <nav class="review-index" aria-label="Перейти к разбору вопроса">
      @foreach ($answers as $answer)
        <a href="#review-question-{{ $answer->position }}" @class(['review-index-chip', 'review-index-chip-correct' => $answer->selected_code === $answer->correct_code, 'review-index-chip-incorrect' => $answer->selected_code !== $answer->correct_code]) aria-label="Вопрос {{ $answer->position }}, {{ $answer->selected_code === $answer->correct_code ? 'верно' : 'ошибка' }}">{{ $answer->position }}</a>
      @endforeach
    </nav>
    <div class="review-grid">
      @foreach ($answers as $answer)
        <article class="review-card" id="review-question-{{ $answer->position }}">
          <div class="review-card-head">
            <h3>Вопрос {{ $answer->position }}</h3>
            <span class="{{ $answer->selected_code === $answer->correct_code ? 'correct' : 'incorrect' }}">{{ $answer->selected_code === $answer->correct_code ? 'Верно' : 'Ошибка' }}</span>
          </div>
          <div class="review-comparison">
            <div><small>Ваш ответ: {{ $answer->selected_code }}</small>{{ App\Support\GraphicPuzzle::image($answer->kind, $answer->selected_value, 65) }}</div>
            <div><small>Правильно: {{ $answer->correct_code }}</small>{{ App\Support\GraphicPuzzle::image($answer->kind, $answer->correct_value, 65) }}</div>
          </div>
          <p>{{ $answer->explanation }}</p>
        </article>
      @endforeach
    </div>
  </section>

  <div class="result-actions">
    <form action="{{ route('attempt.start') }}" method="POST">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <button type="submit" class="btn btn-primary">Пройти тест ещё раз</button>
    </form>
    @auth
      <a href="{{ route('account.export', $attempt->uuid) }}" class="btn btn-secondary">Скачать ответы (CSV)</a>
    @endauth
    <a href="{{ route('landing') }}" class="btn btn-secondary">На главную</a>
  </div>
  <p class="scientific-disclaimer">Это тренировочный тест с оригинальными графическими задачами, а не стандартизированная психометрическая методика. Результат нельзя корректно перевести в IQ-балл, нормативный процентиль или доверительный интервал без исследований надёжности, валидности и соответствующей нормативной выборки.</p>
</section>
@endsection