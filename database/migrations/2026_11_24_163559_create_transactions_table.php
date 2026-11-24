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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->string('invoice_no')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('customer_id')->nullable(); // FK ditambah saat tabel customers dibuat
            $table->string('table_label')->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total');
            $table->string('payment_method'); // cash / qris / transfer / debt
            $table->unsignedInteger('amount_paid');
            $table->unsignedInteger('change_amount')->default(0);
            $table->string('status')->default('paid'); // paid / debt / void
            $table->string('void_reason')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
