<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InsightController extends Controller
{
    /**
     * Get financial insights.
     */
    public function index(Request $request): JsonResponse
    {
        // TODO: Implement AI-powered insights
        // This would call the FastAPI service for insights

        $insights = [
            [
                'type' => 'spending_trend',
                'title' => 'Spending Trend',
                'description' => 'Your spending has increased by 15% this month compared to last month.',
                'severity' => 'warning',
                'data' => [
                    'current_month' => 1250.00,
                    'previous_month' => 1086.96,
                    'change_percentage' => 15.0,
                ],
            ],
            [
                'type' => 'budget_alert',
                'title' => 'Budget Alert',
                'description' => 'You have exceeded 80% of your Food & Dining budget.',
                'severity' => 'danger',
                'data' => [
                    'category' => 'Food & Dining',
                    'budget_amount' => 500.00,
                    'spent_amount' => 425.00,
                    'percentage' => 85.0,
                ],
            ],
            [
                'type' => 'saving_opportunity',
                'title' => 'Saving Opportunity',
                'description' => 'You could save $50/month by reducing dining out expenses.',
                'severity' => 'info',
                'data' => [
                    'potential_savings' => 50.00,
                    'category' => 'Food & Dining',
                ],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $insights,
        ]);
    }
}