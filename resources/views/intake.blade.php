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
    <p>{{ $step === 'gender' ? 'Не влияет на количество правильных ответов. Вы можете не указывать пол.' : 'Возрастная группа сохраняется для будущего анализа. Сейчас она не влияет на подсчёт правильных ответов.' }}</p>
  </header>

  <form method="POST" action="{{ route('quiz.intake.save', ['step' => $step]) }}" class="intake-form">
    @csrf
    @if ($step === 'gender')
      <fieldset class="intake-gender">
        <legend class="sr-only">Выберите пол</legend>
        <label class="intake-choice intake-gender-choice">
          <input class="intake-radio" type="radio" name="gender" value="male" {{ old('gender', $attempt->gender) === 'male' ? 'checked' : '' }} required>
          <span class="intake-choice-inner">
            <img class="intake-icon" src="{{ asset('assets/icons/intake/man.svg') }}" width="84" height="84" alt="" loading="eager" decoding="async">
            <strong>Мужчина</strong>
          </span>
        </label>
        <label class="intake-choice intake-gender-choice">
          <input class="intake-radio" type="radio" name="gender" value="female" {{ old('gender', $attempt->gender) === 'female' ? 'checked' : '' }} required>
          <span class="intake-choice-inner">
            <img class="intake-icon" src="{{ asset('assets/icons/intake/woman.svg') }}" width="84" height="84" alt="" loading="eager" decoding="async">
            <strong>Женщина</strong>
          </span>
        </label>
        <label class="intake-choice intake-unspecified">
          <input class="intake-radio" type="radio" name="gender" value="unspecified" {{ old('gender', $attempt->gender) === 'unspecified' ? 'checked' : '' }} required>
          <span class="intake-choice-inner">
            <img class="intake-icon intake-icon-small" src="{{ asset('assets/icons/intake/person-off.svg') }}" width="26" height="26" alt="" loading="eager" decoding="async">
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
  <p class="intake-footnote">Подготовительные шаги не влияют на результат. Таймер начнёт отсчёт с первого задания.</p>
</section>
@endsection
