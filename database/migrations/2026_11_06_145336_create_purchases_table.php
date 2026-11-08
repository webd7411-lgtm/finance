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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->date('purchase_date');
            $table->string('invoice_no')->nullable(); // Supplier Delivery / Invoice Bill No
            $table->foreignId('supplier_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('vehicle_no')->nullable(); // Tanker / Bowser registration number
            
            $table->decimal('quantity', 12, 2)->default(0); // Volume in Litres or Units
            $table->decimal('unit_price', 12, 2)->default(0); // Cost rate per litre / unit
            $table->decimal('total_amount', 14, 2)->default(0); // quantity * unit_price
            $table->decimal('paid_amount', 14, 2)->default(0); // Paid immediately from account
            $table->foreignId('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            
            $table->text('notes')->nullable(); // Chamber dip, temperature, notes
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('purchase_date');
            $table->index('supplier_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
