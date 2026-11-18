@if($subset->count())
<div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Дата</th><th>Тест</th><th>Результат</th><th>Статус</th><th></th></tr></thead><tbody>
@foreach($subset as $attempt)
<tr><td>{{ \Carbon\Carbon::parse($attempt->started_at)->format('d.m.Y') }}</td><td>Графический тест</td><td>@if($attempt->status==='completed'){{ $attempt->correct_count }} верно @else — @endif</td><td><span @class(['portal-tag','portal-tag-green'=>$attempt->status==='completed','portal-tag-neutral'=>$attempt->status!=='completed'])>{{ $attempt->status==='completed'?'Завершён':'В процессе' }}</span></td><td>@if($attempt->status==='completed')<a href="{{ route($attempt->payment_status==='succeeded'?'result':'paywall',$attempt->uuid) }}">Смотреть →</a>@else<a href="{{ route('attempt.resume',$attempt->uuid) }}">Продолжить</a>@endif</td></tr>
@endforeach</tbody></table></div>
@else<p class="portal-empty">Пока нет прохождений. Начните тест на главной.</p>@endif