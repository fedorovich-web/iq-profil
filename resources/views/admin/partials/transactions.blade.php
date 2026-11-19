<div class="portal-table-wrap portal-table-stack-mobile">
  <table class="portal-table">
    <thead><tr><th>ID</th><th>Пользователь</th><th>Сумма (демо)</th><th>Статус</th><th>Дата</th></tr></thead>
    <tbody>
      @forelse($rows as $payment)
        <tr>
          <td data-label="ID">#{{ $payment->id }}</td>
          <td data-label="Пользователь">{{ $payment->user_name ?: 'Гость' }}</td>
          <td data-label="Сумма">{{ number_format($payment->amount_kopecks / 100, 0, ' ', ' ') }} ₽</td>
          <td data-label="Статус"><span class="portal-tag portal-tag-green">{{ $payment->status === 'succeeded' ? 'Успешно (демо)' : $payment->status }}</span></td>
          <td data-label="Дата">{{ \Carbon\Carbon::parse($payment->confirmed_at)->format('d.m.Y H:i') }}</td>
        </tr>
      @empty
        <tr><td class="portal-empty-row" colspan="5">Демо-транзакций пока нет.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
