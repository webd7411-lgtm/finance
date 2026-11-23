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
        // 1. Add dispenser_unit to pumps table
        Schema::table('pumps', function (Blueprint $table) {
            if (!Schema::hasColumn('pumps', 'dispenser_unit')) {
                $table->string('dispenser_unit')->nullable()->after('name');
            }
        });

        // 2. Enhance shift_closings table
        Schema::table('shift_closings', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_closings', 'testing_liters')) {
                $table->decimal('testing_liters', 14, 2)->default(0)->after('total_liters');
            }
            if (!Schema::hasColumn('shift_closings', 'total_credit_sales')) {
                $table->decimal('total_credit_sales', 14, 2)->default(0)->after('total_sale');
            }
            if (!Schema::hasColumn('shift_closings', 'total_lubricant_sales')) {
                $table->decimal('total_lubricant_sales', 14, 2)->default(0)->after('total_credit_sales');
            }
            if (!Schema::hasColumn('shift_closings', 'govt_subsidy_amount')) {
                $table->decimal('govt_subsidy_amount', 14, 2)->default(0)->after('bank_amount');
            }
            if (!Schema::hasColumn('shift_closings', 'govt_subsidy_account_id')) {
                $table->foreignId('govt_subsidy_account_id')->nullable()->after('govt_subsidy_amount')->constrained('accounts')->nullOnDelete();
            }
            if (!Schema::hasColumn('shift_closings', 'govt_subsidy_remarks')) {
                $table->string('govt_subsidy_remarks')->nullable()->after('govt_subsidy_account_id');
            }
        });

        // 3. Multi-Nozzle meter readings per shift closing
        if (!Schema::hasTable('shift_closing_nozzles')) {
            Schema::create('shift_closing_nozzles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shift_closing_id')->constrained('shift_closings')->cascadeOnDelete();
                $table->foreignId('pump_id')->constrained('pumps')->cascadeOnDelete();
                $table->foreignId('tank_id')->nullable()->constrained('tanks')->nullOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('nozzle_name')->nullable();
                $table->decimal('start_reading', 14, 2)->default(0);
                $table->decimal('end_reading', 14, 2)->default(0);
                $table->decimal('meter_liters', 14, 2)->default(0);
                $table->decimal('testing_liters', 14, 2)->default(0);
                $table->decimal('sold_liters', 14, 2)->default(0);
                $table->decimal('fuel_rate', 12, 2)->default(0);
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->timestamps();

                $table->index(['shift_closing_id', 'pump_id']);
            });
        }

        // 4. Shift Counter Expenses Breakdown (Video walkthrough requirement)
        if (!Schema::hasTable('shift_closing_expenses')) {
            Schema::create('shift_closing_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shift_closing_id')->constrained('shift_closings')->cascadeOnDelete();
                $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
                $table->string('category_name')->nullable();
                $table->string('details')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->timestamps();

                $table->index('shift_closing_id');
            });
        }

        // 5. Shift Counter Lubricants / Engine Oil Sales (Audio 6)
        if (!Schema::hasTable('shift_closing_lubricants')) {
            Schema::create('shift_closing_lubricants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shift_closing_id')->constrained('shift_closings')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->timestamps();

                $table->index('shift_closing_id');
            });
        }

        // 6. Shift Credit / Udhaar Fuel Sales to Parties (Audio 8)
        if (!Schema::hasTable('shift_closing_credits')) {
            Schema::create('shift_closing_credits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shift_closing_id')->constrained('shift_closings')->cascadeOnDelete();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('vehicle_no')->nullable();
                $table->string('slip_no')->nullable();
                $table->decimal('liters', 12, 2)->default(0);
                $table->decimal('rate', 12, 2)->default(0);
                $table->decimal('amount', 14, 2)->default(0);
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['shift_closing_id', 'party_id']);
            });
        }

        // 7. Station Rental Facilities (Car Wash, Tyre Shop, Tuck Shop, ATM) (Audio 5)
        if (!Schema::hasTable('station_rentals')) {
            Schema::create('station_rentals', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('facility_type')->default('car_wash'); // car_wash, tyre_shop, tuck_shop, atm, other
                $table->string('tenant_name');
                $table->string('tenant_phone')->nullable();
                $table->decimal('monthly_rent', 14, 2)->default(0);
                $table->integer('due_day')->default(1);
                $table->string('status')->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 8. Rental Payments & Collections Ledger (Audio 5)
        if (!Schema::hasTable('rental_collections')) {
            Schema::create('rental_collections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('station_rental_id')->constrained('station_rentals')->cascadeOnDelete();
                $table->date('payment_date');
                $table->string('rent_month'); // e.g. "2026-11"
                $table->decimal('amount', 14, 2)->default(0);
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->string('receipt_no')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['station_rental_id', 'payment_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_collections');
        Schema::dropIfExists('station_rentals');
        Schema::dropIfExists('shift_closing_credits');
        Schema::dropIfExists('shift_closing_lubricants');
        Schema::dropIfExists('shift_closing_expenses');
        Schema::dropIfExists('shift_closing_nozzles');

        Schema::table('shift_closings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('govt_subsidy_account_id');
            $table->dropColumn([
                'testing_liters',
                'total_credit_sales',
                'total_lubricant_sales',
                'govt_subsidy_amount',
                'govt_subsidy_remarks',
            ]);
        });

        Schema::table('pumps', function (Blueprint $table) {
            $table->dropColumn('dispenser_unit');
        });
    }
};
