@extends('layouts.app')
@section('title', 'Ваш персональный отчёт — IQ.Профиль')
@section('private', 'yes')
@section('page-class', 'result-page')

@section('content')
@php
  $score = (int) $attempt->correct_count;
  $summary = match (true) {
    $percentage >= 80 => 'Вы уверенно справились с большинством задач этого набора.',
    $percentage >= 60 => 'Вы нашли большую часть закономерностей. Некоторые задания потребовали более сложных преобразований.',
    $percentage >= 40 => 'Вы распознали часть закономерностей. Повторное прохождение поможет потренировать поиск правил.',
    default => 'Это отправная точка для тренировки визуальных закономерностей. Результат относится только к этому набору.',
  };
  $minutes = intdiv((int) $attempt->elapsed_seconds, 60);
  $seconds = (int) $attempt->elapsed_seconds % 60;
@endphp
<section class="result-container" aria-labelledby="result-title">
  <header class="result-head">
    <span class="pill">Персональный отчёт</span>
    <h1 id="result-title">Ваш результат</h1>
    <p class="lead">Персональный отчёт по выполненным заданиям на логику и поиск закономерностей.</p>
    <p class="result-report-date">Пройдено {{ \Carbon\Carbon::parse($attempt->completed_at)->format('d.m.Y в H:i') }}</p>
  </header>

  <section class="result-graph-card result-score-hero" aria-labelledby="result-chart-title">
    <div class="result-score-copy">
      <span class="result-score-caption">Решено правильно</span>
      <h2 id="result-chart-title"><strong>{{ $score }}</strong> <span>из {{ $totalQuestions }}</span></h2>
      <p>{{ $summary }}</p>
    </div>
    <div class="result-chart">
      <div class="result-score-percentage">{{ $percentage }}<span>%</span></div>
      <div class="chart-track" role="progressbar" aria-label="Доля верно решённых заданий" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percentage }}">
        <div style="width: {{ $percentage }}%"></div>
      </div>
      <p class="result-score-legend">Доля правильных решений в этой попытке</p>
    </div>
  </section>

  <div class="stat-grid result-stat-grid" role="group" aria-label="Сводка прохождения">
    <article class="stat-card">
      <span>Всего заданий</span>
      <strong>{{ $totalQuestions }}</strong>
    </article>
    <article class="stat-card">
      <span>Правильных решений</span>
      <strong>{{ $score }}</strong>
    </article>
    <article class="stat-card">
      <span>Продолжительность</span>
      <strong>{{ sprintf('%02d:%02d', $minutes, $seconds) }}</strong>
    </article>
  </div>

  @if (count($categoryBreakdown) > 0)
    <section class="result-category-section" aria-labelledby="category-title">
      <div class="result-section-intro">
        <h2 id="category-title">По направлениям заданий</h2>
        <p>Сравните результаты по категориям заданий.</p>
      </div>
      <div class="result-category-grid">
        @foreach ($categoryBreakdown as $category)
          <article class="category-result-card">
            <div class="category-result-heading">
              <h3>{{ $category['label'] }}</h3>
              <strong>{{ $category['correct'] }} / {{ $category['total'] }}</strong>
            </div>
            <div class="category-progress" role="progressbar" aria-label="{{ $category['label'] }}: {{ $category['correct'] }} из {{ $category['total'] }}" aria-valuemin="0" aria-valuemax="{{ $category['total'] }}" aria-valuenow="{{ $category['correct'] }}">
              <span style="width: {{ $category['percentage'] }}%"></span>
            </div>
          </article>
        @endforeach
      </div>
    </section>
  @else
    <p class="result-legacy-note">Эта попытка была завершена до обновления графических заданий. Общий результат сохранён; разбивка по новым категориям для неё недоступна.</p>
  @endif

  <section class="result-interpretation" aria-labelledby="interpretation-title">
    <h2 id="interpretation-title">Как понимать результат</h2>
    <div class="interpretation-grid">
      <div>
        <h3>Что вы узнали</h3>
        <p>Сколько визуальных задач удалось решить и сколько времени заняло прохождение.</p>
      </div>
      <div>
        <h3>Что означает процент</h3>
        <p>Это доля верных решений в вашей попытке, а не процентиль относительно других людей.</p>
      </div>
      <div>
        <h3>Что можно сделать дальше</h3>
        <p>Пройти тест повторно и потренировать поиск закономерностей. Задания могут повторяться, что способно повлиять на следующий результат.</p>
      </div>
    </div>
    <p class="result-interpretation-note">Результаты отражают выполнение заданий сервиса и не являются стандартизированной оценкой IQ.</p>
  </section>

  <div class="result-actions">
    <form action="{{ route('attempt.start') }}" method="POST">
      @csrf
      <button type="submit" class="btn btn-primary">Пройти тест ещё раз</button>
    </form>
    @auth
      <a href="{{ route('account.home') }}" class="btn btn-secondary">Личный кабинет</a>
      <a href="{{ route('account.export', $attempt->uuid) }}" class="btn btn-secondary">Скачать данные CSV</a>
    @endauth
    <a href="{{ route('landing') }}" class="btn btn-secondary">На главную</a>
  </div>
</section>
@endsection
