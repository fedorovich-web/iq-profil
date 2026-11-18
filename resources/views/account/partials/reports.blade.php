@if($subset->count())
<div class="portal-report-list">@foreach($subset as $attempt)
<div class="portal-report-row"><span class="report-file-icon"><img src="{{ asset('assets/icons/client/reports.avif') }}" width="25" height="25" alt=""></span><div><strong>Графический тест — {{ \Carbon\Carbon::parse($attempt->completed_at)->format('d.m.Y') }}</strong><small>Результат {{ $attempt->correct_count }} правильных решений</small></div><a href="{{ route('result',$attempt->uuid) }}">Открыть</a><a href="{{ route('account.export',$attempt->uuid) }}" download><img src="{{ asset('assets/icons/client/download.avif') }}" width="16" height="16" alt=""> CSV</a></div>
@endforeach</div>
@else<p class="portal-empty">Открытых отчётов пока нет.</p>@endif