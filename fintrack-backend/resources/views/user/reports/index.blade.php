@extends('layouts.user')

@section('title', 'Reports')

@section('content')
<div class="container-fluid py-4">
    <h2 class="mb-4">Financial Reports</h2>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Generate Reports</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label small mb-0 text-white">From</label>
                            <input id="globalStart" type="date" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}">
                        </div>
                        <div class="col-auto">
                            <label class="form-label small mb-0 text-white">To</label>
                            <input id="globalEnd" type="date" class="form-control form-control-sm" value="{{ now()->endOfMonth()->toDateString() }}">
                        </div>
                        <div class="col-auto">
                            <button id="generateAll" class="btn btn-light btn-sm mt-2">Generate</button>
                        </div>
                        <div class="col-auto ms-auto">
                            <div class="btn-group mt-2">
                                <button class="btn btn-outline-light btn-sm bg-black text-white" data-bs-toggle="modal" data-bs-target="#monthlyReport"><i class="fas fa-calendar"></i> Configure</button>
                                <button class="btn btn-outline-light btn-sm bg-black text-white" data-bs-toggle="modal" data-bs-target="#reportSheetModal"><i class="fas fa-file-pdf"></i> Balance Sheet</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="row mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Total Expenses</div>
                            <div id="summaryExpenses" class="h4 mb-0">—</div>
                        </div>
                        <div><i class="fas fa-wallet fa-2x text-danger"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Transactions</div>
                            <div id="summaryCount" class="h4 mb-0">—</div>
                        </div>
                        <div><i class="fas fa-list fa-2x text-primary"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Net (Income − Expense)</div>
                            <div id="summaryNet" class="h4 mb-0">—</div>
                        </div>
                        <div><i class="fas fa-balance-scale fa-2x text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Spending Overview -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Monthly Spending Trend</h6>
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#monthlyReport">Configure</button>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="200"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Spending by Category</h6>
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#categoryReport">Configure</button>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Yearly Summary</h6>
                    <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#yearlyReport">Configure</button>
                </div>
                <div class="card-body">
                    <canvas id="yearlyChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modals')
<!-- Monthly Report Modal -->
<div class="modal fade" id="monthlyReport" tabindex="-1" aria-labelledby="monthlyReportLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="monthlyReportLabel">Monthly Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="monthlyReportForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Start date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Category Report Modal -->
<div class="modal fade" id="categoryReport" tabindex="-1" aria-labelledby="categoryReportLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryReportLabel">Category Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoryReportForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Start date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category (optional)</label>
                        <select name="category_id" class="form-select">
                            <option value="">All categories</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Yearly Report Modal -->
