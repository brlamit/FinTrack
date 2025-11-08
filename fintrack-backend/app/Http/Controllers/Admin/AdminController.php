<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_groups' => Group::count(),
            'total_transactions' => Transaction::count(),
            'total_transaction_amount' => Transaction::sum('amount'),
            'recent_users' => User::latest()->take(5)->get(),
            'recent_transactions' => Transaction::with('user')->latest()->take(5)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
        }

        $users = $query->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function transactions(Request $request)
    {
    $query = Transaction::with(['user', 'category', 'group']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20);
        $groups = Group::orderBy('name')->get(['id', 'name']);

        return view('admin.transactions.index', compact('transactions', 'groups'));
    }

    public function impersonate(User $user)
    {
        Auth::login($user);
        return redirect('/')->with('success', 'Now impersonating ' . $user->name);
    }
}
