<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrescriptionRegistry
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createPrescription(array $payload, User $actor): Prescription
    {
        return DB::transaction(function () use ($payload, $actor): Prescription {
            $patient = $this->patientRecord(Arr::get($payload, 'prescription.patient_id'));
            $doctor = $this->doctorRecord(Arr::get($payload, 'prescription.doctor_id'));
            $attachment = $this->storeAttachment(Arr::get($payload, 'attachment'));

            $prescription = Prescription::query()->create(array_merge(
                $this->prescriptionAttributes($payload, $patient, $doctor, $actor, $attachment),
                [
                    'prescription_number' => strtoupper($this->blankToNull(Arr::get($payload, 'prescription.prescription_number')) ?: $this->nextPrescriptionNumber()),
                    'created_by' => $actor->id,
                    'is_active' => true,
                ]
            ));

            foreach ($this->itemAttributes($payload) as $item) {
                $prescriptionItem = $prescription->items()->create($item);
                app(PrescriptionRefillTracker::class)->sync($prescriptionItem);
            }

            $this->syncStatus($prescription);
            app(AuditLogger::class)->record('prescription.created', $actor, $prescription, $this->auditMetadata($prescription));

            return $prescription->refresh()->load(['patient.customer', 'doctor', 'items.product']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updatePrescription(Prescription $prescription, array $payload, User $actor): Prescription
    {
        return DB::transaction(function () use ($prescription, $payload, $actor): Prescription {
            $prescription->load('items');
            abort_unless($prescription->isEditable(), 422, 'Dispensed prescriptions are read-only.');

            $patient = $this->patientRecord(Arr::get($payload, 'prescription.patient_id'));
            $doctor = $this->doctorRecord(Arr::get($payload, 'prescription.doctor_id'));
            $attachment = $this->replaceAttachment($prescription, Arr::get($payload, 'attachment'), (bool) Arr::get($payload, 'remove_attachment', false));

            $prescription->update($this->prescriptionAttributes($payload, $patient, $doctor, $actor, $attachment));
            $prescription->items()->delete();

            foreach ($this->itemAttributes($payload) as $item) {
                $prescriptionItem = $prescription->items()->create($item);
                app(PrescriptionRefillTracker::class)->sync($prescriptionItem);
            }

            $this->syncStatus($prescription);
            app(AuditLogger::class)->record('prescription.updated', $actor, $prescription, $this->auditMetadata($prescription));

            return $prescription->refresh()->load(['patient.customer', 'doctor', 'items.product']);
        });
    }

    public function archivePrescription(Prescription $prescription, User $actor): Prescription
    {
        return DB::transaction(function () use ($prescription, $actor): Prescription {
            $prescription->update([
                'is_active' => false,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('prescription.archived', $actor, $prescription, $this->auditMetadata($prescription));

            return $prescription->refresh()->load(['patient.customer', 'doctor', 'items.product']);
        });
    }

    public function restorePrescription(Prescription $prescription, User $actor): Prescription
    {
        return DB::transaction(function () use ($prescription, $actor): Prescription {
            $prescription->update([
                'is_active' => true,
                'updated_by' => $actor->id,
            ]);

            app(AuditLogger::class)->record('prescription.restored', $actor, $prescription, $this->auditMetadata($prescription));

            return $prescription->refresh()->load(['patient.customer', 'doctor', 'items.product']);
        });
    }

    public function incrementDispensedQuantity(PrescriptionItem $item, mixed $quantity): PrescriptionItem
    {
        $item->update([
            'quantity_dispensed' => $this->addDecimals($item->quantity_dispensed, $quantity, 6),
        ]);

        $this->syncStatus($item->prescription_id);
        app(PrescriptionRefillTracker::class)->sync($item->id);

        return $item->refresh();
    }

    public function decrementDispensedQuantity(PrescriptionItem $item, mixed $quantity): PrescriptionItem
    {
        $remaining = $this->decimalToScaleInt($item->quantity_dispensed, 6) - $this->decimalToScaleInt($quantity, 6);

        $item->update([
            'quantity_dispensed' => $this->formatScaled(max(0, $remaining), 6),
        ]);

        $this->syncStatus($item->prescription_id);
        app(PrescriptionRefillTracker::class)->sync($item->id);

        return $item->refresh();
    }

    public function syncStatus(Prescription|int $prescription): Prescription
    {
        $record = $prescription instanceof Prescription
            ? $prescription->load('items')
            : Prescription::query()->with('items')->findOrFail($prescription);

        $prescribedMicros = $record->items->sum(fn (PrescriptionItem $item): int => $this->decimalToScaleInt($item->quantity_prescribed, 6));
        $dispensedMicros = $record->items->sum(fn (PrescriptionItem $item): int => $this->decimalToScaleInt($item->quantity_dispensed, 6));

        $status = Prescription::STATUS_OPEN;

        if ($dispensedMicros > 0 && $dispensedMicros < $prescribedMicros) {
            $status = Prescription::STATUS_PARTIAL;
        }

        if ($prescribedMicros > 0 && $dispensedMicros >= $prescribedMicros) {
            $status = Prescription::STATUS_DISPENSED;
        }

        $record->update([
            'status' => $status,
        ]);

        return $record->refresh()->load('items');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function itemAttributes(array $payload): array
    {
        $items = collect(Arr::get($payload, 'items', []))
            ->map(function (array $item): ?array {
                $product = $this->productRecord(Arr::get($item, 'product_id'));
                $medicineName = $this->blankToNull(Arr::get($item, 'medicine_name_snapshot')) ?? $product?->name;
                $quantityPrescribed = $this->decimalOrZero(Arr::get($item, 'quantity_prescribed'));

                if ($medicineName === null || $this->decimalToScaleInt($quantityPrescribed, 6) <= 0) {
                    return null;
                }

                return [
                    'product_id' => $product?->id,
                    'medicine_name_snapshot' => $medicineName,
                    'unit_name_snapshot' => $this->blankToNull(Arr::get($item, 'unit_name_snapshot')) ?? $product?->baseUnit?->unit_name,
                    'dosage_instructions' => $this->blankToNull(Arr::get($item, 'dosage_instructions')),
                    'quantity_prescribed' => $quantityPrescribed,
                    'quantity_dispensed' => '0.000000',
                    'refill_interval_days' => $this->blankToNull(Arr::get($item, 'refill_interval_days')),
                    'refill_reminder_days' => (int) (Arr::get($item, 'refill_reminder_days', 0) ?: 0),
                    'last_dispensed_on' => null,
                    'next_refill_due_on' => null,
                    'notes' => $this->blankToNull(Arr::get($item, 'notes')),
                ];
            })
            ->filter()
            ->values()
            ->all();

        abort_if($items === [], 422, 'Add at least one prescribed medicine line.');

        return $items;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string|null>  $attachment
     * @return array<string, mixed>
     */
    private function prescriptionAttributes(array $payload, ?Patient $patient, ?Doctor $doctor, User $actor, array $attachment): array
    {
        abort_if(! $patient, 422, 'Select a patient record for this prescription.');

        return [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor?->id,
            'patient_name_snapshot' => $patient->full_name,
            'patient_phone_snapshot' => $patient->phone,
            'doctor_name_snapshot' => $doctor?->name ?? $this->blankToNull(Arr::get($payload, 'prescription.doctor_name_snapshot')) ?? $patient->primary_doctor_name,
            'prescription_date' => $this->blankToNull(Arr::get($payload, 'prescription.prescription_date')) ?: today()->toDateString(),
            'valid_until' => $this->blankToNull(Arr::get($payload, 'prescription.valid_until')),
            'attachment_path' => $attachment['path'],
            'attachment_original_name' => $attachment['original_name'],
            'attachment_mime_type' => $attachment['mime_type'],
            'notes' => $this->blankToNull(Arr::get($payload, 'prescription.notes')),
            'pharmacist_notes' => $this->blankToNull(Arr::get($payload, 'prescription.pharmacist_notes')),
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function replaceAttachment(Prescription $prescription, mixed $attachment, bool $removeAttachment = false): array
    {
        $current = [
            'path' => $prescription->attachment_path,
            'original_name' => $prescription->attachment_original_name,
            'mime_type' => $prescription->attachment_mime_type,
        ];

        if ($removeAttachment) {
            $this->deleteAttachment($prescription->attachment_path);

            $current = [
                'path' => null,
                'original_name' => null,
                'mime_type' => null,
            ];
        }

        if (! $attachment instanceof UploadedFile) {
            return $current;
        }

        $this->deleteAttachment($prescription->attachment_path);

        return $this->storeAttachment($attachment);
    }

    /**
     * @return array<string, string|null>
     */
    private function storeAttachment(mixed $attachment): array
    {
        if (! $attachment instanceof UploadedFile) {
            return [
                'path' => null,
                'original_name' => null,
                'mime_type' => null,
            ];
        }

        $path = $attachment->store('prescriptions', 'local');

        return [
            'path' => $path,
            'original_name' => $attachment->getClientOriginalName(),
            'mime_type' => $attachment->getClientMimeType(),
        ];
    }

    private function deleteAttachment(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function auditMetadata(Prescription $prescription): array
    {
        $prescription->loadMissing(['patient', 'doctor', 'items']);

        return [
            'prescription_number' => $prescription->prescription_number,
            'patient_id' => $prescription->patient_id,
            'doctor_id' => $prescription->doctor_id,
            'status' => $prescription->status,
            'attachment_present' => $prescription->attachment_path !== null,
            'item_count' => $prescription->items->count(),
        ];
    }

    private function nextPrescriptionNumber(): string
    {
        $prefix = 'RX-'.now()->format('Ymd');
        $next = Prescription::query()
            ->where('prescription_number', 'like', $prefix.'-%')
            ->count() + 1;

        return $prefix.'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function patientRecord(mixed $patientId): ?Patient
    {
        $patientId = $this->blankToNull($patientId);

        if ($patientId === null) {
            return null;
        }

        return Patient::query()->with(['customer', 'doctor'])->findOrFail($patientId);
    }

    private function doctorRecord(mixed $doctorId): ?Doctor
    {
        $doctorId = $this->blankToNull($doctorId);

        if ($doctorId === null) {
            return null;
        }

        return Doctor::query()->findOrFail($doctorId);
    }

    private function productRecord(mixed $productId): ?Product
    {
        $productId = $this->blankToNull($productId);

        if ($productId === null) {
            return null;
        }

        return Product::query()->with('baseUnit')->findOrFail($productId);
    }

    private function addDecimals(mixed $left, mixed $right, int $scale = 2): string
    {
        return $this->formatScaled(
            $this->decimalToScaleInt($left, $scale) + $this->decimalToScaleInt($right, $scale),
            $scale
        );
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

    private function decimalOrZero(mixed $value): string
    {
        return (string) ($this->blankToNull($value) ?? '0');
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
