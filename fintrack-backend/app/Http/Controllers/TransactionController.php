<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Jobs\SyncTransactionToSupabase;


class TransactionController extends Controller
{
    /**
     * Display a listing of the user's transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'nullable|in:income,expense',
            'search' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Transaction::where('user_id', auth()->id())
            ->whereNull('group_id')
            ->with(['category', 'receipt']);

        // Apply filters
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Store a newly created transaction.
     */
  public function store(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'category_id' => 'required|exists:categories,id',
        'amount' => 'nullable|required_without:receipt_id|numeric|min:0.01|max:999999.99',
        'description' => 'nullable|string|max:255',
        'transaction_date' => 'required|date|before_or_equal:today',
        'type' => 'required|in:income,expense',
        'receipt_id' => 'nullable|exists:receipts,id',
        'tags' => 'nullable|array',
        'tags.*' => 'string|max:50',
        'is_recurring' => 'boolean',
        'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:daily,weekly,monthly,yearly',
        'recurring_end_date' => 'nullable|date|after:transaction_date',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Verify category
    $category = Category::where('id', $request->category_id)
        ->where(function ($query) {
            $query->where('user_id', auth()->id())
                  ->orWhereNull('user_id');
        })
        ->first();

    if (!$category) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid category',
        ], 422);
    }

// Verify receipt belongs to user if provided
$receipt = null;
if ($request->receipt_id) {
    $receipt = auth()->user()->receipts()->find($request->receipt_id);
    if (!$receipt) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid receipt',
        ], 422);
    }

    // If the receipt is still being processed, block transaction creation
    if (!$receipt->processed) {
        return response()->json([
            'success' => false,
            'message' => 'Receipt is still being processed. Please wait a moment and try again.',
        ], 409);
    }

    // Ensure parsed_data has a valid estimated_total
    $parsed = $receipt->parsed_data ?? [];
    $estimatedTotal = $parsed['estimated_total'] ?? null;

    if ($estimatedTotal === null && !$request->has('amount')) {
        return response()->json([
            'success' => false,
            'message' => 'Receipt OCR did not extract a valid total. Please enter an amount manually.',
            'errors' => [
                'amount' => ['Amount is required or must be derivable from the attached receipt.'],
            ],
        ], 422);
    }
}


    // Determine amount
    $amount = $request->input('amount');
    $numericAmount = $amount !== null && $amount !== ''
        ? (float) $amount
        : 0.0;

    if ($numericAmount <= 0.0 && $receipt) {
        $parsed = $receipt->parsed_data ?? [];
        $estimatedTotal = $parsed['estimated_total'] ?? 0.0;
        if ($estimatedTotal > 0.0) {
            $numericAmount = $estimatedTotal;
        }
    }

    if ($numericAmount <= 0.0 && empty($receipt?->parsed_data['requires_manual_amount'])) {
        return response()->json([
            'success' => false,
            'message' => 'Amount is required when a valid total cannot be read from the receipt.',
            'errors' => [
                'amount' => ['Amount is required or must be derivable from the attached receipt.'],
            ],
        ], 422);
    }

    // Create transaction
    DB::beginTransaction();
    try {
        $transaction = Transaction::create([
            'user_id' => auth()->id(),
            'category_id' => $request->category_id,
            'amount' => $numericAmount,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            'type' => $request->type,
            'receipt_id' => $request->receipt_id,
            'tags' => $request->tags,
            'is_recurring' => $request->is_recurring ?? false,
            'recurring_frequency' => $request->recurring_frequency,
            'recurring_end_date' => $request->recurring_end_date,
            'synced_to_supabase' => false,
        ]);

        DB::commit();

        // Sync to Supabase in background (optional)
        // SyncTransactionToSupabase::dispatch($transaction);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction->load(['category', 'receipt']),
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        logger()->error('Transaction store failed', ['error' => $e->getMessage()]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create transaction',
        ], 500);
    }
}

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction): JsonResponse
    {
        // Check if transaction belongs to authenticated user
        if ($transaction->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction->load(['category', 'receipt']),
        ]);
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        // Check if transaction belongs to authenticated user
        if ($transaction->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:categories,id',
            'amount' => 'sometimes|numeric|min:0.01|max:999999.99',
            'description' => 'nullable|string|max:255',
            'transaction_date' => 'sometimes|date|before_or_equal:today',
            'type' => 'sometimes|in:income,expense',
            'receipt_id' => 'nullable|exists:receipts,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:daily,weekly,monthly,yearly',
            'recurring_end_date' => 'nullable|date|after:transaction_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify category belongs to user or is system category if provided
        if ($request->has('category_id')) {
            $category = Category::where('id', $request->category_id)
                ->where(function ($query) {
                    $query->where('user_id', auth()->id())
                          ->orWhereNull('user_id');
                })
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid category',
                ], 422);
            }
        }

        // Verify receipt belongs to user if provided
        if ($request->receipt_id) {
            $receipt = auth()->user()->receipts()->find($request->receipt_id);
            if (!$receipt) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid receipt',
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $transaction->update($request->only([
                'category_id',
                'amount',
                'description',
                'transaction_date',
                'type',
                'receipt_id',
                'tags',
                'is_recurring',
                'recurring_frequency',
                'recurring_end_date',
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully',
                'data' => $transaction->load(['category', 'receipt']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update transaction',
            ], 500);
        }
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Transaction $transaction): JsonResponse
    {
        // Check if transaction belongs to authenticated user
        if ($transaction->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ]);
    }

    /**
     * Get transaction statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->endOfMonth()->toDateString();

        // Keep statistics consistent with the user dashboard by excluding
        // group-based transactions (those with a non-null group_id).
        $baseQuery = Transaction::where('user_id', auth()->id())
            ->whereNull('group_id');

        $stats = [
            'total_income' => (clone $baseQuery)
                ->income()
                ->dateRange($startDate, $endDate)
                ->sum('amount'),

            'total_expenses' => (clone $baseQuery)
                ->expense()
                ->dateRange($startDate, $endDate)
                ->sum('amount'),

            'transaction_count' => (clone $baseQuery)
                ->dateRange($startDate, $endDate)
                ->count(),

            'category_breakdown' => (clone $baseQuery)
                ->expense()
                ->dateRange($startDate, $endDate)
                ->with('category')
                ->selectRaw('category_id, SUM(amount) as total')
                ->groupBy('category_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'category' => $item->category,
                        'total' => $item->total,
                    ];
                }),
        ];

        $stats['net_income'] = $stats['total_income'] - $stats['total_expenses'];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}