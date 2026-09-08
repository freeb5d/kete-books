<?php

namespace App\Services;

use App\Exceptions\UnbalancedTransactionException;
use App\Models\Business;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * LedgerService is the ONLY place in the app that should write to
 * transactions / transaction_lines. Every other feature (invoicing,
 * expenses, bank reconciliation) calls postTransaction() so that the
 * "debits must equal credits" invariant is enforced in exactly one spot.
 *
 * Usage:
 *   $ledger->postTransaction($business, 'Invoice #INV-0001 issued', [
 *       ['account_id' => $accountsReceivable->id, 'debit' => 115.00],
 *       ['account_id' => $salesIncome->id,        'credit' => 100.00],
 *       ['account_id' => $gstPayable->id,         'credit' => 15.00],
 *   ]);
 */
class LedgerService
{
    /**
     * @param  array<int, array{account_id:int, debit?:float, credit?:float, memo?:string}>  $lines
     *
     * @throws UnbalancedTransactionException
     */
    public function postTransaction(
        Business $business,
        string $description,
        array $lines,
        ?string $date = null,
        ?string $referenceNo = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): Transaction {
        $this->assertBalanced($lines);

        return DB::transaction(function () use ($business, $description, $lines, $date, $referenceNo, $sourceType, $sourceId) {
            $transaction = $business->transactions()->create([
                'date' => $date ?? now()->toDateString(),
                'description' => $description,
                'reference_no' => $referenceNo,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            foreach ($lines as $line) {
                $transaction->lines()->create([
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $transaction;
        });
    }

    /**
     * @param  array<int, array{account_id:int, debit?:float, credit?:float}>  $lines
     *
     * @throws UnbalancedTransactionException
     */
    private function assertBalanced(array $lines): void
    {
        $debitTotal = round(array_sum(array_column($lines, 'debit')), 2);
        $creditTotal = round(array_sum(array_column($lines, 'credit')), 2);

        if (abs($debitTotal - $creditTotal) > 0.001) {
            throw UnbalancedTransactionException::forTotals($debitTotal, $creditTotal);
        }
    }

    /**
     * Builds and posts the standard journal entry for issuing an invoice:
     *   Dr Accounts Receivable   (total, incl. GST)
     *      Cr Sales Income          (subtotal)
     *      Cr GST Payable           (gst_total, if registered)
     */
    public function postInvoiceIssued(\App\Models\Invoice $invoice): Transaction
    {
        $business = $invoice->business;
        $ar = $business->accounts()->where('code', '1200')->firstOrFail();
        $sales = $business->accounts()->where('code', '4000')->firstOrFail();

        $lines = [
            ['account_id' => $ar->id, 'debit' => (float) $invoice->total],
            ['account_id' => $sales->id, 'credit' => (float) $invoice->subtotal],
        ];

        if ((float) $invoice->gst_total > 0) {
            $gstPayable = $business->accounts()->where('code', '2100')->firstOrFail();
            $lines[] = ['account_id' => $gstPayable->id, 'credit' => (float) $invoice->gst_total];
        }

        return $this->postTransaction(
            business: $business,
            description: "Invoice {$invoice->number} issued to {$invoice->customer->name}",
            lines: $lines,
            date: $invoice->issue_date->toDateString(),
            referenceNo: $invoice->number,
            sourceType: \App\Models\Invoice::class,
            sourceId: $invoice->id,
        );
    }
}
