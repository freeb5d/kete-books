<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'customer_id', 'transaction_id', 'number', 'issue_date', 'due_date',
        'status', 'currency', 'subtotal', 'gst_total', 'total',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'gst_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Recalculates subtotal / gst_total / total from the current invoice lines,
     * using the business's GST rate for lines flagged gst_applicable.
     * Call after adding/removing/editing lines, before save().
     */
    public function recalculateTotals(): void
    {
        $gstRate = (float) $this->business->gst_rate / 100;

        $subtotal = 0.0;
        $gstTotal = 0.0;

        foreach ($this->lines as $line) {
            $lineAmount = (float) $line->quantity * (float) $line->unit_price;
            $subtotal += $lineAmount;

            if ($line->gst_applicable && $this->business->gst_registered) {
                $gstTotal += $lineAmount * $gstRate;
            }
        }

        $this->subtotal = round($subtotal, 2);
        $this->gst_total = round($gstTotal, 2);
        $this->total = round($subtotal + $gstTotal, 2);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'sent' && $this->due_date->isPast();
    }
}
