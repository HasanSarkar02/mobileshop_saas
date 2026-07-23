<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data-integrity remediation, run BEFORE the unique constraint migration.
     *
     * Resolves two classes of pre-existing dirty data that would otherwise
     * violate a (shop_id, barcode) unique index:
     *
     *   1. Empty-string barcodes ('') stored where NULL should have been used.
     *      MySQL unique indexes treat '' as a real value, not an absence of
     *      value, so multiple '' rows per shop collide.
     *
     *   2. Genuine duplicate non-empty barcodes across different variants in
     *      the same shop — real data collisions that pre-date any uniqueness
     *      enforcement, requiring a deterministic, auditable resolution
     *      rather than a silent overwrite.
     *
     * Every row this migration mutates is recorded in
     * `barcode_cleanup_audit_log` with the old value, so shop owners /
     * support can review exactly what changed, per shop, after the fact.
     */
    public function up(): void
    {
        Schema::create('barcode_cleanup_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id')->index();
            $table->unsignedBigInteger('product_variant_id');
            $table->string('sku')->nullable();
            $table->string('old_barcode')->nullable();
            $table->string('new_barcode')->nullable();
            $table->string('reason', 50); // 'empty_string_normalized' | 'duplicate_collision_nulled'
            $table->timestamp('cleaned_at');
        });

        // ── Step 1: normalize empty-string / whitespace-only barcodes to NULL ──
        // Chunked by primary key to avoid a single long-running lock on a
        // multi-million-row table, and to keep each transaction small enough
        // to be safely interruptible/resumable in production.
        DB::table('product_variants')
            ->select('id', 'shop_id', 'sku', 'barcode')
            ->whereNotNull('barcode')
            ->where(function ($q) {
                $q->where('barcode', '')
                  ->orWhereRaw("TRIM(barcode) = ''");
            })
            ->orderBy('id')
            ->chunkById(1000, function ($rows) {
                DB::transaction(function () use ($rows) {
                    $now = now();
                    $auditRows = [];

                    foreach ($rows as $row) {
                        $auditRows[] = [
                            'shop_id'             => $row->shop_id,
                            'product_variant_id'  => $row->id,
                            'sku'                 => $row->sku,
                            'old_barcode'          => $row->barcode,
                            'new_barcode'          => null,
                            'reason'               => 'empty_string_normalized',
                            'cleaned_at'            => $now,
                        ];
                    }

                    DB::table('product_variants')
                        ->whereIn('id', collect($rows)->pluck('id'))
                        ->update(['barcode' => null, 'updated_at' => $now]);

                    DB::table('barcode_cleanup_audit_log')->insert($auditRows);
                });
            });

        // ── Step 2: resolve genuine duplicate non-empty barcodes per shop ──
        // Policy: within each (shop_id, barcode) collision group, the
        // longest-standing record (lowest id / earliest created_at) keeps
        // the barcode as the presumed original source of truth. Every other
        // colliding row is nulled out and logged — never silently mutated
        // to a different value, since a synthetic replacement barcode could
        // mask a real inventory identification error the shop owner needs
        // to see and correct themselves.
        $duplicateGroups = DB::table('product_variants')
            ->select('shop_id', 'barcode')
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->groupBy('shop_id', 'barcode')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            DB::transaction(function () use ($group) {
                $variants = DB::table('product_variants')
                    ->select('id', 'shop_id', 'sku', 'barcode')
                    ->where('shop_id', $group->shop_id)
                    ->where('barcode', $group->barcode)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                // Keep the first (oldest) row untouched; null the rest.
                $toNull = $variants->slice(1);

                if ($toNull->isEmpty()) {
                    return;
                }

                $now = now();
                $auditRows = $toNull->map(fn ($v) => [
                    'shop_id'             => $v->shop_id,
                    'product_variant_id'  => $v->id,
                    'sku'                 => $v->sku,
                    'old_barcode'          => $v->barcode,
                    'new_barcode'          => null,
                    'reason'               => 'duplicate_collision_nulled',
                    'cleaned_at'            => $now,
                ])->toArray();

                DB::table('product_variants')
                    ->whereIn('id', $toNull->pluck('id'))
                    ->update(['barcode' => null, 'updated_at' => $now]);

                DB::table('barcode_cleanup_audit_log')->insert($auditRows);
            });
        }
    }

    public function down(): void
    {
        // Intentionally irreversible in the strict sense — restoring nulled
        // barcodes from `barcode_cleanup_audit_log.old_barcode` would simply
        // reintroduce the same integrity violation this migration exists to
        // fix. The audit table itself is left in place as the permanent,
        // queryable record of what was changed; it is not dropped here so
        // that support tooling retains it even if this migration is rolled
        // back for unrelated reasons.
    }
};