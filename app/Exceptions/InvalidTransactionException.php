<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a transaction is structurally invalid even if it happens to
 * balance: too few lines, negative amounts, a line with both a debit and a
 * credit, or an account that belongs to another business.
 */
class InvalidTransactionException extends Exception
{
    public static function because(string $reason): self
    {
        return new self("Invalid transaction: {$reason}.");
    }
}
