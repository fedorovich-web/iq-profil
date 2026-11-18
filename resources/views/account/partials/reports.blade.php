@if($subset->count())
<div class="portal-report-list">@foreach($subset as $attempt)
<div class="portal-report-row"><span class="report-file-icon">▤</span><div><strong>Графический тест — {{ \Carbon\Carbon::parse($attempt->completed_at)->format('d.m.Y') }}</strong><small>Результат {{ $attempt->correct_count }} из 25</small></div><a href="{{ route('result',$attempt->uuid) }}">Открыть</a><a href="{{ route('account.export',$attempt->uuid) }}" download>↓ CSV</a></div>
@endforeach</div>
@else<p class="portal-empty">Открытых отчётов пока нет.</p>@endif