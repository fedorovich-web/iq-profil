@extends('layouts.app')
@section('title', 'Ваш индивидуальный отчёт — IQ.Профиль')
@section('private', 'yes')
@section('page-class', 'result-page')
@section('content')
<section class="result-container">
  <div class="result-head"><span class="pill">Отчёт открыт</span><h1>Ваш результат готов</h1><p class="lead">Анализ выполненных графических заданий на закономерности.</p></div>
  <div class="stat-grid">
    <article class="stat-card"><span>Правильных ответов</span><strong>{{ $attempt->correct_count }} <small>/ 25</small></strong></article>
    <article class="stat-card"><span>Точность</span><strong>{{ $percentage }}<small>%</small></strong></article>
    <article class="stat-card"><span>Время прохождения</span><strong>{{ sprintf('%02d:%02d', intdiv($attempt->elapsed_seconds, 60), $attempt->elapsed_seconds % 60) }}</strong></article>
    <article class="stat-card"><span>Ошибок</span><strong>{{ 25 - $attempt->correct_count }}</strong></article>
  </div>
  <div class="result-graph-card"><div><h2>Точность ответов</h2><p>Показатель рассчитан по вашим 25 ответам.</p></div>
    <div class="result-chart" role="img" aria-label="Правильно: {{ $attempt->correct_count }}. Ошибок: {{ 25 - $attempt->correct_count }}."><div class="chart-track"><div style="width: {{ $percentage }}%"></div></div><div class="chart-legend"><span>● Правильно: {{ $attempt->correct_count }}</span><span>● Ошибки: {{ 25 - $attempt->correct_count }}</span></div></div>
  </div>
  <section class="answer-review" aria-labelledby="review-title">
    <h2 id="review-title">Подробный разбор решений</h2><p>Посмотрите свой ответ, правильный вариант и объяснение закономерности.</p>
    <div class="review-grid">
      @foreach ($answers as $answer)
        <article class="review-card">
          <div class="review-card-head"><h3>Вопрос {{ $answer->position }}</h3><span class="{{ $answer->selected_code === $answer->correct_code ? 'correct' : 'incorrect' }}">{{ $answer->selected_code === $answer->correct_code ? 'Верно' : 'Ошибка' }}</span></div>
          <div class="review-comparison"><div><small>Ваш ответ {{ $answer->selected_code }}</small>{{ App\Support\GraphicPuzzle::image($answer->kind, $answer->selected_value, 65) }}</div><div><small>Правильно {{ $answer->correct_code }}</small>{{ App\Support\GraphicPuzzle::image($answer->kind, $answer->correct_value, 65) }}</div></div>
          <p>{{ $answer->explanation }}</p>
        </article>
      @endforeach
    </div>
  </section>
  <div class="result-actions"><form action="{{ route('attempt.start') }}" method="POST"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="btn btn-primary">Пройти тест ещё раз</button></form><a href="{{ route('landing') }}" class="btn btn-secondary">На главную</a></div>
  <p class="scientific-disclaimer">Это учебные задания на мышление, а не стандартизированный психометрический IQ-тест. Результат нельзя корректно перевести в IQ-баллы без валидной нормативной выборки.</p>
</section>
@endsection