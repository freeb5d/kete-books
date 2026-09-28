<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'business_id' => fn (array $attributes) => Customer::find($attributes['customer_id'])->business_id,
            'number' => 'INV-'.$this->faker->unique()->numerify('####'),
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'status' => 'draft',
            'currency' => 'NZD',
        ];
    }
}
