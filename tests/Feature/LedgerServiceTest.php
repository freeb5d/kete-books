<?php

namespace Tests\Feature;

use App\Exceptions\UnbalancedTransactionException;
use App\Models\Business;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->business = Business::factory()->create(['user_id' => $user->id]);
        $this->business->seedDefaultChartOfAccounts();

        $this->ledger = new LedgerService();
    }

    public function test_a_balanced_transaction_posts_successfully(): void
    {
        $cash = $this->business->accounts()->where('code', '1000')->first();
        $sales = $this->business->accounts()->where('code', '4000')->first();

        $transaction = $this->ledger->postTransaction($this->business, 'Cash sale', [
            ['account_id' => $cash->id, 'debit' => 50.00],
            ['account_id' => $sales->id, 'credit' => 50.00],
        ]);

        $this->assertTrue($transaction->isBalanced());
        $this->assertEquals(50.00, $cash->balance());
        $this->assertEquals(50.00, $sales->balance());
    }

    public function test_an_unbalanced_transaction_is_rejected(): void
    {
        $cash = $this->business->accounts()->where('code', '1000')->first();
        $sales = $this->business->accounts()->where('code', '4000')->first();

        $this->expectException(UnbalancedTransactionException::class);

        // Debit 100 but only credit 90 — the ledger must refuse this,
        // otherwise the books would silently stop balancing.
        $this->ledger->postTransaction($this->business, 'Bad entry', [
            ['account_id' => $cash->id, 'debit' => 100.00],
            ['account_id' => $sales->id, 'credit' => 90.00],
        ]);
    }

    public function test_invoice_issuance_splits_gst_into_its_own_liability_account(): void
    {
        $this->business->update(['gst_registered' => true, 'gst_rate' => 15]);

        $customer = $this->business->customers()->create(['name' => 'Test Customer']);

        $invoice = $this->business->invoices()->create([
            'customer_id' => $customer->id,
            'number' => 'INV-0001',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'status' => 'draft',
            'currency' => 'NZD',
        ]);

        $invoice->lines()->create([
            'description' => 'Consulting',
            'quantity' => 1,
            'unit_price' => 100.00,
            'gst_applicable' => true,
            'line_total' => 100.00,
        ]);

        $invoice->load('lines');
        $invoice->recalculateTotals();
        $invoice->save();

        $this->assertEquals(100.00, $invoice->subtotal);
        $this->assertEquals(15.00, $invoice->gst_total);
        $this->assertEquals(115.00, $invoice->total);

        $transaction = $this->ledger->postInvoiceIssued($invoice);

        $this->assertTrue($transaction->isBalanced());

        $gstPayable = $this->business->accounts()->where('code', '2100')->first();
        $this->assertEquals(15.00, $gstPayable->balance());
    }
}
