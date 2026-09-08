<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'gst_number', 'gst_registered', 'gst_rate', 'currency', 'address',
    ];

    protected $casts = [
        'gst_registered' => 'boolean',
        'gst_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Creates the default Chart of Accounts for a newly registered business.
     * Called once from RegisterBusinessAction / a seeder.
     */
    public function seedDefaultChartOfAccounts(): void
    {
        $defaults = [
            ['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'asset'],
            ['code' => '1010', 'name' => 'Business Bank Account', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'GST Payable', 'type' => 'liability'],
            ['code' => '3000', 'name' => "Owner's Equity", 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Sales Income', 'type' => 'income'],
            ['code' => '5000', 'name' => 'General Expenses', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'Cost of Goods Sold', 'type' => 'expense'],
        ];

        foreach ($defaults as $account) {
            $this->accounts()->firstOrCreate(
                ['code' => $account['code']],
                ['name' => $account['name'], 'type' => $account['type'], 'is_system' => true]
            );
        }
    }
}
