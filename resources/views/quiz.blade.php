@extends('layouts.app')
@section('title', 'Вопрос '.$question->position.' — IQ.Профиль')
@section('private', 'yes')
@section('page-class', 'quiz-page')
@section('content')
<section class="quiz-container" aria-labelledby="question-heading">
  <div class="quiz-toolbar">
    <span class="quiz-counter">Вопрос <strong>{{ $question->position }}</strong> из {{ $totalQuestions }}</span>
    <div class="quiz-timer" role="timer" aria-label="Продолжительность теста"><span class="quiz-timer-icon" aria-hidden="true"><img src="{{ asset('assets/icons/client/history.avif') }}" width="20" height="20" alt=""></span><time id="quiz-timer" data-attempt="{{ $attempt->uuid }}" data-elapsed="{{ $elapsedSeconds }}" data-max="{{ $timerMaxSeconds }}" data-idle="{{ $timerIdleSeconds }}">{{ App\Support\AttemptTimer::format($elapsedSeconds) }}</time></div>
  </div>
  <div class="progress" role="progressbar" aria-label="Прогресс тестирования" aria-valuemin="0" aria-valuemax="{{ $totalQuestions }}" aria-valuenow="{{ $answeredQuestions }}"><span style="width: {{ $totalQuestions > 0 ? $answeredQuestions / $totalQuestions * 100 : 0 }}%"></span></div>
  <div class="quiz-headline">
    <span class="quiz-kicker">Найдите закономерность</span>
    <h1 id="question-heading">Какой фигуры не хватает?</h1>
    <p>{{ $question->prompt }}</p>
  </div>
  <div class="question-board" role="group" aria-label="Матрица три на три. Девятая клетка пуста; сравните восемь показанных фигур.">
  @foreach ($cells as $i => $value)
    <div class="matrix-cell {{ $i === 8 ? 'matrix-missing' : '' }}" role="img" aria-label="Строка {{ intdiv($i, 3) + 1 }}, столбец {{ $i % 3 + 1 }}: {{ $i === 8 ? 'пропущенная фигура' : App\Support\GraphicPuzzle::name($question->kind, $value) }}">
      @if ($i === 8) <span aria-hidden="true">?</span>
      @else {{ App\Support\GraphicPuzzle::image($question->kind, $value, 78) }} @endif
    </div>
  @endforeach
  </div>
  <div class="answer-block">
    <h2>Выберите правильный вариант</h2>
    <p>Выберите один из шести вариантов ответа</p>
    <form id="answer-form" action="{{ route('quiz.answer') }}" method="POST">
      <input type="hidden" name="_token" value="{{ csrf_token() }}">
      <input type="hidden" name="question_id" value="{{ $question->id }}">
      <input type="hidden" name="attempt_uuid" value="{{ $attempt->uuid }}">
      <div class="answer-grid" role="radiogroup" aria-label="Варианты ответа">
      @foreach ($options as $option)
        <label class="answer-tile">
          <input type="radio" name="answer_option_id" value="{{ $option->id }}" {{ (int) $selected === $option->id ? 'checked' : '' }} aria-label="Вариант {{ $option->code }}: {{ App\Support\GraphicPuzzle::name($question->kind, $option->graphic_value) }}">
          <span class="answer-face"><span class="answer-letter">{{ $option->code }}</span>{{ App\Support\GraphicPuzzle::image($question->kind, $option->graphic_value, 72) }}</span>
        </label>
      @endforeach
      </div>
      @error('answer_option_id')<p class="form-error" role="alert">{{ $message }}</p>@enderror
      <p class="save-status" id="save-status" role="status" aria-live="polite"></p>
      <div class="quiz-navigation">
        <button type="submit" name="navigation" value="back" class="btn btn-secondary" {{ $question->position === 1 ? 'disabled' : '' }}>← Назад</button>
        <button type="submit" name="navigation" value="{{ $question->position === $totalQuestions ? 'finish' : 'next' }}" class="btn btn-primary">{{ $question->position === $totalQuestions ? 'Завершить тест' : 'Далее →' }}</button>
      </div>
    </form>
  </div>
</section>
@php($milestone = session('quiz_progress_milestone'))
@if(is_array($milestone) && ($milestone['attempt_uuid'] ?? null) === $attempt->uuid)
<aside class="quiz-milestone" data-quiz-milestone hidden role="dialog" aria-modal="true" aria-labelledby="quiz-milestone-title" aria-describedby="quiz-milestone-description">
  <div class="quiz-milestone-panel">
    <button class="quiz-milestone-close" data-milestone-close type="button" aria-label="Закрыть уведомление">×</button>
    <div class="quiz-milestone-ring" style="--progress-angle: {{ (int) $milestone['percent'] * 3.6 }}deg" aria-hidden="true">
      <strong>{{ (int) $milestone['percent'] }}%</strong>
    </div>
    <div class="quiz-milestone-copy">
      <strong id="quiz-milestone-title">{{ $milestone['stage'] === 1 ? 'Треть теста позади' : 'Большая часть теста пройдена' }}</strong>
      <p id="quiz-milestone-description">{{ $milestone['stage'] === 1 ? 'Ответы сохранены. Продолжайте в своём темпе.' : 'До персонального отчёта осталось немного.' }}</p>
    </div>
    <button class="btn btn-primary quiz-milestone-continue" data-milestone-close type="button">Продолжить тест</button>
  </div>
</aside>
@endif
@endsection