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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('location');
            $table->string('banner_icon')->nullable();
            $table->string('profile_icon')->nullable();
            $table->string('full_name');
            $table->string('contact_no');
            $table->string('account_status')->default('pending');
            $table->timestamp('expires')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('gender')->nullable();
            $table->string('address');
            $table->date('dob');
            $table->string('org_registration_card')->nullable();
            $table->string('org_pan_no')->nullable();
            $table->string('email', 15)->unique();
            $table->boolean('email_verified')->default(0);
            $table->string('password', 25);
            $table->string('user_role', 10);
            $table->boolean('is_vend_cust')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
