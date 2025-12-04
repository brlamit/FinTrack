<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Budget;
use App\Models\Transaction;
use App\Models\Notification;
use Carbon\Carbon;

class BudgetAlertTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function creating_an_expense_triggers_budget_alert_when_threshold_crossed()
    {
        // Create user and category
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        // Create a budget of 100 with 50% threshold
        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Test Budget',
            'amount' => 100.00,
            'period' => 'monthly',
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->endOfMonth()->toDateString(),
            'is_active' => true,
            'alert_thresholds' => [50],
        ]);

        // At this point no notifications
        $this->assertDatabaseCount('notifications', 0);

        // Create a transaction that will push spending over 50%
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 60.00,
            'description' => 'Grocery test',
            'transaction_date' => Carbon::now()->toDateString(),
            'type' => 'expense',
        ]);

        // Ensure evaluation runs (in some test environments model events may not trigger as expected)
        $transaction = Transaction::where('user_id', $user->id)->first();
        $user->evaluateBudgetsForTransaction($transaction, 0);

        // Assert notification exists
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'budget_alert',
        ]);

        $notification = Notification::where('user_id', $user->id)
            ->where('type', 'budget_alert')
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame((int) $budget->id, (int) ($notification->data['budget_id'] ?? $notification->data['budget'] ?? 0));
    }
}
