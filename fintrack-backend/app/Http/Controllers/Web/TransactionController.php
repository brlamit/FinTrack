<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Show transaction details (web view)
     */
    public function show(Transaction $transaction)
    {
        $user = auth()->user();

        // If transaction belongs to a group, check that user is a member of that group
        if ($transaction->group_id) {
            $isMember = $transaction->group->members()->where('user_id', $user->id)->exists();
            if (! $isMember) {
                abort(403, 'Unauthorized');
            }
        } else {
            // Non-group transactions can only be viewed by owner
            if ($transaction->user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }
        }

        $transaction->load(['user', 'category', 'receipt', 'group']);

        return view('user.transactions.show', [
            'transaction' => $transaction,
        ]);
    }
}
