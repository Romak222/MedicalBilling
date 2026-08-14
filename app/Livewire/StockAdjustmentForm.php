<?php

namespace App\Livewire;

use App\Models\ProductBatch;
use App\Support\StockAdjustmentManager;
use Illuminate\Validation\Rule;
use Livewire\Component;

class StockAdjustmentForm extends Component
{
    public array $adjustment = [
        'adjustment_number' => '',
        'adjustment_date' => '',
        'reason' => '',
        'notes' => '',
    ];

    public array $items = [['product_batch_id' => '', 'counted_quantity' => '', 'notes' => '']];

    public string $newBatchId = '';

    public string $newCountedQuantity = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('inventory.adjust'), 403);

        $this->adjustment['adjustment_date'] = today()->toDateString();
        $this->items = [['product_batch_id' => '', 'counted_quantity' => '', 'notes' => '']];
    }

    public function addLine(): void
    {
        $this->validate([
            'newBatchId' => ['required', 'integer', 'exists:product_batches,id'],
            'newCountedQuantity' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,6})?$/'],
        ]);

        if (collect($this->items)->contains(fn (array $item): bool => (int) $item['product_batch_id'] === (int) $this->newBatchId)) {
            $this->addError('newBatchId', 'This batch is already in the adjustment.');

            return;
        }

        $line = [
            'product_batch_id' => $this->newBatchId,
            'counted_quantity' => $this->newCountedQuantity,
            'notes' => '',
        ];

        $blankIndex = collect($this->items)->search(fn (array $item): bool => ($item['product_batch_id'] ?? '') === '' && ($item['counted_quantity'] ?? '') === '');

        if ($blankIndex !== false) {
            $this->items[$blankIndex] = $line;
        } else {
            $this->items[] = $line;
        }
        $this->reset(['newBatchId', 'newCountedQuantity']);
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('inventory.adjust'), 403);

        $validated = $this->validate([
            'adjustment.adjustment_number' => ['nullable', 'string', 'max:80', Rule::unique('stock_adjustments', 'adjustment_number')],
            'adjustment.adjustment_date' => ['required', 'date_format:Y-m-d'],
            'adjustment.reason' => ['required', 'string', 'max:255'],
            'adjustment.notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_batch_id' => ['required', 'integer', 'exists:product_batches,id'],
            'items.*.counted_quantity' => ['required', 'regex:/^\d{1,12}(?:\.\d{1,6})?$/'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $adjustment = app(StockAdjustmentManager::class)->createFinalizedAdjustment($validated, auth()->user());

        session()->flash('status', 'Stock adjustment finalized and audited.');

        return $this->redirectRoute('inventory.adjustments.show', $adjustment, navigate: false);
    }

    public function render()
    {
        return view('livewire.stock-adjustment-form', [
            'batches' => ProductBatch::query()
                ->with('product')
                ->orderBy('expires_on')
                ->orderBy('batch_number')
                ->limit(500)
                ->get(),
        ]);
    }
}
