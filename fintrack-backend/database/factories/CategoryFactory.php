<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'color' => '#'.substr(md5($this->faker->word()), 0, 6),
            'user_id' => null, // default to system category; tests can override
        ];
    }
}
