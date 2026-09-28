<?php

namespace Tests\Feature;

use App\Exceptions\InvalidTransactionException;
use App\Exceptions\UnbalancedTransactionException;
use App\Models\Business;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerValidationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->business->seedDefaultChartOfAccounts();
        $this->ledger = new LedgerService();
    }

    private function account(string $code, ?Business $business = null): int
    {
        return ($business ?? $this->business)->accounts()->where('code', $code)->value('id');
    }

    public function test_float_drift_does_not_make_a_balanced_entry_fail(): void
    {
        $transaction = $this->ledger->postTransaction($this->business, 'Drift', [
            ['account_id' => $this->account('1000'), 'debit' => 0.1],
            ['account_id' => $this->account('1000'), 'debit' => 0.2],
            ['account_id' => $this->account('4000'), 'credit' => 0.3],
        ]);

        $this->assertTrue($transaction->isBalanced());
    }

    public function test_a_single_line_is_rejected(): void
    {
        $this->expectException(InvalidTransactionException::class);

        $this->ledger->postTransaction($this->business, 'One line', [
            ['account_id' => $this->account('1000'), 'debit' => 0],
        ]);
    }

    public function test_an_all_zero_entry_is_rejected(): void
    {
        $this->expectException(InvalidTransactionException::class);

        $this->ledger->postTransaction($this->business, 'Zeros', [
            ['account_id' => $this->account('1000'), 'debit' => 0],
            ['account_id' => $this->account('4000'), 'credit' => 0],
        ]);
    }

    public function test_negative_amounts_are_rejected(): void
    {
        $this->expectException(InvalidTransactionException::class);

        $this->ledger->postTransaction($this->business, 'Negative', [
            ['account_id' => $this->account('1000'), 'debit' => -50],
            ['account_id' => $this->account('4000'), 'credit' => -50],
        ]);
    }

    public function test_a_line_with_both_debit_and_credit_is_rejected(): void
    {
        $this->expectException(InvalidTransactionException::class);

        $this->ledger->postTransaction($this->business, 'Both sides', [
            ['account_id' => $this->account('1000'), 'debit' => 50, 'credit' => 50],
            ['account_id' => $this->account('4000'), 'debit' => 10],
            ['account_id' => $this->account('5000'), 'credit' => 10],
        ]);
    }

    public function test_accounts_from_another_business_are_rejected(): void
    {
        $other = Business::factory()->create();
        $other->seedDefaultChartOfAccounts();

        $this->expectException(InvalidTransactionException::class);

        $this->ledger->postTransaction($this->business, 'Cross-business', [
            ['account_id' => $this->account('1000'), 'debit' => 50],
            ['account_id' => $this->account('4000', $other), 'credit' => 50],
        ]);
    }

    public function test_unbalanced_is_still_reported_as_unbalanced(): void
    {
        $this->expectException(UnbalancedTransactionException::class);

        $this->ledger->postTransaction($this->business, 'Off by a cent', [
            ['account_id' => $this->account('1000'), 'debit' => 100.00],
            ['account_id' => $this->account('4000'), 'credit' => 99.99],
        ]);
    }
}
