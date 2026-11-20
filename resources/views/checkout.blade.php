@extends('layouts.app')
@section('title', 'Демонстрационная оплата — IQ.Профиль')
@section('private', 'yes')
@section('page-class', 'checkout-page')
@section('content')
<section class="checkout-container">
  <nav class="checkout-steps" aria-label="Этапы получения результата">
    <div class="checkout-step done"><b>✓</b><span>Выбор предложения</span></div>
    <div class="checkout-step active" aria-current="step"><b>2</b><span>Демо-оплата</span></div>
    <div class="checkout-step"><b>3</b><span>Доступ к результату</span></div>
  </nav>
  <div class="checkout-layout">
    <div class="checkout-intro">
      <span class="pill">Без реальных списаний</span>
      <h1>Подтвердите доступ к отчёту</h1>
      <p class="lead">Проверьте условия доступа и подтвердите демонстрационный платёж. Отчёт откроется сразу после подтверждения.</p>
      <div class="subscription-box"><h2>Что входит в доступ</h2><ul><li>Количество правильных ответов и процент выполнения</li><li>Результаты по доступным направлениям заданий</li><li>Время прохождения и персональная сводка</li>@if((int) $attempt->question_revision === App\Support\PuzzleCatalog::VERSION)<li>Разбор каждого графического задания с правильной фигурой</li>@endif</ul></div>
    </div>
    <div class="checkout-card">
      <h2>Подтверждение доступа</h2>
      <div class="demo-alert"><strong>Демонстрационная оплата</strong><span>Это тестовый сценарий. Мы не принимаем данные карт и ничего не списываем.</span></div>
      <div class="order-lines">
        <div><span>Первый доступ</span><strong>20 ₽</strong></div>
        <div><span>Далее — подписка (в месяц)</span><strong>499 ₽</strong></div>
      </div>
      <p class="disclaimer">По запланированной модели после первого доступа стоимость подписки — 499 ₽ в месяц. В текущей версии оплата и автопродление демонстрационные: деньги не списываются.</p>
      <form action="{{ route('checkout.confirm', $attempt->uuid) }}" method="POST"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="btn btn-primary btn-block">Подтвердить без списания</button></form>
      <div class="payment-safe"><span class="payment-safe-icon" aria-hidden="true"><img src="{{ asset('assets/icons/client/security.avif') }}" width="32" height="32" alt=""></span><span><strong>Безопасная демонстрация</strong><br>Без платёжного провайдера и банковских данных</span></div>
    </div>
  </div>
</section>
@endsection