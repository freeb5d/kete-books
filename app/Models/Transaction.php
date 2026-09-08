<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'date', 'description', 'reference_no', 'source_type', 'source_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TransactionLine::class);
    }

    /**
     * A transaction is only valid bookkeeping if debits == credits.
     * Used as a guard before persisting, and by an artisan command that
     * audits the ledger for integrity.
     */
    public function isBalanced(): bool
    {
        $totalDebit = (float) $this->lines->sum('debit');
        $totalCredit = (float) $this->lines->sum('credit');

        return abs($totalDebit - $totalCredit) < 0.001;
    }
}
