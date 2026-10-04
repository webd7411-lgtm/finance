<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_closings', function (Blueprint $table) {
            $table->unsignedInteger('invoice_start')->nullable();
            $table->unsignedInteger('invoice_end')->nullable();
            $table->string('return_invoice_number')->nullable();
            $table->text('expenses_details')->nullable();
        });

        Schema::create('shift_closing_party_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_closing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->text('details');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_closing_party_payments');

        Schema::table('shift_closings', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_start',
                'invoice_end',
                'return_invoice_number',
                'expenses_details',
            ]);
        });
    }
};