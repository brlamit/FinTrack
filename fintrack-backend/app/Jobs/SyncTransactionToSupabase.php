<?php

namespace App\Jobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class SyncTransactionToSupabase implements ShouldQueue
{
    public function __construct(public $transaction) {}

    public function handle()
    {
        DB::connection('pgsql_supabase')
            ->table('transactions')
            ->insert($this->transaction->toArray());

        // mark as synced
        $this->transaction->update([
            'synced_to_supabase' => true
        ]);
    }

    public function failed(\Throwable $e)
    {
        logger()->error('Supabase sync failed', [
            'error' => $e->getMessage()
        ]);
    }
}
