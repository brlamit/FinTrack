<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BudgetController extends Controller
{
    /**
     * Display a listing of the user's budgets.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'active_only' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Budget::where('user_id', auth()->id())
            ->with(['category']);

        if ($request->boolean('active_only', false)) {
            $query->active();
        }

        $budgets = $query->orderBy('created_at', 'desc')->get();

        // Add current spending to each budget
        $budgets->transform(function ($budget) {
            return $budget->load('category');
        });

        return response()->json([
            'success' => true,
            'data' => $budgets,
        ]);
    }

    /**
     * Store a newly created budget.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'period' => 'required|in:weekly,monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'alert_thresholds' => 'nullable|array',
            'alert_thresholds.*' => 'numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify category belongs to user or is system category if provided
        if ($request->category_id) {
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

        $budget = Budget::create([
            'user_id' => auth()->id(),
            'category_id' => $request->category_id,
            'name' => $request->name,
            'amount' => $request->amount,
            'period' => $request->period,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => true,
            'alert_thresholds' => $request->alert_thresholds ?? [50, 75, 90],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Budget created successfully',
            'data' => $budget->load('category'),
        ], 201);
    }

    /**
     * Display the specified budget.
     */
    public function show(Budget $budget): JsonResponse
    {
        // Check if budget belongs to authenticated user
        if ($budget->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Budget not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $budget->load('category'),
        ]);
    }

    /**
     * Update the specified budget.
     */
    public function update(Request $request, Budget $budget): JsonResponse
    {
        // Check if budget belongs to authenticated user
        if ($budget->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Budget not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'period' => 'sometimes|in:weekly,monthly,quarterly,yearly',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_active' => 'boolean',
            'alert_thresholds' => 'nullable|array',
            'alert_thresholds.*' => 'numeric|min:0|max:100',
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

        $budget->update($request->only([
            'category_id',
            'name',
            'amount',
            'period',
            'start_date',
            'end_date',
            'is_active',
            'alert_thresholds',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Budget updated successfully',
            'data' => $budget->load('category'),
        ]);
    }

    /**
     * Remove the specified budget.
     */
    public function destroy(Budget $budget): JsonResponse
    {
        // Check if budget belongs to authenticated user
        if ($budget->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Budget not found',
            ], 404);
        }

        $budget->delete();

        return response()->json([
            'success' => true,
            'message' => 'Budget deleted successfully',
        ]);
    }
}