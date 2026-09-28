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
        // Work in integer cents: each line is rounded to the cent (matching the
        // stored line_total), and GST is rounded once on the invoice total.
        $subtotalCents = 0;
        $gstableCents = 0;

        foreach ($this->lines as $line) {
            $lineCents = (int) round((float) $line->quantity * (float) $line->unit_price * 100);
            $subtotalCents += $lineCents;

            if ($line->gst_applicable && $this->business->gst_registered) {
                $gstableCents += $lineCents;
            }
        }

        $gstCents = (int) round($gstableCents * (float) $this->business->gst_rate / 100);

        $this->subtotal = $subtotalCents / 100;
        $this->gst_total = $gstCents / 100;
        $this->total = ($subtotalCents + $gstCents) / 100;
    }

    public static function lineTotal(float|string $quantity, float|string $unitPrice): float
    {
        return round((float) $quantity * (float) $unitPrice, 2);
    }

    /**
     * "Overdue" is derived from the due date rather than stored, so it can
     * never go stale. The 'overdue' enum value is kept only for backwards
     * compatibility and is never written.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'sent' && $this->due_date->endOfDay()->isPast();
    }

    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }
}
