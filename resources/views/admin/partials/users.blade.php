<div class="portal-table-wrap portal-table-stack-mobile">
  <table class="portal-table">
    <thead><tr><th>ID</th><th>Пользователь</th><th>E-mail</th><th>Роль</th><th>Создан</th></tr></thead>
    <tbody>
      @forelse($rows as $entry)
        <tr>
          <td data-label="ID">#{{ $entry->id }}</td>
          <td data-label="Имя">{{ $entry->name }}</td>
          <td data-label="E-mail">{{ $entry->email }}</td>
          <td data-label="Роль"><span @class(['portal-tag', 'portal-tag-green' => ! $entry->is_admin, 'portal-tag-neutral' => $entry->is_admin])>{{ $entry->is_admin ? 'Администратор' : 'Пользователь' }}</span></td>
          <td data-label="Создан">{{ \Carbon\Carbon::parse($entry->created_at)->format('d.m.Y') }}</td>
        </tr>
      @empty
        <tr><td class="portal-empty-row" colspan="5">Пользователей пока нет.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
