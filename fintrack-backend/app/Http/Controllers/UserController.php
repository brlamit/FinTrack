<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Group;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Show user dashboard
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        return view('user.dashboard', [
            'totalBalance' => '$0.00',
            'thisMonth' => '$0.00',
            'categoryCount' => 0,
            'budgetCount' => Budget::where('user_id', $user->id)
                ->count(),
            'recentTransactions' => Transaction::where('user_id', $user->id)
                ->latest()
                ->limit(5)
                ->get(),
            'activeBudgets' => Budget::where('user_id', $user->id)
                ->where('is_active', true)
                ->limit(3)
                ->get(),
        ]);
    }

    /**
     * Show user profile
     */
    public function profile()
    {
        $user = auth()->user();
        
        return view('user.profile', [
            'totalExpenses' => '$0.00',
            'thisMonthExpenses' => '$0.00',
            'groupCount' => $user->groups()->count(),
            'recentTransactions' => Transaction::where('user_id', $user->id)
                ->latest()
                ->limit(5)
                ->get(),
            'userGroups' => $user->groups()
                ->with('members')
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }

    /**
     * Show edit profile form
     */
    public function edit()
    {
        return view('user.edit');
    }

    /**
     * Update user profile
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:5120'], // 5MB
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $user->update($validated);

        return redirect()->route('user.profile')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Show security settings
     */
    public function security()
    {
        return view('user.security');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ], [
            'new_password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'The provided password does not match our records.']);
        }

        // Update password
        $user->update(['password' => Hash::make($validated['new_password'])]);

        return back()->with('password_updated', 'Password updated successfully!');
    }

    /**
     * Show preferences
     */
    public function preferences()
    {
        return view('user.preferences');
    }

    /**
     * Update preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = auth()->user();

        $preferences = [
            'notify_email' => $request->has('notify_email'),
            'notify_budget' => $request->has('notify_budget'),
            'notify_group' => $request->has('notify_group'),
            'notify_weekly' => $request->has('notify_weekly'),
            'theme' => $request->input('theme', 'light'),
            'currency' => $request->input('currency', 'USD'),
            'language' => $request->input('language', 'en'),
            'show_profile' => $request->has('show_profile'),
            'share_stats' => $request->has('share_stats'),
        ];

        $user->update([
            'preferences' => $preferences,
        ]);

        return back()->with('preferences_updated', 'Preferences updated successfully!');
    }

    /**
     * Sign out all sessions
     */
    public function logoutAll()
    {
        // This would require a tokens table to track sessions
        // For now, just log out the current session
        auth('sanctum')->user()->tokens()->delete();
        auth()->guard('sanctum')->logout();

        return redirect('/')->with('success', 'You have been signed out from all sessions.');
    }

    /**
     * Disable 2FA
     */
    public function disable2FA()
    {
        $user = auth()->user();
        $user->update(['two_factor_enabled' => false]);

        return back()->with('success', '2FA has been disabled.');
    }

    /**
     * Show enable 2FA
     */
    public function enable2FA()
    {
        return view('user.enable-2fa');
    }

    /**
     * List user transactions
     */
    public function transactions()
    {
        $transactions = Transaction::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('user.transactions.index', compact('transactions'));
    }

    /**
     * Create transaction
     */
    public function createTransaction()
    {
        return view('user.transactions.create');
    }

    /**
     * Store transaction
     */
    public function storeTransaction(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:categories,id',
            'date' => 'required|date',
        ]);

        Transaction::create([
            'user_id' => auth()->id(),
            ...$validated,
        ]);

        return redirect()->route('user.dashboard')
            ->with('success', 'Transaction added successfully!');
    }

    /**
     * List user budgets
     */
    public function budgets()
    {
        $budgets = Budget::where('user_id', auth()->id())
            ->with('category')
            ->paginate(10);

        return view('user.budgets.index', compact('budgets'));
    }

    /**
     * List user groups
     */
    public function groups()
    {
        $groups = auth()->user()
            ->groups()
            ->with('members')
            ->paginate(10);

        return view('user.groups.index', compact('groups'));
    }

    /**
     * Show single group
     */
    public function group(Group $group)
    {
        // Check if user is member of group
        if (!$group->members()->where('user_id', auth()->id())->exists()) {
            abort(403, 'Unauthorized');
        }

    $group->load(['members.user', 'owner', 'sharedTransactions.user']);

        return view('user.groups.show', compact('group'));
    }

    /**
     * List user reports
     */
    public function reports()
    {
        return view('user.reports.index');
    }
}
