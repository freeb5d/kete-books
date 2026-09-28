<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Last invoice number handed out. Incremented under a row lock so
            // two concurrent requests can never both get the same INV-xxxx.
            $table->unsignedInteger('invoice_sequence')->default(0)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('invoice_sequence');
        });
    }
};
