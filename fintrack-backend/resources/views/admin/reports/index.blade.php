@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Reports</h2>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="{{ route('admin.transactions') }}">Back to Transactions</a>
    <a class="btn btn-outline-primary" href="{{ route('admin.reports.export.csv', request()->query()) }}">
      <i class="fas fa-file-csv me-1"></i> Export CSV
    </a>
    <a class="btn btn-outline-danger" href="{{ route('admin.reports.export.pdf', request()->query()) }}">
      <i class="fas fa-file-pdf me-1"></i> Export PDF
    </a>
  </div>
</div>

<form method="GET" class="mb-4">
  <div class="row g-2">
    <div class="col-md-2">
      <select name="type" class="form-control">
        <option value="">All Types</option>
        <option value="income" {{ request('type')==='income' ? 'selected' : '' }}>Income</option>
        <option value="expense" {{ request('type')==='expense' ? 'selected' : '' }}>Expense</option>
      </select>
    </div>
    <div class="col-md-3">
      <select name="group_id" class="form-control">
        <option value="">All Groups</option>
        @foreach($groups as $group)
          <option value="{{ $group->id }}" {{ (string)request('group_id')===(string)$group->id ? 'selected' : '' }}>{{ $group->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <input type="number" name="user_id" class="form-control" placeholder="User ID" value="{{ request('user_id') }}">
    </div>
    <div class="col-md-2">
      <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
    </div>
    <div class="col-md-2">
      <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
    </div>
    @can('view_personal_transactions')
    <div class="col-md-4">
      <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" value="1" id="includePersonal" name="include_personal" {{ request('include_personal') ? 'checked' : '' }}>
        <label class="form-check-label" for="includePersonal">Include personal transactions (requires reason)</label>
      </div>
      <input type="text" name="reason" class="form-control mt-2" placeholder="Reason for inclusion" value="{{ request('reason') }}">
    </div>
    @endcan
    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" class="btn btn-primary w-100">Run Report</button>
    </div>
  </div>
</form>

@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Total Transactions</div>
        <div class="h4 mb-0">{{ number_format($report['totals']['count']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Total Amount</div>
        <div class="h4 mb-0">${{ number_format($report['totals']['amount'], 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Income</div>
        <div class="h4 mb-0 text-success">${{ number_format($report['totals']['income'], 2) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="text-muted small">Expense</div>
        <div class="h4 mb-0 text-danger">${{ number_format($report['totals']['expense'], 2) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">By Category</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead><tr><th>Category</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            @forelse ($report['by_category'] as $row)
              <tr>
                <td>{{ optional(App\Models\Category::find($row->category_id))->name ?? 'Uncategorized' }}</td>
                <td class="text-end">${{ number_format((float) $row->total, 2) }}</td>
              </tr>
            @empty
              <tr><td colspan="2" class="text-muted">No data</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Top Users</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead><tr><th>User</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            @forelse ($report['by_user'] as $row)
              <tr>
                <td>{{ optional(App\Models\User::find($row->user_id))->name ?? ('User #'.$row->user_id) }}</td>
                <td class="text-end">${{ number_format((float) $row->total, 2) }}</td>
              </tr>
            @empty
              <tr><td colspan="2" class="text-muted">No data</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Top Groups</div>
      <div class="card-body p-0">
        <table class="table mb-0">
          <thead><tr><th>Group</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            @forelse ($report['by_group'] as $row)
              <tr>
                <td>{{ optional(App\Models\Group::find($row->group_id))->name ?? ('Group #'.$row->group_id) }}</td>
                <td class="text-end">${{ number_format((float) $row->total, 2) }}</td>
              </tr>
            @empty
              <tr><td colspan="2" class="text-muted">No data</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<hr class="my-4" />

<div class="card shadow-sm">
  <div class="card-header bg-white fw-semibold">Detailed Transactions</div>
  <div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Type</th>
          <th>Group</th>
          <th>Amount</th>
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
            <td>
              <span class="badge bg-{{ $t->type==='income' ? 'success' : 'danger' }}">{{ ucfirst($t->type) }}</span>
            </td>
            <td>{{ $t->group?->name ?? '—' }}</td>
            <td>${{ number_format((float)$t->amount, 2) }}</td>
            <td>{{ $t->category->name ?? 'N/A' }}</td>
            <td>{{ $t->description }}</td>
            <td>{{ optional($t->transaction_date ?? $t->created_at)->format('M d, Y H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-muted">No transactions found</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white">
    {{ $transactions->links() }}
  </div>
</div>
@endsection
