<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Budget;
use App\Models\User;
use Carbon\Carbon;

class BudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sensible sample budgets for existing users but do not overwrite existing budgets
        $users = User::all();

        foreach ($users as $user) {
            if ($user->budgets()->exists()) {
                continue; // don't create duplicates for users who already have budgets
            }

            // Monthly groceries
            Budget::create([
                'user_id' => $user->id,
                'category_id' => null,
                'name' => 'Monthly Groceries',
                'amount' => 500.00,
                'period' => 'monthly',
                'start_date' => Carbon::now()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->endOfMonth()->toDateString(),
                'is_active' => true,
                'alert_thresholds' => [50, 75, 90],
            ]);

            // Entertainment
            Budget::create([
                'user_id' => $user->id,
                'category_id' => null,
                'name' => 'Entertainment',
                'amount' => 150.00,
                'period' => 'monthly',
                'start_date' => Carbon::now()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->endOfMonth()->toDateString(),
                'is_active' => true,
                'alert_thresholds' => [50, 85],
            ]);
        }
    }
}
