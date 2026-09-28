<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /** Preloads debit/credit sums in the same query, for balance(). */
    public function scopeWithBalanceTotals(Builder $query): Builder
    {
        return $query
            ->withSum('lines as debit_total', 'debit')
            ->withSum('lines as credit_total', 'credit');
    }

    /**
     * Current balance of the account, respecting whether it's naturally
     * a debit or credit account. This is what should be shown on reports —
     * never just sum(debit) - sum(credit) blindly, or liability/income/equity
     * balances would display as negative numbers.
     */
    public function balance(): float
    {
        // Use totals preloaded by withBalanceTotals() when present, so listing
        // N accounts costs one query instead of 2N.
        if (array_key_exists('debit_total', $this->attributes)) {
            $debitTotal = (float) $this->debit_total;
            $creditTotal = (float) $this->credit_total;
        } else {
            $debitTotal = (float) $this->lines()->sum('debit');
            $creditTotal = (float) $this->lines()->sum('credit');
        }

        return in_array($this->type, self::DEBIT_NATURAL, true)
            ? $debitTotal - $creditTotal
            : $creditTotal - $debitTotal;
    }
}
