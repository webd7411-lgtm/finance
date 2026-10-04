<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_closings', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->decimal('total_cash_in', 14, 2)->default(0);
            $table->decimal('total_payments_out', 14, 2)->default(0);
            $table->decimal('closing_cash', 14, 2)->default(0);
            $table->decimal('total_difference', 14, 2)->default(0);
            $table->enum('status', ['open', 'closed'])->default('closed');
            $table->text('remarks')->nullable();
            $table->foreignId('closed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_closings');
    }
};
