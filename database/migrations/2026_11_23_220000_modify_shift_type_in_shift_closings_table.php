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
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE shift_closings MODIFY shift_type VARCHAR(30) NOT NULL DEFAULT '24_hours'");
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            // Rebuild table on SQLite to lift the legacy enum CHECK constraint on shift_type
            $tableInfo = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'shift_closings'");
            if ($tableInfo && isset($tableInfo->sql)) {
                $newSql = preg_replace(
                    '/"shift_type"\s+varchar\s+check\s*\(\s*"shift_type"\s+in\s*\([^)]+\)\s*\)\s+not\s+null/i',
                    '"shift_type" varchar(30) not null default \'24_hours\'',
                    $tableInfo->sql
                );
                if ($newSql && $newSql !== $tableInfo->sql) {
                    $tmpSql = preg_replace('/^CREATE TABLE\s+"?shift_closings"?/i', 'CREATE TABLE "shift_closings_tmp"', $newSql);
                    $cols = array_map(fn ($c) => '"' . $c->name . '"', DB::select("PRAGMA table_info('shift_closings')"));
                    $colList = implode(', ', $cols);

                    DB::statement('PRAGMA foreign_keys=OFF');
                    DB::statement($tmpSql);
                    DB::statement("INSERT INTO \"shift_closings_tmp\" ({$colList}) SELECT {$colList} FROM \"shift_closings\"");
                    DB::statement('DROP TABLE "shift_closings"');
                    DB::statement('ALTER TABLE "shift_closings_tmp" RENAME TO "shift_closings"');
                    DB::statement('PRAGMA foreign_keys=ON');
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE shift_closings MODIFY shift_type ENUM('morning', 'evening') NOT NULL DEFAULT 'morning'");
        }
    }
};
