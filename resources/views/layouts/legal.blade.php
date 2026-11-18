@extends('layouts.app')
@section('private', 'yes')
@section('page-class','legal-page')
@section('content')
<article class="legal-content">
    <p class="legal-state">ТЕСТОВАЯ РЕДАКЦИЯ · Не является юридически утверждённым документом действующего оператора</p>
    <h1>@yield('legal-title')</h1>
    <p class="legal-date">Редакция от 18 ноября 2026 года</p>
    @yield('legal-body')
</article>
@endsection
