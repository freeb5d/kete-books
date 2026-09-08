<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['business_id', 'code', 'name', 'type', 'is_system', 'is_active'];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Account types whose "natural" balance increases with a DEBIT. */
    public const DEBIT_NATURAL = ['asset', 'expense'];

    /** Account types whose "natural" balance increases with a CREDIT. */
    public const CREDIT_NATURAL = ['liability', 'equity', 'income'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TransactionLine::class);
    }

    /**
     * Current balance of the account, respecting whether it's naturally
     * a debit or credit account. This is what should be shown on reports —
     * never just sum(debit) - sum(credit) blindly, or liability/income/equity
     * balances would display as negative numbers.
     */
    public function balance(): float
    {
        $debitTotal = (float) $this->lines()->sum('debit');
        $creditTotal = (float) $this->lines()->sum('credit');

        return in_array($this->type, self::DEBIT_NATURAL, true)
            ? $debitTotal - $creditTotal
            : $creditTotal - $debitTotal;
    }
}
