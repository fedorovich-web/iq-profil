@extends('layouts.app')
@section('title', 'Вопрос '.$question->position.' из 25 — IQ.Профиль')
@section('private', 'yes')
@section('page-class', 'quiz-page')
@section('content')
<section class="quiz-container" aria-labelledby="question-heading">
  <div class="quiz-toolbar">
    <span class="quiz-counter">Вопрос <strong>{{ $question->position }}</strong> из 25</span>
    <div class="quiz-timer" role="timer" aria-label="Продолжительность теста"><span aria-hidden="true">◷</span><time id="quiz-timer" data-start="{{ $startedTimestamp }}" data-server="{{ $serverTimestamp }}">00:00</time></div>
  </div>
  <div class="progress" role="progressbar" aria-label="Прогресс тестирования" aria-valuemin="0" aria-valuemax="25" aria-valuenow="{{ $question->position - 1 }}"><span style="width: {{ ($question->position - 1) * 4 }}%"></span></div>
  <div class="quiz-headline">
    <span class="quiz-kicker">Найдите закономерность</span>
    <h1 id="question-heading">Какой фигуры не хватает?</h1>
    <p>{{ $question->prompt }}</p>
  </div>
  <div class="question-board" role="img" aria-label="Матрица три на три. Девятая клетка пуста; изучите последовательность.">
  @foreach ($cells as $i => $value)
    <div class="matrix-cell {{ $i === 8 ? 'matrix-missing' : '' }}">
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
        <button type="submit" name="navigation" value="{{ $question->position === 25 ? 'finish' : 'next' }}" class="btn btn-primary">{{ $question->position === 25 ? 'Завершить тест' : 'Далее →' }}</button>
      </div>
    </form>
  </div>
</section>
@endsection