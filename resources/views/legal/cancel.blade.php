@extends('layouts.legal')
@section('title','Отмена подписки — IQ.Профиль')
@section('private','yes')
@section('legal-title','Отмена демонстрационной подписки')
@section('legal-body')
<p>Здесь можно отменить демо-продление для попыток, принадлежащих вашему аккаунту или текущей браузерной сессии. Реальных платежей, платёжных карт и будущих списаний нет.</p>
@if(session('status'))<p role="status" class="form-success">{{ session('status') }}</p>@endif
@if($subscriptions->count())
<div class="portal-table-wrap"><table class="portal-table">
<thead><tr><th>Дата активации</th><th>Попытка</th><th>Статус</th></tr></thead>
<tbody>@foreach($subscriptions as $s)
<tr><td>{{ \Carbon\Carbon::parse($s->activated_at)->format('d.m.Y H:i') }}</td>
<td>{{ mb_substr($s->uuid,0,8) }}…</td><td><strong>{{ match ($s->status) { 'active' => 'Активна (демо)', 'cancelled' => 'Отменена (демо)', 'superseded' => 'Заменена новым демо-продлением', default => 'Неизвестный статус' } }}</strong></td></tr>
@endforeach</tbody></table></div>
@if($subscriptions->contains('status','active'))
<form method="POST" action="{{ route('subscription.cancel.perform') }}" class="cancel-form">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
<label><input type="checkbox" name="confirm_cancel" value="1" required> Подтверждаю отмену всех активных демонстрационных продлений в моём аккаунте или сессии.</label>
<button class="btn btn-primary" type="submit">Подтвердить отмену</button></form>
@else<p class="form-success">Все демонстрационные продления уже отменены.</p>@endif
@else
<p class="portal-empty">В текущей сессии или аккаунте нет виртуальных подписок, доступных для отмены.</p>
@endif
<p><a href="{{ auth()->check() ? route('account.home',['section'=>'subscription']) : route('landing') }}">← Вернуться {{ auth()->check() ? 'в кабинет' : 'на главную' }}</a></p>
@endsection
