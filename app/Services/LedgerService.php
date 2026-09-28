<?php

namespace App\Services;

use App\Exceptions\InvalidTransactionException;
use App\Exceptions\UnbalancedTransactionException;
use App\Models\Business;
use App\Models\Invoice;
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
     * @param  array<int, array{account_id:int, debit?:float|string, credit?:float|string, memo?:string}>  $lines
     *
     * @throws UnbalancedTransactionException
     * @throws InvalidTransactionException
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
        $this->assertValid($business, $lines);

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
                    'debit' => self::toCents($line['debit'] ?? 0) / 100,
                    'credit' => self::toCents($line['credit'] ?? 0) / 100,
                    'memo' => $line['memo'] ?? null,
                ]);
            }

            return $transaction;
        });
    }

    /**
     * Amounts are compared as integer cents so float drift (0.1 + 0.2) can
     * never make a balanced entry look unbalanced or vice versa.
     *
     * @throws UnbalancedTransactionException
     * @throws InvalidTransactionException
     */
    private function assertValid(Business $business, array $lines): void
    {
        if (count($lines) < 2) {
            throw InvalidTransactionException::because('a transaction needs at least two lines');
        }

        $debitCents = 0;
        $creditCents = 0;

        foreach (array_values($lines) as $i => $line) {
            $debit = self::toCents($line['debit'] ?? 0);
            $credit = self::toCents($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw InvalidTransactionException::because("line {$i} has a negative amount");
            }

            if (($debit > 0) === ($credit > 0)) {
                throw InvalidTransactionException::because("line {$i} must have either a debit or a credit, not both or neither");
            }

            $debitCents += $debit;
            $creditCents += $credit;
        }

        if ($debitCents !== $creditCents) {
            throw UnbalancedTransactionException::forTotals($debitCents / 100, $creditCents / 100);
        }

        $accountIds = array_unique(array_column($lines, 'account_id'));
        $ownedCount = $business->accounts()->whereIn('id', $accountIds)->count();

        if ($ownedCount !== count($accountIds)) {
            throw InvalidTransactionException::because('one or more accounts do not belong to this business');
        }
    }

    private static function toCents(float|int|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    /**
     * Builds and posts the standard journal entry for issuing an invoice:
     *   Dr Accounts Receivable   (total, incl. GST)
     *      Cr Sales Income          (subtotal)
     *      Cr GST Payable           (gst_total, if registered)
     */
    public function postInvoiceIssued(Invoice $invoice): Transaction
    {
        $business = $invoice->business;
        $ar = $business->accounts()->where('code', '1200')->firstOrFail();
        $sales = $business->accounts()->where('code', '4000')->firstOrFail();

        $lines = [
            ['account_id' => $ar->id, 'debit' => $invoice->total],
            ['account_id' => $sales->id, 'credit' => $invoice->subtotal],
        ];

        if ((float) $invoice->gst_total > 0) {
            $gstPayable = $business->accounts()->where('code', '2100')->firstOrFail();
            $lines[] = ['account_id' => $gstPayable->id, 'credit' => $invoice->gst_total];
        }

        return $this->postTransaction(
            business: $business,
            description: "Invoice {$invoice->number} issued to {$invoice->customer->name}",
            lines: $lines,
            date: $invoice->issue_date->toDateString(),
            referenceNo: $invoice->number,
            sourceType: Invoice::class,
            sourceId: $invoice->id,
        );
    }
}
