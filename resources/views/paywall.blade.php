@extends('layouts.app')
@section('title', 'Ваш отчёт готов — IQ.Профиль')
@section('private', 'yes')
@section('page-class', 'paywall-page')
@section('content')
<section class="paywall-layout">
  <div class="paywall-copy">
    <span class="pill">Результат готов</span>
    <h1>Получите полный<br>отчёт о вашем тесте</h1>
    <p class="lead">Узнайте результат, количество ошибок и объяснение каждой из 25 задач.</p>
    <div class="paywall-benefits">
      <div class="benefit"><span class="benefit-icon">▥</span><div><strong>Ваш результат</strong><p>Количество правильных ответов и процент</p></div></div>
      <div class="benefit"><span class="benefit-icon">◇</span><div><strong>Детальный анализ</strong><p>Разбор каждого ответа и верного решения</p></div></div>
      <div class="benefit"><span class="benefit-icon">◷</span><div><strong>Время прохождения</strong><p>Продолжительность тестирования</p></div></div>
      <div class="benefit"><span class="benefit-icon">☷</span><div><strong>Индивидуальный отчёт</strong><p>Результат именно вашей попытки</p></div></div>
    </div>
  </div>
  <div class="paywall-right">
    <div class="report-illustration" aria-hidden="true"><div class="report-page"><div class="report-head"><span class="report-logo">IQ</span><div><b></b><b></b></div></div><div class="report-lines"><i></i><i></i></div><div class="report-bars"><b></b><b></b><b></b><b></b></div></div><div class="report-lock">▣</div></div>
    <div class="offer-card">
      <span class="pill">Специальное предложение</span>
      <div class="price-row"><strong>20 ₽</strong><del>499 ₽</del></div>
      <p>Первый демонстрационный доступ к результату</p>
      <div class="subscription-note"><span aria-hidden="true">↻</span><span>В будущей коммерческой модели далее — автоматическая подписка по <strong>499 ₽/месяц</strong>. В демо подписка не активируется.</span></div>
      <a class="btn btn-primary btn-block" href="{{ route('checkout', $attempt->uuid) }}">Открыть результат за 20 ₽</a>
      <p class="demo-note">Демонстрационная оплата: деньги не списываются.</p>
    </div>
  </div>
</section>
@endsection