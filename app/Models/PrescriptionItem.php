<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrescriptionItem extends Model
{
    public const REFILL_STATUS_UNSCHEDULED = 'unscheduled';

    public const REFILL_STATUS_PENDING = 'pending';

    public const REFILL_STATUS_UPCOMING = 'upcoming';

    public const REFILL_STATUS_DUE = 'due';

    public const REFILL_STATUS_OVERDUE = 'overdue';

    public const REFILL_STATUS_COMPLETED = 'completed';

    public const REFILL_STATUS_EXPIRED = 'expired';

    public const REFILL_STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'prescription_id',
        'product_id',
        'medicine_name_snapshot',
        'unit_name_snapshot',
        'dosage_instructions',
        'quantity_prescribed',
        'quantity_dispensed',
        'refill_interval_days',
        'refill_reminder_days',
        'last_dispensed_on',
        'next_refill_due_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_prescribed' => 'decimal:6',
            'quantity_dispensed' => 'decimal:6',
            'refill_interval_days' => 'integer',
            'refill_reminder_days' => 'integer',
            'last_dispensed_on' => 'date',
            'next_refill_due_on' => 'date',
        ];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function salesInvoiceItems(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function controlledMedicineRegisterEntries(): HasMany
    {
        return $this->hasMany(ControlledMedicineRegisterEntry::class);
    }

    public function refillTrackingEnabled(): bool
    {
        return (int) ($this->refill_interval_days ?? 0) > 0;
    }

    public function remainingQuantity(): string
    {
        return $this->formatScaled($this->remainingQuantityMicros(), 6);
    }

    public function remainingQuantityMicros(): int
    {
        return max(
            0,
            $this->decimalToScaleInt($this->quantity_prescribed, 6) - $this->decimalToScaleInt($this->quantity_dispensed, 6)
        );
    }

    public function refillStatus(?CarbonInterface $today = null): string
    {
        $today = $today?->copy()->startOfDay() ?? today();

        if (! $this->refillTrackingEnabled()) {
            return self::REFILL_STATUS_UNSCHEDULED;
        }

        if (! $this->prescription?->is_active) {
            return self::REFILL_STATUS_ARCHIVED;
        }

        if ($this->remainingQuantityMicros() <= 0) {
            return self::REFILL_STATUS_COMPLETED;
        }

        if ($this->prescription?->valid_until?->lt($today)) {
            return self::REFILL_STATUS_EXPIRED;
        }

        if (! $this->last_dispensed_on || ! $this->next_refill_due_on) {
            return self::REFILL_STATUS_PENDING;
        }

        $nextDue = $this->next_refill_due_on->copy()->startOfDay();
        $reminderLead = max(0, (int) ($this->refill_reminder_days ?? 0));

        if ($nextDue->lt($today)) {
            return self::REFILL_STATUS_OVERDUE;
        }

        if ($nextDue->lte($today->copy()->addDays($reminderLead))) {
            return self::REFILL_STATUS_DUE;
        }

        return self::REFILL_STATUS_UPCOMING;
    }

    public function refillStatusLabel(?CarbonInterface $today = null): string
    {
        return match ($this->refillStatus($today)) {
            self::REFILL_STATUS_PENDING => 'Pending first fill',
            self::REFILL_STATUS_UPCOMING => 'Upcoming',
            self::REFILL_STATUS_DUE => 'Due soon',
            self::REFILL_STATUS_OVERDUE => 'Overdue',
            self::REFILL_STATUS_COMPLETED => 'Completed',
            self::REFILL_STATUS_EXPIRED => 'Expired',
            self::REFILL_STATUS_ARCHIVED => 'Archived',
            default => 'Not scheduled',
        };
    }

    private function decimalToScaleInt(mixed $value, int $scale): int
    {
        $value = trim((string) ($value ?? '0'));
        $sign = str_starts_with($value, '-') ? -1 : 1;
        $value = ltrim($value, '+-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad(substr($fraction, 0, $scale), $scale, '0');

        return $sign * (((int) $whole * (10 ** $scale)) + (int) $fraction);
    }

    private function formatScaled(int $value, int $scale): string
    {
        $sign = $value < 0 ? '-' : '';
        $value = abs($value);
        $base = 10 ** $scale;

        return sprintf('%s%d.%0'.$scale.'d', $sign, intdiv($value, $base), $value % $base);
    }
}
