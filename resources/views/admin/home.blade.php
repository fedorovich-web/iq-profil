@extends('layouts.portal')
@section('title','Панель администратора — IQ.Профиль')
@section('portal-heading','Панель администратора')
@section('sidebar')
<nav class="portal-sidebar-nav" aria-label="Администрирование">
@foreach([['overview','overview','Обзор'],['users','users','Пользователи'],['attempts','test-attempts','Попытки тестов'],['transactions','demo-transactions','Демо-транзакции'],['reports','analytics','Отчёты'],['audit','audit-log','Журнал действий']] as [$key,$icon,$label])
<a href="{{ route('admin.home',['section'=>$key]) }}" @class(['active'=>$section===$key]) @if($section===$key) aria-current="page" @endif>
@if(file_exists(public_path('assets/icons/admin/'.$icon.'.avif')))
<img src="{{ asset('assets/icons/admin/'.$icon.'.avif') }}" alt="" width="28" height="28">
@else<span class="nav-fallback" aria-hidden="true">{{ ['overview'=>'⌂','users'=>'♙','attempts'=>'▥','transactions'=>'▣','reports'=>'▤','audit'=>'◷'][$key] }}</span>@endif
<span>{{ $label }}</span></a>
@endforeach
</nav>
@endsection
@section('content')
<header class="admin-head"><div><h1>{{ ['overview'=>'Обзор системы','users'=>'Пользователи','attempts'=>'Попытки тестов','transactions'=>'Демо-транзакции','reports'=>'Отчёты','audit'=>'Журнал действий'][$section] }}</h1><p>Актуальные данные из базы данных. Реальные платежи отсутствуют.</p></div></header>
@if($section==='overview' || $section==='reports')
<div class="admin-stats">
 <article class="admin-stat"><span class="admin-stat-icon">♙</span><div><span>Пользователи</span><strong>{{ number_format($stats['users'],0,' ',' ') }}</strong></div></article>
 <article class="admin-stat"><span class="admin-stat-icon">▥</span><div><span>Попытки тестов</span><strong>{{ number_format($stats['attempts'],0,' ',' ') }}</strong></div></article>
 <article class="admin-stat"><span class="admin-stat-icon">✓</span><div><span>Завершены</span><strong>{{ number_format($stats['completed'],0,' ',' ') }}</strong></div></article>
 <article class="admin-stat"><span class="admin-stat-icon">▣</span><div><span>Демо-платежи</span><strong>{{ number_format($stats['payments'],0,' ',' ') }}</strong></div></article>
</div>
@endif
@if($section==='overview')
<div class="admin-sections">
 <section class="portal-panel"><div class="portal-panel-heading"><h2>Последние попытки</h2><a href="{{ route('admin.home',['section'=>'attempts']) }}">Все попытки →</a></div>@include('admin.partials.attempts',['rows'=>$attempts->take(5)])</section>
 <section class="portal-panel"><div class="portal-panel-heading"><h2>Демо-транзакции</h2><a href="{{ route('admin.home',['section'=>'transactions']) }}">Все транзакции →</a></div><p class="admin-demo-disclaimer">ДЕМО · списания не выполняются</p>@include('admin.partials.transactions',['rows'=>$payments->take(5)])</section>
 <section class="portal-panel"><div class="portal-panel-heading"><h2>Последние пользователи</h2><a href="{{ route('admin.home',['section'=>'users']) }}">Все пользователи →</a></div>@include('admin.partials.users',['rows'=>$users->take(5)])</section>
 <section class="portal-panel"><div class="portal-panel-heading"><h2>Состояние системы</h2><span class="portal-tag portal-tag-green">Работает</span></div>
 <p>Воронка: тест → доступ к результату → демонстрационная оплата → индивидуальный отчёт.</p><p>Завершённых прохождений: <strong>{{ $stats['completed'] }}</strong>. Все результаты рассчитываются на сервере.</p><a href="{{ route('admin.home',['section'=>'reports']) }}" class="portal-main-action">Смотреть отчёты →</a></section>
</div>
@elseif($section==='users')
<section class="portal-panel portal-full"><form method="get" action="{{ route('admin.home') }}" class="admin-filter"><input type="hidden" name="section" value="users"><label for="admin-search">Поиск по имени или e-mail</label><input id="admin-search" name="search" value="{{ $search }}" placeholder="Найти пользователя..."><button type="submit" class="btn btn-primary">Найти</button></form>@include('admin.partials.users',['rows'=>$users]){{ $users->links() }}</section>
@elseif($section==='attempts')
<section class="portal-panel portal-full">@include('admin.partials.attempts',['rows'=>$attempts]){{ $attempts->links() }}</section>
@elseif($section==='transactions')
<section class="portal-panel portal-full"><p class="admin-demo-disclaimer">Все платежи здесь виртуальные. Никакого эквайринга и регулярных списаний нет.</p>@include('admin.partials.transactions',['rows'=>$payments]){{ $payments->links() }}</section>
@elseif($section==='reports')
<section class="portal-panel portal-full"><h2>Операционные метрики</h2><p>Данные рассчитаны по реальным записям SQLite. Эти значения не являются нормативной оценкой IQ и не включают реальные доходы.</p><div class="admin-report-metrics"><div><strong>{{ $stats['completed'] }}</strong><span>Завершённых попыток</span></div><div><strong>{{ $stats['attempts'] ? round($stats['completed'] / $stats['attempts'] * 100,1) : 0 }}%</strong><span>Доля завершённых</span></div><div><strong>{{ $stats['payments'] }}</strong><span>Демо-подтверждений</span></div></div></section>
@elseif($section==='audit')
<section class="portal-panel portal-full"><h2>Журнал действий администраторов</h2>
<div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Дата</th><th>Администратор</th><th>Действие</th></tr></thead><tbody>
@forelse($audit as $item)<tr><td>{{ $item->created_at }}</td><td>{{ $item->email }}</td><td>{{ $item->action }}</td></tr>@empty<tr><td colspan="3">Событий ещё нет.</td></tr>@endforelse
</tbody></table></div>{{ $audit->links() }}</section>
@endif
@endsection
@section('mobile-nav')
<nav class="portal-mobile-nav" aria-label="Разделы администрирования">
@foreach([['overview','⌂','Обзор'],['users','♙','Люди'],['attempts','▥','Попытки'],['transactions','▣','Демо'],['reports','▤','Отчёты']] as [$key,$icon,$label])
<a href="{{ route('admin.home',['section'=>$key]) }}" @class(['active'=>$section===$key])><span aria-hidden="true">{{ $icon }}</span><small>{{ $label }}</small></a>
@endforeach
</nav>
@endsection