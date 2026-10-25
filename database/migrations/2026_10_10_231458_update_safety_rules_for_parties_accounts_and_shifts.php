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
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('type');
            }
        });

        Schema::table('shift_closings', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_closings', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->after('verified_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('shift_closings', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_closings', function (Blueprint $table) {
            if (Schema::hasColumn('shift_closings', 'deleted_by')) {
                if (Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['deleted_by']);
                }
                $table->dropColumn('deleted_by');
            }
            if (Schema::hasColumn('shift_closings', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('accounts', function (Blueprint $table) {
            if (Schema::hasColumn('accounts', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
