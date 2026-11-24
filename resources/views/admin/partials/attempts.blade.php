<div class="portal-table-wrap portal-table-stack-mobile">
  <table class="portal-table">
    <thead><tr><th>ID</th><th>Пользователь</th><th>Тест</th><th>Результат</th><th>Статус</th><th>Дата</th></tr></thead>
    <tbody>
      @forelse($rows as $attempt)
        <tr>
          <td data-label="ID">#{{ $attempt->id }}</td>
          <td data-label="Пользователь">{{ $attempt->user_name ?: 'Гость' }}</td>
          <td data-label="Тест">Графический тест</td>
          <td data-label="Результат">{{ $attempt->status === 'completed' ? $attempt->correct_count.' верно' : ($attempt->question_count ? $attempt->answered_count.'/'.$attempt->question_count : '—') }}</td>
          <td data-label="Статус"><span @class(['portal-tag', 'portal-tag-green' => $attempt->status === 'completed', 'portal-tag-neutral' => $attempt->status !== 'completed'])>{{ match($attempt->status) { 'completed' => 'Завершён', 'expired' => 'Не завершён', default => 'В процессе' } }}</span></td>
          <td data-label="Дата">{{ \Carbon\Carbon::parse($attempt->completed_at ?? $attempt->updated_at ?? $attempt->started_at)->format('d.m.Y H:i') }}</td>
        </tr>
      @empty
        <tr><td class="portal-empty-row" colspan="6">Попыток пока нет.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
