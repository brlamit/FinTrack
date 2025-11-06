<?php

namespace App\Http\Controllers;

use App\Models\SyncToken;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SyncController extends Controller
{
    /**
     * Sync transactions.
     */
    public function transactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'last_sync_timestamp' => 'nullable|date',
            'transactions' => 'required|array',
            'transactions.*.id' => 'nullable|integer',
            'transactions.*.category_id' => 'required|exists:categories,id',
            'transactions.*.amount' => 'required|numeric|min:0.01',
            'transactions.*.description' => 'nullable|string|max:255',
            'transactions.*.transaction_date' => 'required|date',
            'transactions.*.type' => 'required|in:income,expense',
            'transactions.*.local_id' => 'nullable|string|max:255',
            'transactions.*.updated_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deviceId = $request->device_id;
        $lastSyncTimestamp = $request->last_sync_timestamp ? \Carbon\Carbon::parse($request->last_sync_timestamp) : null;

        // Get or create sync token for this device
        $syncToken = SyncToken::firstOrCreate(
            ['user_id' => auth()->id(), 'device_id' => $deviceId],
            ['token' => SyncToken::generateToken()]
        );

        $syncResults = [
            'created' => [],
            'updated' => [],
            'conflicts' => [],
            'server_changes' => [],
        ];

        // Process incoming transactions
        foreach ($request->transactions as $transactionData) {
            try {
                if (isset($transactionData['id'])) {
                    // Update existing transaction
                    $transaction = Transaction::where('user_id', auth()->id())
                        ->find($transactionData['id']);

                    if ($transaction) {
                        // Check for conflicts
                        if ($lastSyncTimestamp && $transaction->updated_at > $lastSyncTimestamp) {
                            $syncResults['conflicts'][] = [
                                'local' => $transactionData,
                                'server' => $transaction,
                            ];
                            continue;
                        }

                        $transaction->update([
                            'category_id' => $transactionData['category_id'],
                            'amount' => $transactionData['amount'],
                            'description' => $transactionData['description'],
                            'transaction_date' => $transactionData['transaction_date'],
                            'type' => $transactionData['type'],
                        ]);

                        $syncResults['updated'][] = $transaction;
                    }
                } else {
                    // Create new transaction
                    $transaction = Transaction::create([
                        'user_id' => auth()->id(),
                        'category_id' => $transactionData['category_id'],
                        'amount' => $transactionData['amount'],
                        'description' => $transactionData['description'],
                        'transaction_date' => $transactionData['transaction_date'],
                        'type' => $transactionData['type'],
                    ]);

                    $syncResults['created'][] = $transaction;
                }
            } catch (\Exception $e) {
                // Log error but continue processing
                continue;
            }
        }

        // Get server changes since last sync
        if ($lastSyncTimestamp) {
            $serverChanges = Transaction::where('user_id', auth()->id())
                ->where('updated_at', '>', $lastSyncTimestamp)
                ->with('category')
                ->get();

            $syncResults['server_changes'] = $serverChanges;
        }

        // Update sync timestamp
        $syncToken->updateLastSync();

        return response()->json([
            'success' => true,
            'data' => [
                'sync_token' => $syncToken->token,
                'last_sync_at' => $syncToken->last_sync_at,
                'results' => $syncResults,
            ],
        ]);
    }
}