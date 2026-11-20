@extends('layouts.app')
@section('title', $step === 'gender' ? 'Перед тестом — шаг 1 | IQ.Профиль' : 'Перед тестом — шаг 2 | IQ.Профиль')
@section('private', 'yes')
@section('page-class', 'intake-page')

@section('content')
<section class="intake-shell" aria-labelledby="intake-title">
  <div class="intake-progress" aria-label="Шаг {{ $step === 'gender' ? 1 : 2 }} из 2">
    <span>Перед началом теста</span>
    <strong>Шаг {{ $step === 'gender' ? 1 : 2 }} из 2</strong>
    <div class="intake-progress-track"><span style="width: {{ $step === 'gender' ? 50 : 100 }}%"></span></div>
  </div>

  <header class="intake-heading">
    <h1 id="intake-title">{{ $step === 'gender' ? 'Для начала укажите пол' : 'Укажите вашу возрастную группу' }}</h1>
    <p>{{ $step === 'gender' ? 'Не влияет на количество правильных ответов. Вы можете не указывать пол.' : 'Возраст поможет в будущем сравнивать результаты с подходящими нормативными данными.' }}</p>
  </header>

  <form method="POST" action="{{ route('quiz.intake.save', ['step' => $step]) }}" class="intake-form">
    @csrf
    @if ($step === 'gender')
      <fieldset class="intake-gender">
        <legend class="sr-only">Выберите пол</legend>
        <label class="intake-choice intake-gender-choice">
          <input class="intake-radio" type="radio" name="gender" value="male" {{ old('gender', $attempt->gender) === 'male' ? 'checked' : '' }} required>
          <span class="intake-choice-inner">
            <svg viewBox="0 0 96 112" width="72" height="84" aria-hidden="true" focusable="false"><circle cx="48" cy="18" r="13" fill="currentColor"/><path d="M32 39h32a14 14 0 0 1 14 14v31a7 7 0 0 1-14 0V58h-3v44a8 8 0 0 1-16 0V79h-2v23a8 8 0 0 1-16 0V58h-3v26a7 7 0 0 1-14 0V53a14 14 0 0 1 14-14Z" fill="currentColor"/></svg>
            <strong>Мужчина</strong>
          </span>
        </label>
        <label class="intake-choice intake-gender-choice">
          <input class="intake-radio" type="radio" name="gender" value="female" {{ old('gender', $attempt->gender) === 'female' ? 'checked' : '' }} required>
          <span class="intake-choice-inner">
            <svg viewBox="0 0 96 112" width="72" height="84" aria-hidden="true" focusable="false"><circle cx="48" cy="18" r="13" fill="currentColor"/><path d="M35 39h26a14 14 0 0 1 13 9l13 35a7 7 0 0 1-13 5l-7-20 7 29H56v7a8 8 0 0 1-16 0v-7H22l7-29-7 20a7 7 0 0 1-13-5l13-35a14 14 0 0 1 13-9Z" fill="currentColor"/></svg>
            <strong>Женщина</strong>
          </span>
        </label>
        <label class="intake-choice intake-unspecified">
          <input class="intake-radio" type="radio" name="gender" value="unspecified" {{ old('gender', $attempt->gender) === 'unspecified' ? 'checked' : '' }} required>
          <span class="intake-choice-inner">
            <svg viewBox="0 0 24 24" width="23" height="23" aria-hidden="true"><circle cx="12" cy="8" r="3.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M4.5 20c.4-4.6 3-7 7.5-7s7.1 2.4 7.5 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <strong>Не хочу указывать</strong>
          </span>
        </label>
      </fieldset>
      @error('gender')<p class="form-error" role="alert">{{ $message }}</p>@enderror
    @else
      <fieldset class="intake-age">
        <legend class="sr-only">Выберите возрастную группу</legend>
        @foreach (['18_24' => '18–24 года', '25_34' => '25–34 года', '35_44' => '35–44 года', '45_54' => '45–54 года', '55_plus' => '55 лет и старше'] as $key => $label)
          <label class="intake-choice intake-age-choice">
            <input class="intake-radio" type="radio" name="age_group" value="{{ $key }}" {{ old('age_group', $attempt->age_group) === $key ? 'checked' : '' }} required>
            <span class="intake-choice-inner"><strong>{{ $label }}</strong></span>
          </label>
        @endforeach
      </fieldset>
      @error('age_group')<p class="form-error" role="alert">{{ $message }}</p>@enderror
    @endif
    <div class="intake-actions">
      @if($step === 'age') <a class="intake-back" href="{{ route('quiz.intake', ['step' => 'gender']) }}">← Назад</a> @endif
      <button class="btn btn-primary intake-submit" type="submit">{{ $step === 'gender' ? 'Далее' : 'Начать тест' }}</button>
    </div>
  </form>
  <p class="intake-footnote">Два подготовительных шага не входят в 25 вопросов. Таймер начнёт отсчёт с первого задания.</p>
</section>
@endsection
