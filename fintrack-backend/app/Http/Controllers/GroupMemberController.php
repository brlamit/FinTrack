<?php

namespace App\Http\Controllers;

use App\Mail\GroupInviteMail;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class GroupMemberController extends Controller
{
    /**
     * Invite a member to the group.
     */
    public function invite(Request $request, Group $group): JsonResponse|\Illuminate\Http\RedirectResponse
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
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if user already exists
        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Check if user is already a member of the group
            $existingMember = $group->members()->where('user_id', $user->id)->first();
            if ($existingMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is already a member of the group',
                ], 422);
            }

            // Add existing user to group
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $user->id,
                'role' => 'member',
                'joined_at' => now(),
            ]);

            // Send notification email to existing user
            try {
                Mail::to($user->email)->send(new GroupInviteMail($user, $group, null));
            } catch (\Exception $e) {
                \Log::error('Failed to send group invite email to existing member: ' . $e->getMessage());
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Existing member added to group successfully',
                    'data' => [
                        'user' => $user,
                    ],
                ]);
            }

            return redirect()->back()->with('success', 'Existing member added to group successfully.');
        }

        // Generate username and password for new user
        $username = $this->generateUsername($request->name);
        $password = $this->generatePassword();

        // Create new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $username,
            'password' => Hash::make($password),
            'phone' => $request->phone,
            'invited_by' => auth()->id(),
            'invited_at' => now(),
            'status' => 'invited',
        ]);

        // Add to group
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // Send email immediately to new member
        try {
            Mail::to($user->email)->send(new GroupInviteMail($user, $group, $password));
        } catch (\Exception $e) {
            \Log::error('Failed to send group invite email: ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'New member invited successfully',
                'data' => [
                    'user' => $user,
                    'username' => $username,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'New member invited successfully. Username: ' . $username);
    }

    /**
     * Generate unique username.
     */
    private function generateUsername(string $name): string
    {
        $parts = explode(' ', $name);
        $firstName = strtolower($parts[0]);
        $initial = count($parts) > 1 ? strtolower(substr($parts[1], 0, 1)) : '';
        $baseUsername = $firstName . ($initial ? '_' . $initial : '') . rand(1000, 9999);

        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Generate random password.
     */
    private function generatePassword(): string
    {
        return Str::random(8);
    }

    /**
     * Remove a member from the group.
     */
    public function remove(Request $request, Group $group, $memberId): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        \Log::info("RemoveMember: Attempting to remove member {$memberId} from group {$group->id}");
        
        // Check if requesting user is admin of the group
        $adminMember = $group->members()->where('user_id', auth()->id())->first();
        if (!$adminMember || $adminMember->role !== 'admin') {
            \Log::warning("RemoveMember: Unauthorized attempt by user " . auth()->id());
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Find the member in the group
        $member = $group->members()->find($memberId);
        if (!$member) {
            \Log::warning("RemoveMember: Member {$memberId} not found in group {$group->id}");
            return response()->json([
                'success' => false,
                'message' => 'Member not found in this group',
            ], 404);
        }

        // Cannot remove the group owner
        if ($member->user_id === $group->owner_id) {
            \Log::warning("RemoveMember: Attempted to remove group owner");
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove the group owner',
            ], 422);
        }

        // Cannot remove self as admin
        if ($member->user_id === auth()->id()) {
            \Log::warning("RemoveMember: User tried to remove themselves");
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove yourself from the group',
            ], 422);
        }

        // Delete the GroupMember record (removes user from group, doesn't delete user)
        $member->delete();
        \Log::info("RemoveMember: Successfully removed member {$memberId} from group {$group->id}");

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Member removed from group successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Member removed from group successfully.');
    }
}
