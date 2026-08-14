<?php

namespace App\Support;

use App\Models\ProductBatch;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StockAdjustmentManager
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFinalizedAdjustment(array $payload, User $actor): StockAdjustment
    {
        return DB::transaction(function () use ($payload, $actor): StockAdjustment {
            $adjustment = StockAdjustment::query()->create([
                'adjustment_number' => strtoupper($this->blankToNull(Arr::get($payload, 'adjustment.adjustment_number')) ?: $this->nextNumber()),
                'adjustment_date' => $this->blankToNull(Arr::get($payload, 'adjustment.adjustment_date')) ?: today()->toDateString(),
                'status' => StockAdjustment::STATUS_FINALIZED,
                'reason' => trim((string) Arr::get($payload, 'adjustment.reason')),
                'notes' => $this->blankToNull(Arr::get($payload, 'adjustment.notes')),
                'finalized_at' => now(),
                'finalized_by' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $lines = $this->lineAttributes(Arr::get($payload, 'items', []), $actor, $adjustment);
            abort_if($lines === [], 422, 'Enter a changed counted quantity for at least one batch.');

            $adjustment->items()->createMany($lines);

            if (collect($lines)->sum(fn (array $line): int => $this->cents($line['value_amount'])) > 0) {
                app(AccountingManager::class)->postStockAdjustment($adjustment->load('items'), $actor);
            }

            app(AuditLogger::class)->record('stock_adjustment.created', $actor, $adjustment, [
                'adjustment_number' => $adjustment->adjustment_number,
                'line_count' => count($lines),
                'reason' => $adjustment->reason,
            ]);

            return $adjustment->refresh()->load(['items.productBatch.product', 'journalEntry']);
        });
    }

    public function nextNumber(): string
    {
        $next = (int) StockAdjustment::query()->max('id') + 1;

        return 'ADJ-'.today()->format('Ymd').'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function lineAttributes(array $items, User $actor, StockAdjustment $adjustment): array
    {
        $seen = [];
        $lines = [];

        foreach ($items as $item) {
            $batchId = (int) Arr::get($item, 'product_batch_id');
            abort_if(! $batchId || isset($seen[$batchId]), 422, 'Each stock batch can appear only once in an adjustment.');
            $seen[$batchId] = true;

            $batch = ProductBatch::query()->with('product')->lockForUpdate()->findOrFail($batchId);
            $countedQuantity = $this->decimalOrZero(Arr::get($item, 'counted_quantity'));
            abort_if($this->decimalToScaleInt($countedQuantity, 6) < 0, 422, 'Counted quantity cannot be negative.');

            $beforeQuantity = (string) $batch->available_quantity;
            $beforeMicros = $this->decimalToScaleInt($beforeQuantity, 6);
            $countedMicros = $this->decimalToScaleInt($countedQuantity, 6);
            $deltaMicros = $countedMicros - $beforeMicros;

            if ($deltaMicros === 0) {
                continue;
            }

            $valueCents = intdiv((abs($deltaMicros) * $this->cents($batch->purchase_rate)) + 500000, 1000000);
            $deltaQuantity = $this->formatScaled($deltaMicros, 6);

            $batch->update([
                'available_quantity' => $this->formatScaled($countedMicros, 6),
                'updated_by' => $actor->id,
            ]);

            StockMovement::query()->create([
                'product_id' => $batch->product_id,
                'product_batch_id' => $batch->id,
                'movement_type' => StockMovement::TYPE_STOCK_ADJUSTMENT,
                'source_type' => StockAdjustment::class,
                'source_id' => $adjustment->id,
                'quantity' => $deltaQuantity,
                'unit_cost' => $batch->purchase_rate,
                'notes' => 'Stock adjustment '.$adjustment->adjustment_number,
                'created_by' => $actor->id,
                'occurred_at' => now(),
            ]);

            $lines[] = [
                'product_batch_id' => $batch->id,
                'product_id' => $batch->product_id,
                'product_name_snapshot' => $batch->product?->name ?: 'Deleted product',
                'batch_number_snapshot' => $batch->batch_number,
                'before_quantity' => $beforeQuantity,
                'counted_quantity' => $this->formatScaled($countedMicros, 6),
                'delta_quantity' => $deltaQuantity,
                'unit_cost' => $batch->purchase_rate,
                'value_amount' => $this->formatCents($valueCents),
                'notes' => $this->blankToNull(Arr::get($item, 'notes')),
            ];
        }

        return $lines;
    }

    private function cents(mixed $value): int
    {
        return $this->decimalToScaleInt($value, 2);
    }

    private function decimalOrZero(mixed $value): string
    {
        return (string) ($this->blankToNull($value) ?? '0');
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = (string) ($this->blankToNull($value) ?? '0');
        $sign = str_starts_with($value, '-') ? -1 : 1;
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $sign * (((int) $whole * (10 ** $scale)) + (int) $fraction);
    }

    private function formatCents(int $value): string
    {
        return $this->formatScaled($value, 2);
    }

    private function formatScaled(int $value, int $scale): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);
        $base = 10 ** $scale;

        return sprintf('%s%d.%0'.$scale.'d', $sign, intdiv($value, $base), $value % $base);
    }

    private function blankToNull(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
