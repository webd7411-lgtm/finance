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
        Schema::table('shift_closings', function (Blueprint $table) {
            $table->unsignedInteger('return_invoice_start')->nullable()->after('return_invoice_number');
            $table->unsignedInteger('return_invoice_end')->nullable()->after('return_invoice_start');
            $table->unsignedInteger('total_return_invoices')->default(0)->after('return_invoice_end');
        });
    }

    public function down(): void
    {
        Schema::table('shift_closings', function (Blueprint $table) {
            $table->dropColumn(['return_invoice_start', 'return_invoice_end', 'total_return_invoices']);
        });
    }
};
