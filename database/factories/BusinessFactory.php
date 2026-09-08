<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'gst_number' => null,
            'gst_registered' => false,
            'gst_rate' => 15.00,
            'currency' => 'NZD',
        ];
    }
}
