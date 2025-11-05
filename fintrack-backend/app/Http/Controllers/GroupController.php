<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Mail\GroupInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
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

        // Send invitation email to existing user
        try {
            $invitationMail = new GroupInvitationMail($user, $group, auth()->user());
            Mail::to($user->email)->send($invitationMail);
        } catch (\Exception $e) {
            \Log::error('Failed to send group invitation email: ' . $e->getMessage());
        }

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
    public function splitExpense(Request $request, Group $group)
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
            'description' => 'required_without:category_id|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'split_type' => 'required|in:equal,percentage,custom',
            'splits' => 'required_if:split_type,custom|array',
            'splits.*.user_id' => 'required|exists:users,id',
            'splits.*.amount' => 'required|numeric|min:0',
            'receipt' => 'nullable|file|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Implement expense splitting logic: create a receipt (if provided) and create transactions
        try {
            // handle receipt upload if present
            $receiptId = null;
            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('receipts/' . auth()->id(), $filename, config('filesystems.default'));

                $receipt = \App\Models\Receipt::create([
                    'user_id' => auth()->id(),
                    'filename' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'processed' => false,
                ]);
                $receiptId = $receipt->id;
            }

            // Determine category: if none provided, use or create a system 'Other Expense' category
            $categoryId = $request->category_id;
            if (!$categoryId) {
                $other = \App\Models\Category::where('name', 'Other Expense')->where('type', 'expense')->first();
                if (!$other) {
                    // create a system category (user_id null)
                    $other = \App\Models\Category::create([
                        'name' => 'Other Expense',
                        'icon' => '📦',
                        'color' => '#9E9E9E',
                        'type' => 'expense',
                        'user_id' => null,
                    ]);
                }
                $categoryId = $other->id;
            }

            // Build splits list depending on split_type
            $splits = [];
            if ($request->split_type === 'equal') {
                $members = $group->members()->with('user')->get();
                $count = $members->count() ?: 1;
                $per = round($request->amount / $count, 2);
                foreach ($members as $m) {
                    $splits[] = [
                        'user_id' => $m->user_id,
                        'amount' => $per,
                    ];
                }
                // Adjust last member for rounding differences
                $totalAssigned = array_sum(array_column($splits, 'amount'));
                if (abs($totalAssigned - $request->amount) > 0.001) {
                    $diff = $request->amount - $totalAssigned;
                    $splits[count($splits) - 1]['amount'] += $diff;
                }
            } elseif ($request->split_type === 'percentage') {
                // Expecting splits with percentage values (not yet implemented fully)
                foreach ($request->input('splits', []) as $s) {
                    // If incoming split has 'percent', convert to amount
                    if (isset($s['percent'])) {
                        $amt = round(($s['percent'] / 100) * $request->amount, 2);
                        $splits[] = ['user_id' => $s['user_id'], 'amount' => $amt];
                    } else {
                        $splits[] = $s;
                    }
                }
            } else {
                // custom
                $splits = $request->input('splits', []);
            }

            // Validate that splits sum equals amount (tolerate tiny float rounding)
            $sum = 0;
            foreach ($splits as $s) {
                $sum += (float) ($s['amount'] ?? 0);
            }
            if (abs($sum - (float) $request->amount) > 0.01) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Split amounts do not sum to total amount',
                    ], 422);
                }
                return redirect()->back()->with('error', 'Split amounts do not sum to total amount');
            }

            // Create transactions per split (attach to group)
            foreach ($splits as $split) {
                $userId = $split['user_id'];
                $amt = $split['amount'];
                \App\Models\Transaction::create([
                    'user_id' => $userId,
                    'group_id' => $group->id,
                    'category_id' => $categoryId ?? 1,
                    'amount' => $amt,
                    'description' => $request->description,
                    'transaction_date' => now()->toDateString(),
                    'type' => 'expense',
                    'receipt_id' => $receiptId,
                ]);
            }

            // Deduct from group budget if set
            if (!is_null($group->budget_limit)) {
                $group->budget_limit = max(0, round($group->budget_limit - (float) $request->amount, 2));
                $group->save();
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Expense split created successfully',
                ]);
            }

            return redirect()->back()->with('success', 'Expense added and split successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to create split expense: ' . $e->getMessage());
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create split expense',
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to create split expense');
        }
    }
}