<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Income categories
            ['name' => 'Salary', 'icon' => '💼', 'color' => '#4CAF50', 'type' => 'income'],
            ['name' => 'Freelance', 'icon' => '💻', 'color' => '#2196F3', 'type' => 'income'],
            ['name' => 'Business', 'icon' => '🏢', 'color' => '#FF9800', 'type' => 'income'],
            ['name' => 'Investment', 'icon' => '📈', 'color' => '#9C27B0', 'type' => 'income'],
            ['name' => 'Gift', 'icon' => '🎁', 'color' => '#E91E63', 'type' => 'income'],
            ['name' => 'Other Income', 'icon' => '💰', 'color' => '#00BCD4', 'type' => 'income'],

            // Expense categories
            ['name' => 'Food & Dining', 'icon' => '🍽️', 'color' => '#FF5722', 'type' => 'expense'],
            ['name' => 'Transportation', 'icon' => '🚗', 'color' => '#795548', 'type' => 'expense'],
            ['name' => 'Shopping', 'icon' => '🛍️', 'color' => '#9C27B0', 'type' => 'expense'],
            ['name' => 'Entertainment', 'icon' => '🎬', 'color' => '#673AB7', 'type' => 'expense'],
            ['name' => 'Bills & Utilities', 'icon' => '💡', 'color' => '#FF9800', 'type' => 'expense'],
            ['name' => 'Healthcare', 'icon' => '🏥', 'color' => '#F44336', 'type' => 'expense'],
            ['name' => 'Education', 'icon' => '📚', 'color' => '#2196F3', 'type' => 'expense'],
            ['name' => 'Travel', 'icon' => '✈️', 'color' => '#00BCD4', 'type' => 'expense'],
            ['name' => 'Insurance', 'icon' => '🛡️', 'color' => '#607D8B', 'type' => 'expense'],
            ['name' => 'Personal Care', 'icon' => '💅', 'color' => '#E91E63', 'type' => 'expense'],
            ['name' => 'Home & Garden', 'icon' => '🏠', 'color' => '#4CAF50', 'type' => 'expense'],
            ['name' => 'Pets', 'icon' => '🐾', 'color' => '#FFEB3B', 'type' => 'expense'],
            ['name' => 'Other Expense', 'icon' => '📦', 'color' => '#9E9E9E', 'type' => 'expense'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}