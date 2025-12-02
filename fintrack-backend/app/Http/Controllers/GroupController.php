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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:family,friends',
            'budget_limit' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Group created successfully',
                'data' => $group->load(['owner', 'members']),
            ], 201);
        }

        return redirect()->route('user.group', $group)
            ->with('success', 'Group created successfully!');
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
    public function destroy(Request $request, Group $group)
    {
        // Check if user is owner of the group
        if ($group->owner_id !== auth()->id()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return redirect()->back()->withErrors(['error' => 'You are not allowed to delete this group.']);
        }

        $group->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Group deleted successfully',
            ]);
        }

        return redirect()->route('user.groups')->with('success', 'Group deleted successfully.');
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
        if (! $group->members()->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Group not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'split_type' => 'required|in:equal,custom,percentage',
            'splits' => 'required|array|min:1',
            'splits.*.user_id' => 'required|exists:users,id',
            'splits.*.amount' => 'nullable|numeric|min:0',
            'splits.*.percent' => 'nullable|numeric|min:0|max:100',
            'receipt' => 'nullable|file|image|max:5120',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return redirect()->back()->withErrors($validator)->withInput();
        }

        $members = $group->members()->with('user')->get();
        $memberIds = $members->pluck('user_id')->all();

        $handleError = function (string $message) use ($request) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->back()->withErrors(['split' => $message])->withInput();
        };

        try {
            $receiptId = null;
            if ($request->hasFile('receipt')) {
                $file = $request->file('receipt');
                $filename = time() . '_' . $file->getClientOriginalName();
                $disk = env('FILESYSTEM_DISK', config('filesystems.default'));
                $path = $file->storeAs('receipts/' . auth()->id(), $filename, $disk);

                // Build a public URL for storage entry when possible (include bucket and encode)
                $pathToSave = $path;
                $generated = null;
                try {
                    $generated = \Storage::disk($disk)->url($path);
                } catch (\Throwable $e) {
                    $generated = null;
                }

                $diskConfig = config("filesystems.disks.{$disk}", []);
                $diskUrl = $diskConfig['url'] ?? null;
                $bucket = $diskConfig['bucket'] ?? env('SUPABASE_PUBLIC_BUCKET');

                if (!empty($generated) && !empty($bucket) && strpos($generated, trim($bucket, '/')) === false) {
                    $generated = null;
                }

                if (empty($generated) && !empty($diskUrl)) {
                    $encodedKey = implode('/', array_map('rawurlencode', explode('/', $path)));
                    if (!empty($bucket)) {
                        $generated = rtrim($diskUrl, '/') . '/' . trim($bucket, '/') . '/' . ltrim($encodedKey, '/');
                    } else {
                        $generated = rtrim($diskUrl, '/') . '/' . ltrim($encodedKey, '/');
                    }
                }

                if (!empty($generated)) {
                    $pathToSave = $generated;
                }

                $receipt = \App\Models\Receipt::create([
                    'user_id' => auth()->id(),
                    'filename' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'path' => $pathToSave,
                    'size' => $file->getSize(),
                    'processed' => false,
                ]);
                $receiptId = $receipt->id;
            }

            $type = $request->input('type', 'expense');
            $categoryId = $request->input('category_id');

            if (! $categoryId) {
                if ($type === 'income') {
                    $other = \App\Models\Category::firstOrCreate(
                        ['user_id' => null, 'name' => 'Other Income', 'type' => 'income'],
                        ['icon' => '💰', 'color' => '#38B2AC']
                    );
                } else {
                    $other = \App\Models\Category::firstOrCreate(
                        ['user_id' => null, 'name' => 'Other Expense', 'type' => 'expense'],
                        ['icon' => '📦', 'color' => '#9E9E9E']
                    );
                }
                $categoryId = $other->id;
            }

            $splitType = $request->input('split_type');
            $amount = (float) $request->input('amount');
            $rawSplits = $request->input('splits', []);
            $computedSplits = [];

            $ensureMember = function ($userId) use ($memberIds, $handleError) {
                if (! in_array($userId, $memberIds, true)) {
                    throw new \RuntimeException('Invalid member included in split.');
                }
            };

            if ($splitType === 'equal') {
                $count = max($members->count(), 1);
                $base = round($amount / $count, 2);
                $runningTotal = 0.0;

                foreach ($members as $index => $member) {
                    $portion = $index === $count - 1
                        ? round($amount - $runningTotal, 2)
                        : $base;

                    $runningTotal += $portion;
                    $computedSplits[] = [
                        'user_id' => $member->user_id,
                        'amount' => max($portion, 0),
                    ];
                }
            } elseif ($splitType === 'percentage') {
                $percentTotal = 0.0;
                foreach ($rawSplits as $index => $split) {
                    $userId = (int) ($split['user_id'] ?? 0);
                    $ensureMember($userId);

                    if (! isset($split['percent'])) {
                        throw new \RuntimeException('Percentage value missing for one of the members.');
                    }

                    $percent = (float) $split['percent'];
                    $percentTotal += $percent;
                    $computedSplits[] = [
                        'user_id' => $userId,
                        'amount' => round(($percent / 100) * $amount, 2),
                        'percent' => $percent,
                    ];
                }

                if ($percentTotal <= 0 || abs($percentTotal - 100) > 0.01) {
                    throw new \RuntimeException('Percentage splits must add up to 100%.');
                }

                $assigned = array_sum(array_column($computedSplits, 'amount'));
                if (abs($assigned - $amount) > 0.01 && count($computedSplits) > 0) {
                    $diff = round($amount - $assigned, 2);
                    $computedSplits[count($computedSplits) - 1]['amount'] += $diff;
                }
            } else {
                $customSplits = [];
                foreach ($rawSplits as $split) {
                    $userId = (int) ($split['user_id'] ?? 0);
                    $ensureMember($userId);

                    if (! isset($split['amount'])) {
                        throw new \RuntimeException('Amount missing for one of the members.');
                    }

                    $customSplits[] = [
                        'user_id' => $userId,
                        'amount' => round((float) $split['amount'], 2),
                    ];
                }

                $assigned = array_sum(array_column($customSplits, 'amount'));
                if (abs($assigned - $amount) > 0.01) {
                    throw new \RuntimeException('Custom split amounts must add up to the total.');
                }

                $computedSplits = $customSplits;
            }

            if (empty($computedSplits)) {
                return $handleError('No split data provided.');
            }

            foreach ($computedSplits as $split) {
                \App\Models\Transaction::create([
                    'user_id' => $split['user_id'],
                    'group_id' => $group->id,
                    'category_id' => $categoryId,
                    'amount' => $split['amount'],
                    'description' => $request->input('description'),
                    'transaction_date' => now()->toDateString(),
                    'type' => $type,
                    'receipt_id' => $receiptId,
                ]);
            }

            if ($type === 'expense' && ! is_null($group->budget_limit)) {
                $group->budget_limit = max(0, round($group->budget_limit - $amount, 2));
                $group->save();
            }

            $message = $type === 'income'
                ? 'Income recorded and split successfully'
                : 'Expense recorded and split successfully';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\RuntimeException $validationException) {
            return $handleError($validationException->getMessage());
        } catch (\Exception $e) {
            \Log::error('Failed to create group transaction split: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create split transaction',
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to create split transaction');
        }
    }
}