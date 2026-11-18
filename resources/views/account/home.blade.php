@extends('layouts.portal')
@section('title', 'Личный кабинет — IQ.Профиль')
@section('portal-heading','Личный кабинет')
@section('sidebar')
<nav class="portal-sidebar-nav" aria-label="Разделы личного кабинета">
@foreach([
 ['overview','home','Главная'],['history','history','История прохождений'],
 ['reports','reports','Мои отчёты'],['subscription','layers','Подписка'],
 ['profile','profile','Профиль'],['security','security','Безопасность'],
 ['support','support','Поддержка']
] as [$key,$icon,$label])
<a href="{{ route('account.home', ['section'=>$key]) }}" @class(['active'=>$section===$key]) @if($section===$key) aria-current="page" @endif>
 <img src="{{ asset('assets/icons/client/'.$icon.'.avif') }}" alt="" width="28" height="28">
 <span>{{ $label }}</span>
</a>
@endforeach
</nav>
@endsection
@section('content')
@if($section==='overview')
<div class="account-overview">
 <div class="account-hero">
   <div class="account-hero-copy">
      <span>Ваш последний результат</span>
      @if($latest)
       <div class="account-score">{{ $latest->correct_count }} <small>/ 25 верно</small></div>
       <span class="account-score-label">{{ round($latest->correct_count / 25 * 100) }}% правильных ответов</span>
       <p>Дата: {{ \Carbon\Carbon::parse($latest->completed_at)->format('d.m.Y') }}</p>
       <div class="hero-account-actions"><a class="account-white-button" href="{{ route('result', $latest->uuid) }}">Открыть отчёт →</a><a class="account-ghost-button" href="{{ route('account.export', $latest->uuid) }}" title="Скачать CSV-отчёт"><img src="{{ asset('assets/icons/client/download.avif') }}" width="17" height="17" alt=""> CSV</a></div>
      @else
       <div class="account-score account-score-empty">Пока нет результата</div>
       <p>Пройдите тест и откройте свой персональный разбор.</p>
       <form action="{{ route('attempt.start') }}" method="post"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button class="account-white-button" type="submit">Пройти тест →</button></form>
      @endif
   </div>
   @if(file_exists(public_path('assets/illustrations/iq-result-brain-banner-right.avif')))
   <img class="account-brain" src="{{ asset('assets/illustrations/iq-result-brain-banner-right.avif') }}" alt="" width="450" height="350">
   @else <div class="account-brain-placeholder" aria-hidden="true">◈</div>@endif
 </div>
 <div class="portal-panel subscription-panel">
   <h2>Подписка</h2>
   <p class="sub-price">499 ₽ <small>/ месяц</small></p>
   <span class="portal-tag portal-tag-neutral">Демо-модель</span>
   <p>Денежные списания и подписка не активируются.</p>
   <a class="portal-main-action" href="{{ route('subscription.cancel') }}">Отменить демо-подписку →</a>
 </div>
 <nav class="mobile-shortcuts" aria-label="Быстрая навигация">
 @foreach([['history','history','История прохождений'],['reports','reports','Мои отчёты'],['subscription','layers','Подписка'],['profile','profile','Профиль']] as [$key,$icon,$label])
 <a href="{{ route('account.home',['section'=>$key]) }}">
 <img src="{{ asset('assets/icons/client/'.$icon.'.avif') }}" alt="" width="36" height="36">
 <strong>{{ $label }}</strong><span aria-hidden="true">›</span></a>
 @endforeach
 </nav>
 <section class="portal-panel">
  <div class="portal-panel-heading"><h2>История прохождений</h2><a href="{{ route('account.home',['section'=>'history']) }}">Вся история →</a></div>
  @include('account.partials.history',['subset'=>$attempts->take(3)])
 </section>
 <section class="portal-panel">
  <div class="portal-panel-heading"><h2>Мои отчёты</h2><a href="{{ route('account.home',['section'=>'reports']) }}">Все отчёты →</a></div>
  @include('account.partials.reports',['subset'=>$attempts->filter(fn($a) => $a->status==='completed' && $a->payment_status==='succeeded')->take(3)])
 </section>
 <section class="portal-panel">
  <h2>Рекомендуем продолжить</h2>
  <div class="portal-recommendation"><span class="portal-rec-icon"><img src="{{ asset('assets/icons/client/layers.avif') }}" width="27" height="27" alt=""></span><div><strong>Графические задания</strong><p>Новая попытка из 25 вопросов на закономерности.</p></div><form method="post" action="{{ route('attempt.start') }}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit">Пройти →</button></form></div>
 </section>
 <section class="portal-panel">
  <h2>Профиль и безопасность</h2>
  @foreach([['profile','Личные данные'],['security','Сменить пароль'],['support','Поддержка']] as [$key,$label])
   <a class="portal-setting-link" href="{{ route('account.home',['section'=>$key]) }}"><span>{{ $label }}</span><span aria-hidden="true">›</span></a>
  @endforeach
 </section>
