@extends('layouts.app')
@section('title', 'Ваш отчёт готов — IQ.Профиль')
@section('private', 'yes')
@section('page-class', 'paywall-page')
@section('content')
@if(session('quiz_gate_notice'))
<p class="quiz-gate-notice" role="status">{{ session('quiz_gate_notice') }}</p>
@endif
<section class="paywall-layout">
  <div class="paywall-copy">
    <span class="pill">Результат готов</span>
    <h1>Ваш результат<br>уже готов</h1>
    <p class="lead">Узнайте, как вы справились с заданиями: сколько ответов оказались верными, сколько времени заняло прохождение и какие направления дались лучше.</p>
    <div class="paywall-benefits">
      <div class="benefit"><span class="benefit-icon"><img src="{{ asset('assets/icons/admin/analytics.avif') }}" width="34" height="34" alt="" loading="eager"></span><div><strong>Ваш результат</strong><p>Количество верных ответов и процент выполнения теста</p></div></div>
      <div class="benefit"><span class="benefit-icon"><img src="{{ asset('assets/icons/client/layers.avif') }}" width="34" height="34" alt="" loading="eager"></span><div><strong>Анализ по направлениям</strong><p>Результаты по доступным категориям графических задач</p></div></div>
      <div class="benefit"><span class="benefit-icon"><img src="{{ asset('assets/icons/client/history.avif') }}" width="34" height="34" alt="" loading="eager"></span><div><strong>Время прохождения</strong><p>Продолжительность вашей попытки</p></div></div>
      <div class="benefit"><span class="benefit-icon"><img src="{{ asset('assets/icons/client/reports.avif') }}" width="34" height="34" alt="" loading="eager"></span><div><strong>Персональный отчёт</strong><p>Сводка, основанная на ваших ответах</p></div></div>
    </div>
  </div>
  <div class="paywall-right">
    <div class="report-illustration" aria-hidden="true"><div class="report-page"><div class="report-head"><span class="report-logo">IQ</span><div><b></b><b></b></div></div><div class="report-lines"><i></i><i></i></div><div class="report-bars"><b></b><b></b><b></b><b></b></div></div><div class="report-lock"><img src="{{ asset('assets/icons/admin/lock.avif') }}" width="48" height="48" alt=""></div></div>
    <div class="offer-card">
      <span class="pill">Специальное предложение</span>
      <div class="price-row"><strong>20 ₽</strong><del>499 ₽</del></div>
      <p>Первый доступ к персональному отчёту</p>
      <div class="subscription-note"><span class="subscription-icon" aria-hidden="true"><img src="{{ asset('assets/icons/admin/subscription.avif') }}" width="27" height="27" alt=""></span><span>После первого доступа — подписка <strong>499 ₽/месяц</strong> по запланированной тарифной модели. Сейчас оплата и продление демонстрационные: реальные списания отсутствуют.</span></div>
      <a class="btn btn-primary btn-block" href="{{ route('checkout', $attempt->uuid) }}">Открыть отчёт за 20 ₽</a>
      <p class="demo-note">Демо-режим: подтверждение без карты и реальных списаний.</p>
    </div>
  </div>
</section>
@endsection