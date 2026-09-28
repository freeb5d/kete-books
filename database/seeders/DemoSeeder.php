<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Invoice;
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

        $ledger = app(LedgerService::class);
        $account = fn (string $code) => $business->accounts()->where('code', $code)->value('id');

        $ledger->postTransaction($business, 'Owner start-up capital', [
            ['account_id' => $account('1010'), 'debit' => 5000.00],
            ['account_id' => $account('3000'), 'credit' => 5000.00],
        ], now()->subDays(40)->toDateString());

        $ledger->postTransaction($business, 'Produce and packaging — Pak\'nSave', [
            ['account_id' => $account('5100'), 'debit' => 612.40],
            ['account_id' => $account('1010'), 'credit' => 612.40],
        ], now()->subDays(12)->toDateString());

        $ledger->postTransaction($business, 'Council food truck licence', [
            ['account_id' => $account('5000'), 'debit' => 180.00],
            ['account_id' => $account('1010'), 'credit' => 180.00],
        ], now()->subDays(20)->toDateString());

        $cafe = $business->customers()->create([
            'name' => 'Northland Cafe Supplies',
            'email' => 'orders@northlandcafe.example',
        ]);
        $marae = $business->customers()->create(['name' => 'Te Tai Tokerau Events']);

        $this->invoice($business, $cafe, 5, 9, 'sent', [
            ['Weekly catering — food truck event', 1, 480.00, true],
            ['Delivery', 1, 35.00, true],
        ]);
        $this->invoice($business, $marae, 30, -2, 'sent', [
            ['Hāngī catering for 60 guests', 60, 18.50, true],
        ]);
        $this->invoice($business, $cafe, 0, 14, 'draft', [
            ['Coffee cart hire (half day)', 1, 220.00, true],
        ]);

        $this->command?->info("Demo data seeded. Log in as {$user->email} with password \"password\".");
    }

    /** @param  array<int, array{0:string, 1:float, 2:float, 3:bool}>  $lines */
    private function invoice(Business $business, Customer $customer, int $issuedDaysAgo, int $dueInDays, string $status, array $lines): void
    {
        $invoice = $business->invoices()->create([
            'customer_id' => $customer->id,
            'number' => $business->nextInvoiceNumber(),
            'issue_date' => now()->subDays($issuedDaysAgo),
            'due_date' => now()->addDays($dueInDays),
            'status' => 'draft',
            'currency' => $business->currency,
        ]);

        foreach ($lines as [$description, $quantity, $unitPrice, $gst]) {
            $invoice->lines()->create([
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'gst_applicable' => $gst,
                'line_total' => Invoice::lineTotal($quantity, $unitPrice),
            ]);
        }

        $invoice->load('lines')->recalculateTotals();
        $invoice->save();

        if ($status === 'sent') {
            $transaction = app(LedgerService::class)->postInvoiceIssued($invoice);
            $invoice->update(['status' => 'sent', 'transaction_id' => $transaction->id]);
        }
    }
}
