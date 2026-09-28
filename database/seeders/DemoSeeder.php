<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Database\Seeder;

/**
 * Seeds one demo business with a chart of accounts, a couple of customers,
 * and a sent invoice — enough to make the dashboard look alive for a
 * portfolio demo. Run with: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@ketebooks.test',
        ]);

        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Kaveh\'s Kai Truck',
            'gst_registered' => true,
            'gst_rate' => 15.00,
            'gst_number' => '123-456-789',
            'currency' => 'NZD',
        ]);

        $business->seedDefaultChartOfAccounts();

        $customer = $business->customers()->create([
            'name' => 'Northland Cafe Supplies',
            'email' => 'orders@northlandcafe.example',
        ]);

        $invoice = $business->invoices()->create([
            'customer_id' => $customer->id,
            'number' => $business->nextInvoiceNumber(),
            'issue_date' => now()->subDays(5),
            'due_date' => now()->addDays(9),
            'status' => 'draft',
            'currency' => 'NZD',
        ]);

        $invoice->lines()->create([
            'description' => 'Weekly catering — food truck event',
            'quantity' => 1,
            'unit_price' => 480.00,
            'gst_applicable' => true,
            'line_total' => 480.00,
        ]);

        $invoice->load('lines');
        $invoice->recalculateTotals();
        $invoice->save();

        $transaction = app(LedgerService::class)->postInvoiceIssued($invoice);
        $invoice->update(['status' => 'sent', 'transaction_id' => $transaction->id]);

        $this->command?->info("Demo data seeded. Log in as {$user->email} with password \"password\".");
    }
}
