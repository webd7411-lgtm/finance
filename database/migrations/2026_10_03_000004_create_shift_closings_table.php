<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_closings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('shift_type', ['morning', 'evening']);
            $table->foreignId('cashier_id')->constrained('users')->onDelete('cascade');
            
            // Sales & Returns
            $table->integer('total_invoices')->default(0);
            $table->decimal('total_sale', 14, 2)->default(0);
            $table->decimal('returns_amount', 14, 2)->default(0);
            $table->decimal('expenses_amount', 14, 2)->default(0);
            
            // Cash Note Denominations
            $table->integer('note_5000')->default(0);
            $table->integer('note_1000')->default(0);
            $table->integer('note_500')->default(0);
            $table->integer('note_100')->default(0);
            $table->integer('note_50')->default(0);
            $table->integer('note_20')->default(0);
            $table->integer('note_10')->default(0);
            $table->decimal('coins', 10, 2)->default(0);
            $table->decimal('total_counted_cash', 14, 2)->default(0);
            
            // JazzCash & Bank
            $table->decimal('jazzcash_amount', 14, 2)->default(0);
            $table->decimal('bank_amount', 14, 2)->default(0);
            $table->decimal('total_actual_received', 14, 2)->default(0);
            
            // Calculations
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('difference', 14, 2)->default(0);
            
            // Status and Control
            $table->enum('status', ['submitted', 'locked'])->default('locked');
            $table->text('remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_closings');
    }
};