</div>
@elseif($section==='history')
<section class="portal-panel portal-full"><h1>История прохождений</h1><p>Ваши попытки сохраняются в базе данных и доступны после входа.</p>@include('account.partials.history',['subset'=>$attempts])</section>
@elseif($section==='reports')
<section class="portal-panel portal-full"><h1>Мои отчёты</h1><p>Открытые отчёты и экспорт персональной сводки в CSV.</p>@include('account.partials.reports',['subset'=>$attempts->filter(fn($a)=>$a->status==='completed' && $a->payment_status==='succeeded')])</section>
@elseif($section==='subscription')
<section class="portal-panel portal-full"><h1>Подписка</h1><p class="lead">20 ₽ за первый доступ, далее 499 ₽ в месяц — только демонстрационная модель.</p><span class="portal-tag portal-tag-neutral">Подписка не активна</span><p>Ни один пользователь не получает реальную платную подписку; банковских реквизитов и автоматических списаний нет. Доступ к результатам выдаётся только после подтверждения отдельной демо-оплаты.</p><p>Активных демонстрационных продлений: <strong>{{ $demoActive ?? 0 }}</strong>.</p><a class="portal-main-action" href="{{ route('subscription.cancel') }}">Отменить демо-подписку →</a></section>
@elseif($section==='profile')
<section class="portal-panel portal-full"><h1>Личные данные</h1><p>Изменения применяются к вашему аккаунту.</p>
<form method="post" action="{{ route('account.profile') }}" class="portal-form"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH">
<label>Имя<input name="name" value="{{ old('name',$user->name) }}" required maxlength="100"></label>
<label>E-mail<input name="email" value="{{ old('email',$user->email) }}" type="email" required></label>
@foreach($errors->all() as $error)<p class="form-error" role="alert">{{ $error }}</p>@endforeach
<button type="submit" class="btn btn-primary">Сохранить изменения</button></form></section>
@elseif($section==='security')
<section class="portal-panel portal-full"><h1>Безопасность</h1><p>Для изменения пароля потребуется действующий пароль.</p>
<form method="post" action="{{ route('account.password') }}" class="portal-form"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH">
<label>Текущий пароль<input name="current_password" type="password" autocomplete="current-password" required></label>
<label>Новый пароль<input name="password" type="password" autocomplete="new-password" minlength="10" required></label>
<label>Повторите пароль<input name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required></label>
@foreach($errors->all() as $error)<p class="form-error" role="alert">{{ $error }}</p>@endforeach
<button type="submit" class="btn btn-primary">Изменить пароль</button></form></section>
@elseif($section==='support')
<section class="portal-panel portal-full"><h1>Поддержка</h1><p>Этот раздел содержит справку по прохождению теста, демо-платежам и учётной записи.</p><details><summary>Где посмотреть результат?</summary><p>Откройте «Мои отчёты» и выберите результат подтверждённой демо-оплаты.</p></details><details><summary>Почему нельзя увидеть IQ 124?</summary><p>Задания не нормированы, поэтому сервис не рассчитывает психологический IQ-балл. Отчёт показывает число правильных ответов.</p></details><details><summary>Как отменить подписку?</summary><p>Отменять нечего: в этом MVP не создаётся настоящая подписка и не списываются деньги.</p></details></section>
@endif
@endsection
@section('mobile-nav')
<nav class="portal-mobile-nav" aria-label="Мобильная навигация">
@foreach([['overview','home','Главная'],['history','history','История'],['reports','reports','Отчёты'],['subscription','layers','Подписка'],['profile','profile','Профиль']] as [$key,$icon,$label])
<a href="{{ route('account.home',['section'=>$key]) }}" @class(['active'=>$section===$key]) @if($section===$key) aria-current="page" @endif>
<img src="{{ asset('assets/icons/client/'.$icon.'.avif') }}" alt="" width="26" height="26">
<small>{{ $label }}</small></a>
@endforeach
</nav>
@endsection