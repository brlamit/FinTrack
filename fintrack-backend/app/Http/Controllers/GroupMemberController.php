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
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Generate username and password
        $username = $this->generateUsername($request->name);
        $password = $this->generatePassword();

        // Create user
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

        // Send email (queued)
        Mail::to($user->email)->queue(new GroupInviteMail($user, $group, $password));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Member invited successfully',
                'data' => [
                    'user' => $user,
                    'username' => $username,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Member invited successfully. Username: ' . $username);
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
}