<div class="modal fade" id="yearlyReport" tabindex="-1" aria-labelledby="yearlyReportLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="yearlyReportLabel">Yearly Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="yearlyReportForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" value="{{ now()->year }}" min="2000" max="2100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Report Sheet Modal -->
<div class="modal fade" id="reportSheetModal" tabindex="-1" aria-labelledby="reportSheetModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportSheetModalLabel">Generate Report Sheet (PDF)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="GET" action="{{ route('user.reports.reportsheet.download') }}" target="_blank">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bs-start" class="form-label">Start date</label>
                        <input type="date" class="form-control" id="bs-start" name="start_date" value="{{ now()->startOfMonth()->toDateString() }}">
                    </div>
                    <div class="mb-3">
                        <label for="bs-end" class="form-label">End date</label>
                        <input type="date" class="form-control" id="bs-end" name="end_date" value="{{ now()->endOfMonth()->toDateString() }}">
                    </div>
                    <input type="hidden" name="format" value="pdf">
                    <p class="text-muted">The PDF will open in a new tab. Make sure you are signed in.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Download PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Helper to fetch report JSON from web endpoint (session-authenticated)
    async function fetchReport(params = {}) {
        const url = new URL('/reports/spending/data', window.location.origin);
        Object.keys(params).forEach(k => url.searchParams.set(k, params[k]));
        const resp = await fetch(url.toString(), { credentials: 'same-origin' });
        if (!resp.ok) throw new Error('Failed to fetch report: ' + resp.status);
        return resp.json();
    }

    // Create or update Chart.js chart
    let monthlyChart = null, categoryChart = null, yearlyChart = null;

    function createBarChart(ctx, labels, data, label) {
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{ label: label, data: data, backgroundColor: '#0d6efd' }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    function createPieChart(ctx, labels, data) {
        return new Chart(ctx, {
            type: 'pie',
            data: { labels: labels, datasets: [{ data: data, backgroundColor: labels.map((_,i)=>`hsl(${i*60 % 360} 70% 50%)`) }] },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    async function loadMonthly(startDate, endDate) {
        try {
            const json = await fetchReport({ group_by: 'month', start_date: startDate, end_date: endDate });
            const report = json.data.report;
            updateSummary(json.data.summary || {});
            const labels = report.map(r => `${r.year}-${String(r.month).padStart(2,'0')}`);
            const data = report.map(r => parseFloat(r.total));
            const ctx = document.getElementById('monthlyChart').getContext('2d');
            if (monthlyChart) monthlyChart.destroy();
            monthlyChart = createBarChart(ctx, labels, data, 'Expense by Month');
        } catch (err) {
            console.error(err);
        }
    }

    async function loadCategory(startDate, endDate) {
        try {
            const json = await fetchReport({ group_by: 'category', start_date: startDate, end_date: endDate });
            const report = json.data.report;
            updateSummary(json.data.summary || {});
            const labels = report.map(r => r.category.name || r.category);
            const data = report.map(r => parseFloat(r.total));
            const ctx = document.getElementById('categoryChart').getContext('2d');
            if (categoryChart) categoryChart.destroy();
            categoryChart = createPieChart(ctx, labels, data);
        } catch (err) { console.error(err); }
    }

    async function loadYearly(year) {
        try {
            const start = year + '-01-01';
            const end = year + '-12-31';
            const json = await fetchReport({ group_by: 'month', start_date: start, end_date: end });
            const report = json.data.report;
            updateSummary(json.data.summary || {});
            // Sum per month across year
            const labels = report.map(r => `${r.year}-${String(r.month).padStart(2,'0')}`);
            const data = report.map(r => parseFloat(r.total));
            const ctx = document.getElementById('yearlyChart').getContext('2d');
            if (yearlyChart) yearlyChart.destroy();
            yearlyChart = createBarChart(ctx, labels, data, `Year ${year}`);
        } catch (err) { console.error(err); }
    }

    function updateSummary(summary) {
        const fmt = (v)=> typeof v === 'number' ? v.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) : (v || '0.00');
        document.getElementById('summaryExpenses').textContent = summary.total_expenses ? fmt(summary.total_expenses) : '0.00';
        document.getElementById('summaryCount').textContent = summary.transaction_count ?? '0';
        // Net isn't provided by spending endpoint; fetch income quickly
        (async ()=>{
            try {
                const resp = await fetch('/api/reports/spending?start_date=' + (document.getElementById('globalStart').value) + '&end_date=' + (document.getElementById('globalEnd').value) + '&group_by=month', { credentials: 'same-origin' });
            } catch (_){}
            // Best-effort: compute net by subtracting expenses from summary.assets if available; left as placeholder
            document.getElementById('summaryNet').textContent = '—';
        })();
    }

    function downloadChartAsImage(chart, filename) {
        try {
            const url = chart.toBase64Image();
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
        } catch (err) { console.error('Export failed', err); }
    }

    // Wire export buttons
    function wireExportButtons() {
        document.querySelectorAll('.export-chart-btn').forEach(btn=>{
            btn.addEventListener('click', (e)=>{
                const target = e.currentTarget.getAttribute('data-target');
                const chart = ({monthlyChart,categoryChart,yearlyChart})[target];
                if (!chart) return alert('Chart not loaded yet');
                downloadChartAsImage(chart, target + '_' + new Date().toISOString().slice(0,10) + '.png');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Load default charts for current month / year
        const start = new Date(); start.setDate(1);
        const end = new Date(); end.setMonth(end.getMonth()+1); end.setDate(0);
        const s = start.toISOString().slice(0,10);
        const e = end.toISOString().slice(0,10);
        loadMonthly(s,e);
        loadCategory(s,e);
        loadYearly(new Date().getFullYear());

        wireExportButtons();

        // Monthly modal: on submit, refresh monthly chart
        const monthlyForm = document.getElementById('monthlyReportForm');
        if (monthlyForm) monthlyForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            const s = this.querySelector('[name="start_date"]').value;
            const e = this.querySelector('[name="end_date"]').value;
            loadMonthly(s,e);
            bootstrap.Modal.getInstance(document.getElementById('monthlyReport')).hide();
        });

        // Category modal
        const categoryForm = document.getElementById('categoryReportForm');
        if (categoryForm) categoryForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            const s = this.querySelector('[name="start_date"]').value;
            const e = this.querySelector('[name="end_date"]').value;
            loadCategory(s,e);
            bootstrap.Modal.getInstance(document.getElementById('categoryReport')).hide();
        });

        // Yearly modal
        const yearlyForm = document.getElementById('yearlyReportForm');
        if (yearlyForm) yearlyForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            const y = this.querySelector('[name="year"]').value;
            loadYearly(y);
            bootstrap.Modal.getInstance(document.getElementById('yearlyReport')).hide();
        });
    });
</script>
@endpush
