<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cards_uses', function (Blueprint $table) {
            $table->id();
            $table->string('product_id');
            $table->string('card_no');
            $table->string('customer_name');
            $table->double('total_price');
            $table->string('discount_amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards_uses');
    }
};
