<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->string('reference_no')->nullable();
            // Polymorphic-ish link so a transaction can originate from an invoice, a manual entry, a bank import, etc.
            $table->string('source_type')->nullable()->comment('e.g. Invoice, ManualEntry, BankImport');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });

        // The double-entry backbone: every transaction has 2+ lines,
        // and sum(debit) MUST equal sum(credit) for the transaction — enforced in app logic (see App\Services\LedgerService).
        Schema::create('transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_lines');
        Schema::dropIfExists('transactions');
    }
};
