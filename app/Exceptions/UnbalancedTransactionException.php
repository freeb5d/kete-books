<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown whenever code attempts to persist a transaction whose
 * debit lines and credit lines don't sum to the same amount.
 * This is the one rule the whole ledger depends on — it must
 * never be relaxed, even for "just this once" fixes.
 */
class UnbalancedTransactionException extends Exception
{
    public static function forTotals(float $debitTotal, float $creditTotal): self
    {
        return new self(
            "Transaction is not balanced: debits ({$debitTotal}) must equal credits ({$creditTotal})."
        );
    }
}
