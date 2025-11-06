<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the user's categories.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:income,expense',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Category::query();

        // Include system categories and user's custom categories
        $query->where(function ($q) {
            $q->whereNull('user_id') // System categories
              ->orWhere('user_id', auth()->id()); // User's categories
        });

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $categories = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'type' => 'required|in:income,expense',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify parent category belongs to user or is system category
        if ($request->parent_id) {
            $parent = Category::where('id', $request->parent_id)
                ->where(function ($query) {
                    $query->where('user_id', auth()->id())
                          ->orWhereNull('user_id');
                })
                ->first();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parent category',
                ], 422);
            }
        }

        $category = Category::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'icon' => $request->icon,
            'color' => $request->color,
            'type' => $request->type,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category->load('parent'),
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): JsonResponse
    {
        // Check if category belongs to user or is system category
        if ($category->user_id && $category->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category->load(['parent', 'children']),
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        // Check if category belongs to user
        if ($category->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'type' => 'sometimes|in:income,expense',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify parent category belongs to user or is system category
        if ($request->parent_id) {
            $parent = Category::where('id', $request->parent_id)
                ->where(function ($query) {
                    $query->where('user_id', auth()->id())
                          ->orWhereNull('user_id');
                })
                ->first();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parent category',
                ], 422);
            }
        }

        $category->update($request->only([
            'name',
            'icon',
            'color',
            'type',
            'parent_id',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category->load(['parent', 'children']),
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): JsonResponse
    {
        // Check if category belongs to user
        if ($category->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        // Check if category has transactions
        if ($category->transactions()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing transactions',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}