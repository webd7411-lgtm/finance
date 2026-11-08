<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Station Locations table (Multi-station / site support)
        if (!Schema::hasTable('station_locations')) {
            Schema::create('station_locations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable()->unique();
                $table->string('city')->nullable();
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('manager_name')->nullable();
                $table->boolean('is_default')->default(false);
                $table->string('status')->default('active'); // active, inactive
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 2. Storage Tanks table (Underground & Aboveground fuel storage)
        if (!Schema::hasTable('tanks')) {
            Schema::create('tanks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('location_id')->nullable()->constrained('station_locations')->nullOnDelete();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
                $table->string('tank_number'); // e.g. TK-01, Tank #1
                $table->string('name');        // e.g. Underground Tank #1 (Super Petrol)
                $table->string('tank_type')->default('underground'); // underground, aboveground
                $table->decimal('capacity', 14, 2)->default(25000.00); // Total capacity in liters
                $table->decimal('opening_stock', 14, 2)->default(0.00);
                $table->decimal('current_stock', 14, 2)->default(0.00); // Live stock in liters
                $table->decimal('dead_stock', 14, 2)->default(500.00);   // Unusable fuel below suction pipe
                $table->decimal('current_dip_reading', 8, 2)->nullable(); // Dip stick reading in cm
                $table->decimal('max_dip_reading', 8, 2)->nullable();     // Max calibration height in cm
                $table->string('status')->default('active'); // active, maintenance, inactive
                $table->text('description')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 3. Tank Dip Readings table (Audit log of physical dip stick measurements & book variance)
        if (!Schema::hasTable('tank_dip_readings')) {
            Schema::create('tank_dip_readings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
                $table->date('reading_date');
                $table->decimal('dip_cm', 8, 2);
                $table->decimal('measured_liters', 14, 2);
                $table->decimal('book_stock_liters', 14, 2)->default(0.00);
                $table->decimal('variance_liters', 14, 2)->default(0.00);
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 4. Add location_id and tank_id to pumps table
        Schema::table('pumps', function (Blueprint $table) {
            if (!Schema::hasColumn('pumps', 'location_id')) {
                $table->foreignId('location_id')->nullable()->after('id')->constrained('station_locations')->nullOnDelete();
            }
            if (!Schema::hasColumn('pumps', 'tank_id')) {
                $table->foreignId('tank_id')->nullable()->after('product_id')->constrained('tanks')->nullOnDelete();
            }
        });

        // 5. Add tank_id to purchases table
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'tank_id')) {
                $table->foreignId('tank_id')->nullable()->after('pump_id')->constrained('tanks')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchases', 'location_id')) {
                $table->foreignId('location_id')->nullable()->after('product_id')->constrained('station_locations')->nullOnDelete();
            }
        });

        // 6. Add tank_id to shift_closings table
        Schema::table('shift_closings', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_closings', 'tank_id')) {
                $table->foreignId('tank_id')->nullable()->after('pump_id')->constrained('tanks')->nullOnDelete();
            }
        });

        // 7. Seed Initial Main Station Location & Tanks based on existing products and pumps
        $defaultLocId = DB::table('station_locations')->insertGetId([
            'name'         => 'Caltex Rao Filling Station - Main Site',
            'code'         => 'CRFS-MAIN',
            'city'         => 'Hyderabad',
            'address'      => 'National Highway / Main Station Road',
            'phone'        => '+92 317 3836223',
            'manager_name' => 'Station Incharge',
            'is_default'   => true,
            'status'       => 'active',
            'notes'        => 'Primary 24/7 Filling Station Site with Underground Fuel Tanks & Dispensers',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Auto-seed storage tanks for fuel products
        $fuelProducts = DB::table('products')->where('is_fuel', 1)->get();
        $tankMapping = [];

        foreach ($fuelProducts as $fp) {
            $cap = match ($fp->id) {
                1 => 25000.00, // Super Petrol
                2 => 50000.00, // Diesel HSD
                3 => 15000.00, // Hi-Octane
                default => 20000.00,
            };
            $dead = match ($fp->id) {
                1 => 500.00,
                2 => 800.00,
                3 => 300.00,
                default => 400.00,
            };

            $tankNumber = 'TK-0' . $fp->id;
            $tankName = 'Underground Tank #' . $fp->id . ' (' . $fp->name . ')';

            $tankId = DB::table('tanks')->insertGetId([
                'location_id'         => $defaultLocId,
                'product_id'          => $fp->id,
                'tank_number'         => $tankNumber,
                'name'                => $tankName,
                'tank_type'           => 'underground',
                'capacity'            => $cap,
                'opening_stock'       => $fp->current_stock,
                'current_stock'       => $fp->current_stock,
                'dead_stock'          => $dead,
                'current_dip_reading' => 140.00,
                'max_dip_reading'     => 220.00,
                'status'              => 'active',
                'description'         => 'Calibrated Underground Tank dedicated to ' . $fp->name,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $tankMapping[$fp->id] = $tankId;
        }

        // Link existing pumps to the corresponding tanks and location
        $pumps = DB::table('pumps')->get();
        foreach ($pumps as $pump) {
            $matchedTankId = $tankMapping[$pump->product_id] ?? null;
            DB::table('pumps')->where('id', $pump->id)->update([
                'location_id' => $defaultLocId,
                'tank_id'     => $matchedTankId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_closings', function (Blueprint $table) {
            if (Schema::hasColumn('shift_closings', 'tank_id')) {
                $table->dropConstrainedForeignId('tank_id');
            }
        });

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'tank_id')) {
                $table->dropConstrainedForeignId('tank_id');
            }
            if (Schema::hasColumn('purchases', 'location_id')) {
                $table->dropConstrainedForeignId('location_id');
            }
        });

        Schema::table('pumps', function (Blueprint $table) {
            if (Schema::hasColumn('pumps', 'tank_id')) {
                $table->dropConstrainedForeignId('tank_id');
            }
            if (Schema::hasColumn('pumps', 'location_id')) {
                $table->dropConstrainedForeignId('location_id');
            }
        });

        Schema::dropIfExists('tank_dip_readings');
        Schema::dropIfExists('tanks');
        Schema::dropIfExists('station_locations');
    }
};
