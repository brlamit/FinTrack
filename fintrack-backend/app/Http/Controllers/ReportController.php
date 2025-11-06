<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get spending report.
     */
    public function spending(Request $request): JsonResponse
    {
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
                    'total_expenses' => $query->sum('amount'),
                    'transaction_count' => $query->count(),
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
}