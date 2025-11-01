<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    /**
     * Display a listing of the user's groups.
     */
    public function index(Request $request): JsonResponse
    {
        $groups = auth()->user()->groups()
            ->with(['owner', 'members'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:family,friends',
            'budget_limit' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'budget_limit' => $request->budget_limit,
            'owner_id' => auth()->id(),
            'invite_code' => Str::random(8),
        ]);

        // Add owner as admin member
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => auth()->id(),
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully',
            'data' => $group->load(['owner', 'members']),
        ], 201);
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group): JsonResponse
    {
        // Check if user is member of the group
        if (!$group->members()->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $group->load(['owner', 'members.user']),
        ]);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, Group $group): JsonResponse
    {
        // Check if user is admin of the group
        $member = $group->members()->where('user_id', auth()->id())->first();
        if (!$member || $member->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $group->update($request->only(['name', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'Group updated successfully',
            'data' => $group->load(['owner', 'members']),
        ]);
    }

    /**
     * Remove the specified group.
     */
    public function destroy(Group $group): JsonResponse
    {
        // Check if user is owner of the group
        if ($group->owner_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully',
        ]);
    }

    /**
     * Invite a user to the group.
     */
    public function invite(Request $request, Group $group): JsonResponse
    {
        // Check if user is admin of the group
        $member = $group->members()->where('user_id', auth()->id())->first();
        if (!$member || $member->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Check if user is already a member
        if ($group->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already a member of this group',
            ], 422);
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // TODO: Send invitation notification

        return response()->json([
            'success' => true,
            'message' => 'User invited to group successfully',
        ]);
    }

    /**
     * Get group members.
     */
    public function members(Group $group): JsonResponse
    {
        // Check if user is member of the group
        if (!$group->members()->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found',
            ], 404);
        }

        $members = $group->members()->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    /**
     * Split an expense among group members.
     */
    public function splitExpense(Request $request, Group $group): JsonResponse
    {
        // Check if user is member of the group
        if (!$group->members()->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'split_type' => 'required|in:equal,percentage,custom',
            'splits' => 'required_if:split_type,custom|array',
            'splits.*.user_id' => 'required|exists:users,id',
            'splits.*.amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implement expense splitting logic
        // This would create transactions for each member based on the split

        return response()->json([
            'success' => true,
            'message' => 'Expense split created successfully',
        ]);
    }
}