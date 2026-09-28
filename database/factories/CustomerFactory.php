<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => $this->faker->company(),
            'email' => $this->faker->companyEmail(),
        ];
    }
}
