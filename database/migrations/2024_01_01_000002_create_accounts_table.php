<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10)->comment('e.g. 1000, 2000, 4000');
            $table->string('name');
            // asset, liability, equity, income, expense — the 5 root account types
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->boolean('is_system')->default(false)->comment('Protected accounts created at business setup (e.g. GST Payable)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
