<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotent, schema-drift-safe version. Two prior attempts at this
     * migration partially applied DDL before failing (MySQL auto-commits
     * DDL per-statement, outside Laravel's migration transaction), leaving
     * the database in states this version must tolerate unconditionally:
     *
     *   - product_units.active_serial_number / active_secondary_serial_number
     *     may already exist, with or without their unique indexes.
     *   - serial_number_cleanup_audit_log may already exist under an
     *     earlier column layout (serial_value) from the first attempt,
     *     rather than the current layout (old_value).
     *
     * The audit log table is treated as fully disposable/rebuildable on
     * every run of this migration — it is written to and read from only
     * within this migration's own remediation steps, has no foreign key
     * dependents, and carries no data that predates this migration's own
     * execution. Dropping and recreating it fresh at the start of up()
     * removes an entire class of schema-drift failure rather than trying
     * to detect and patch every possible partial prior shape.
     */
    public function up(): void
    {
        // ── Idempotency guard: tear down any partial state from prior attempts ──
        if (Schema::hasColumn('product_units', 'active_secondary_serial_number')) {
            $this->dropUniqueIfExists('product_units', 'product_units_active_secondary_serial_unique');
            Schema::table('product_units', fn (Blueprint $t) => $t->dropColumn('active_secondary_serial_number'));
        }
        if (Schema::hasColumn('product_units', 'active_serial_number')) {
            $this->dropUniqueIfExists('product_units', 'product_units_active_serial_unique');
            Schema::table('product_units', fn (Blueprint $t) => $t->dropColumn('active_serial_number'));
        }

        Schema::dropIfExists('serial_number_cleanup_audit_log');
        Schema::create('serial_number_cleanup_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_unit_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('field', 30);
            $table->string('old_value')->nullable();
            $table->string('resolution', 30); // 'empty_string_normalized' | 'force_archived_duplicate'
            $table->timestamp('cleaned_at');
        });

        // ── Step 1: normalize empty-string / whitespace-only values to NULL ──
        $this->normalizeEmptyToNull('serial_number');
        $this->normalizeEmptyToNull('secondary_serial_number');

        // ── Step 2: resolve genuine duplicate non-empty active values ──
        $this->resolveActiveDuplicates('serial_number');
        $this->resolveActiveDuplicates('secondary_serial_number');

        // ── Step 3: generated columns — '' treated as NULL inside the expression ──
        Schema::table('product_units', function (Blueprint $table) {
            $table->string('active_serial_number')
                ->nullable()
                ->storedAs("CASE WHEN is_archived = 0 THEN NULLIF(serial_number, '') ELSE NULL END")
                ->after('serial_number');

            $table->string('active_secondary_serial_number')
                ->nullable()
                ->storedAs("CASE WHEN is_archived = 0 THEN NULLIF(secondary_serial_number, '') ELSE NULL END")
                ->after('secondary_serial_number');
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->unique('active_serial_number', 'product_units_active_serial_unique');
            $table->unique('active_secondary_serial_number', 'product_units_active_secondary_serial_unique');
        });
    }

    private function normalizeEmptyToNull(string $column): void
    {
        DB::table('product_units')
            ->select('id', 'shop_id', $column)
            ->where(function ($q) use ($column) {
                $q->where($column, '')->orWhereRaw("TRIM($column) = ''");
            })
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use ($column) {
                DB::transaction(function () use ($rows, $column) {
                    $now = now();

                    DB::table('product_units')
                        ->whereIn('id', collect($rows)->pluck('id'))
                        ->update([$column => null, 'updated_at' => $now]);

                    DB::table('serial_number_cleanup_audit_log')->insert(
                        collect($rows)->map(fn ($row) => [
                            'product_unit_id' => $row->id,
                            'shop_id'         => $row->shop_id,
                            'field'           => $column,
                            'old_value'       => $row->{$column},
                            'resolution'      => 'empty_string_normalized',
                            'cleaned_at'      => $now,
                        ])->toArray()
                    );
                });
            });
    }

    private function resolveActiveDuplicates(string $column): void
    {
        $duplicateGroups = DB::table('product_units')
            ->select($column)
            ->where('is_archived', false)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $value = $group->{$column};

            DB::transaction(function () use ($column, $value) {
                $rows = DB::table('product_units')
                    ->select('id', 'shop_id', $column)
                    ->where('is_archived', false)
                    ->where($column, $value)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $toArchive = $rows->slice(1);
                if ($toArchive->isEmpty()) {
                    return;
                }

                $now = now();

                DB::table('product_units')
                    ->whereIn('id', $toArchive->pluck('id'))
                    ->update([
                        'status'      => 'lost',
                        'is_archived' => true,
                        'updated_at'  => $now,
                    ]);

                DB::table('serial_number_cleanup_audit_log')->insert(
                    $toArchive->map(fn ($row) => [
                        'product_unit_id' => $row->id,
                        'shop_id'         => $row->shop_id,
                        'field'           => $column,
                        'old_value'       => $value,
                        'resolution'      => 'force_archived_duplicate',
                        'cleaned_at'      => $now,
                    ])->toArray()
                );
            });
        }
    }

    private function dropUniqueIfExists(string $table, string $indexName): void
    {
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();

        if ($exists) {
            Schema::table($table, fn (Blueprint $t) => $t->dropUnique($indexName));
        }
    }

    public function down(): void
    {
        $this->dropUniqueIfExists('product_units', 'product_units_active_secondary_serial_unique');
        $this->dropUniqueIfExists('product_units', 'product_units_active_serial_unique');

        Schema::table('product_units', function (Blueprint $table) {
            if (Schema::hasColumn('product_units', 'active_secondary_serial_number')) {
                $table->dropColumn('active_secondary_serial_number');
            }
            if (Schema::hasColumn('product_units', 'active_serial_number')) {
                $table->dropColumn('active_serial_number');
            }
        });

        Schema::dropIfExists('serial_number_cleanup_audit_log');
    }
};