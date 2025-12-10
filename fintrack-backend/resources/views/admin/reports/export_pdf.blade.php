<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Report</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h1 { font-size: 18px; margin-bottom: 0; }
    .muted { color: #666; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #ddd; padding: 6px 8px; }
    th { background: #f3f3f3; text-align: left; }
    .text-right { text-align: right; }
  </style>
</head>
<body>
  <h1>Admin Transactions Report</h1>
  <div class="muted">Generated: {{ $generated_at->format('Y-m-d H:i:s') }}</div>
  <div class="muted">Filters: {{ json_encode($filters) }}</div>

  <h3>Totals</h3>
  <table>
    <tr><th>Total Count</th><td class="text-right">{{ number_format($totals['count']) }}</td></tr>
    <tr><th>Total Amount</th><td class="text-right">${{ number_format($totals['amount'], 2) }}</td></tr>
    <tr><th>Income</th><td class="text-right">${{ number_format($totals['income'], 2) }}</td></tr>
    <tr><th>Expense</th><td class="text-right">${{ number_format($totals['expense'], 2) }}</td></tr>
  </table>

  <h3>Transactions</h3>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Type</th>
        <th>Group</th>
        <th class="text-right">Amount</th>
        <th>Category</th>
        <th>Description</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      @forelse($transactions as $t)
      <tr>
        <td>{{ $t->id }}</td>
        <td>{{ $t->user->name ?? '—' }}</td>
        <td>{{ ucfirst($t->type) }}</td>
        <td>{{ $t->group?->name ?? '—' }}</td>
        <td class="text-right">${{ number_format((float)$t->amount, 2) }}</td>
        <td>{{ $t->category->name ?? 'N/A' }}</td>
        <td>{{ $t->description }}</td>
        <td>{{ optional($t->transaction_date ?? $t->created_at)->format('Y-m-d H:i') }}</td>
      </tr>
      @empty
      <tr><td colspan="8">No transactions found</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
