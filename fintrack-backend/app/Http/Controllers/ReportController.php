<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Get spending report.
     */
    public function spending(Request $request): JsonResponse
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'group_by' => 'nullable|in:category,date,month',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();
        $groupBy = $request->group_by ?? 'category';

        $query = Transaction::where('user_id', auth()->id())
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $report = [];

    //     // Capture totals before we modify the query with GROUP BY / ORDER BY clauses
        $totalExpenses = (clone $query)->sum('amount');
        $totalIncome = (clone $query)->where('type', 'income')->sum('amount');

        $overallIncome = Transaction::query()
        ->where('user_id', auth()->id())
        ->whereNull('group_id')
        ->where('type', 'income')
        ->sum('amount');

    $overallExpense = Transaction::query()
        ->where('user_id', auth()->id())
        ->whereNull('group_id')
        ->where('type', 'expense')
        ->sum('amount');

    $totalNet = $overallIncome - $overallExpense;
        $transactionCount = (clone $query)->count();

        switch ($groupBy) {
            case 'category':
                $report = $query->with('category')
                    ->selectRaw('category_id, SUM(amount) as total')
                    ->groupBy('category_id')
                    ->orderBy('total', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'category' => $item->category,
                            'total' => $item->total,
                            'percentage' => 0, // Will be calculated below
                        ];
                    });
                break;

            case 'date':
                $report = $query->selectRaw('transaction_date::date as date, SUM(amount) as total')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'date' => $item->date,
                            'total' => $item->total,
                        ];
                    });
                break;

            case 'month':
                $report = $query->selectRaw('EXTRACT(YEAR FROM transaction_date) as year, EXTRACT(MONTH FROM transaction_date) as month, SUM(amount) as total')
                    ->groupBy('year', 'month')
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'year' => $item->year,
                            'month' => $item->month,
                            'total' => $item->total,
                        ];
                    });
                break;
        }

        // Calculate percentages for category grouping
        if ($groupBy === 'category' && $report->isNotEmpty()) {
            $totalAmount = $report->sum('total');
            $report = $report->map(function ($item) use ($totalAmount) {
                $item['percentage'] = $totalAmount > 0 ? round(($item['total'] / $totalAmount) * 100, 2) : 0;
                return $item;
            });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'group_by' => $groupBy,
                'report' => $report,
                'summary' => [
                    'total_expenses' => $totalExpenses,
                     'total_income' => $totalIncome,
                    'net_total' => $totalNet,
                    'transaction_count' => $transactionCount,
                ],
            ],
        ]);
    }

    /**
     * Export report.
     */
    public function export(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:pdf,csv,json',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'type' => 'required|in:spending,income,all',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implement report export functionality
        // This would generate PDF, CSV, or JSON files and return download URLs

        return response()->json([
            'success' => true,
            'message' => 'Export functionality will be implemented',
            'data' => [
                'format' => $request->get('format'),
                'download_url' => 'https://example.com/download/report.pdf',
            ],
        ]);
    }

    /**
     * Generate a simple balance sheet and return PDF download (or JSON).
     */
    public function reportsheet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'format' => 'nullable|in:pdf,json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now()->endOfMonth();

        $baseQuery = Transaction::where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        $totalIncome = (clone $baseQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $baseQuery)->where('type', 'expense')->sum('amount');

        // For a simple balance-sheet-like view we still compute totals, but
        // also compute opening balance (before period) and per-category statement
        // lines so the PDF can show opening/debit/credit/closing like a bank
        $assets = $totalIncome;
        $liabilities = $totalExpense;
        $equity = $assets - $liabilities;

        // Opening balance before the period (income - expense prior to start)
        $openingIncome = (clone $baseQuery)->where('transaction_date', '<', $startDate)->where('type', 'income')->sum('amount');
        $openingExpense = (clone $baseQuery)->where('transaction_date', '<', $startDate)->where('type', 'expense')->sum('amount');
        $opening_balance = (float) $openingIncome - (float) $openingExpense;

        // Period totals grouped by category and type
        $periodGrouped = (clone $baseQuery)
            ->with('category')
            ->selectRaw('category_id, type, SUM(amount) as total')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('category_id', 'type')
            ->get();

        // Opening grouped by category before the period
        $openingGrouped = (clone $baseQuery)
            ->with('category')
            ->selectRaw('category_id, type, SUM(amount) as total')
            ->where('transaction_date', '<', $startDate)
            ->groupBy('category_id', 'type')
            ->get();

        // Build per-account (per-category) statement lines
        $perAccount = [];
        // seed openings
        foreach ($openingGrouped as $row) {
            $catName = $row->category?->name ?? 'Uncategorized';
            if (!isset($perAccount[$catName])) {
                $perAccount[$catName] = ['opening' => 0.0, 'debit' => 0.0, 'credit' => 0.0];
            }
            $amt = (float) $row->total;
            if ($row->type === 'income' || strtolower($row->type) === 'credit') {
                $perAccount[$catName]['opening'] += $amt;
            } else {
                $perAccount[$catName]['opening'] -= $amt;
            }
        }

        // add period activity
        foreach ($periodGrouped as $row) {
            $catName = $row->category?->name ?? 'Uncategorized';
            if (!isset($perAccount[$catName])) {
                $perAccount[$catName] = ['opening' => 0.0, 'debit' => 0.0, 'credit' => 0.0];
            }
            $amt = (float) $row->total;
            if ($row->type === 'income' || strtolower($row->type) === 'credit') {
                $perAccount[$catName]['credit'] += $amt;
            } else {
                $perAccount[$catName]['debit'] += $amt;
            }
        }

        // Convert to list for view and compute totals
        $perAccountList = [];
        $totalDebits = 0.0; $totalCredits = 0.0;
        foreach ($perAccount as $cat => $vals) {
            $opening = (float) ($vals['opening'] ?? 0.0);
            $debit = (float) ($vals['debit'] ?? 0.0);
            $credit = (float) ($vals['credit'] ?? 0.0);
            $closing = $opening + $credit - $debit;
            $perAccountList[] = [
                'category' => $cat,
                'opening' => $opening,
                'debit' => $debit,
                'credit' => $credit,
                'closing' => $closing,
            ];
            $totalDebits += $debit;
            $totalCredits += $credit;
        }

        $data = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'assets' => (float) $assets,
            'liabilities' => (float) $liabilities,
            'equity' => (float) $equity,
            'by_category' => $periodGrouped->map(function ($row) {
                return [
                    'category' => $row->category?->name ?? 'Uncategorized',
                    'type' => $row->type,
                    'total' => (float) $row->total,
                ];
            })->values(),
            'opening_balance' => (float) $opening_balance,
            'per_account' => $perAccountList,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'currency' => env('APP_CURRENCY', 'USD'),
            // Transactions list for detailed statement (ordered by date asc)
            'transactions' => [],
        ];

        // Build transaction list with running balance for the statement view
        try {
            $txs = Transaction::where('user_id', auth()->id())
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->orderBy('transaction_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            $running = (float) $opening_balance;
            $txList = [];

            foreach ($txs as $tx) {
                $amt = (float) $tx->amount;
                $deposit = 0.0; $withdrawal = 0.0;
                if (strtolower($tx->type) === 'income' || strtolower($tx->type) === 'credit') {
                    $deposit = $amt;
                    $running += $amt;
                } else {
                    $withdrawal = $amt;
                    $running -= $amt;
                }

                $txList[] = [
                    'date' => Carbon::parse($tx->transaction_date)->toDateString(),
                    'description' => $tx->description ?? $tx->category?->name ?? 'Transaction',
                    'withdrawal' => $withdrawal,
                    'deposit' => $deposit,
                    'balance' => $running,
                ];
            }

            $data['transactions'] = $txList;
        } catch (\Throwable $e) {
            // If anything goes wrong building the transactions list, leave it empty.
            $data['transactions'] = [];
        }

        if ($request->get('format') === 'json') {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        // Render PDF using a Blade view
        try {
            $pdf = Pdf::loadView('user.reports.report_sheet_pdf', $data);
            $filename = sprintf('report_sheet_%s_to_%s.pdf', $startDate->toDateString(), $endDate->toDateString());
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            // If the client expects JSON, return JSON error (API clients)
            if ($request->wantsJson() || $request->get('format') === 'json') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate PDF. Is the PDF package installed?',
                    'error' => $e->getMessage(),
                ], 500);
            }

            // For browser requests, fall back to rendering the HTML view so users
            // can view/save the report manually. Also pass the error message so
            // the UI can show a friendly banner.
            $dataWithError = array_merge($data, ['pdf_error' => $e->getMessage()]);
            return response()->view('user.reports.report_sheet_pdf', $dataWithError, 200);
        }
    }
}