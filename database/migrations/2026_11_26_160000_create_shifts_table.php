<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedInteger('opening_cash');
            $table->unsignedInteger('cash_sales')->default(0);
            $table->unsignedInteger('cash_expenses')->default(0);
            $table->unsignedInteger('expected_cash')->nullable();
            $table->unsignedInteger('actual_cash')->nullable();
            $table->integer('difference')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
