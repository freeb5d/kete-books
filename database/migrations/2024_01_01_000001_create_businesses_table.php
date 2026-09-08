<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('gst_number')->nullable()->comment('IRD GST number, NZ format 000-000-000');
            $table->boolean('gst_registered')->default(false);
            $table->decimal('gst_rate', 5, 2)->default(15.00)->comment('NZ standard GST rate = 15%');
            $table->string('currency', 3)->default('NZD');
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
