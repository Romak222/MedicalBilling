<?php

namespace App\Livewire;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Support\PrescriptionRegistry;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class PrescriptionForm extends Component
{
    use WithFileUploads;

    public ?int $prescriptionId = null;

    public mixed $attachment = null;

    public bool $removeAttachment = false;

    public bool $readOnly = false;

    public ?string $existingAttachmentName = null;

    public array $prescription = [
        'prescription_number' => '',
        'patient_id' => '',
        'doctor_id' => '',
        'doctor_name_snapshot' => '',
        'prescription_date' => '',
        'valid_until' => '',
        'notes' => '',
        'pharmacist_notes' => '',
    ];

    public array $items = [];

    public function mount(?Prescription $record = null): void
    {
        abort_unless(auth()->user()?->hasPermission('prescriptions.manage'), 403);

        $this->prescription['prescription_date'] = today()->format('Y-m-d');
        $this->items = [$this->blankItem()];

        if ($record?->exists) {
            $this->fillFromPrescription($record->load(['patient', 'doctor', 'items.product']));
        }
    }

    public function save(): mixed
    {
        abort_unless(auth()->user()?->hasPermission('prescriptions.manage'), 403);

        $this->prescription['patient_id'] = $this->prescription['patient_id'] === '' ? null : $this->prescription['patient_id'];
        $this->prescription['doctor_id'] = $this->prescription['doctor_id'] === '' ? null : $this->prescription['doctor_id'];
        $validated = $this->validate();
        $validated['attachment'] = $this->attachment;
        $validated['remove_attachment'] = $this->removeAttachment;
        $registry = app(PrescriptionRegistry::class);

        if ($this->prescriptionId) {
            $prescription = $registry->updatePrescription(Prescription::query()->findOrFail($this->prescriptionId), $validated, auth()->user());
            session()->flash('status', 'Prescription updated.');
        } else {
            $prescription = $registry->createPrescription($validated, auth()->user());
            session()->flash('status', 'Prescription added.');
        }

        return $this->redirectRoute('prescriptions.show', $prescription, navigate: false);
    }

    public function addItem(): void
    {
        if ($this->readOnly) {
            return;
        }

        $this->items[] = $this->blankItem();
    }

    public function removeItem(int $index): void
    {
        if ($this->readOnly) {
            return;
        }

        if (count($this->items) === 1) {
            $this->items[0] = $this->blankItem();

            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function useProduct(int $index): void
    {
        if (! isset($this->items[$index]) || $this->items[$index]['product_id'] === '') {
            return;
        }

        $product = Product::query()->with('baseUnit')->findOrFail($this->items[$index]['product_id']);
        $this->items[$index]['medicine_name_snapshot'] = $product->name;
        $this->items[$index]['unit_name_snapshot'] = $product->baseUnit?->unit_name ?? $this->items[$index]['unit_name_snapshot'];
    }

    public function updatedPrescriptionPatientId(mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $patient = Patient::query()->with('doctor')->findOrFail($value);

        if ($patient->doctor && $this->prescription['doctor_id'] === '') {
            $this->prescription['doctor_id'] = (string) $patient->doctor->id;
            $this->prescription['doctor_name_snapshot'] = $patient->doctor->name;
        } elseif ($this->prescription['doctor_name_snapshot'] === '' && $patient->primary_doctor_name) {
            $this->prescription['doctor_name_snapshot'] = $patient->primary_doctor_name;
        }
    }

    public function updatedPrescriptionDoctorId(mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $doctor = Doctor::query()->findOrFail($value);
        $this->prescription['doctor_name_snapshot'] = $doctor->name;
    }

    public function render()
    {
        return view('livewire.prescription-form', [
            'patients' => Patient::query()->with(['customer', 'doctor'])->where('is_active', true)->orderBy('full_name')->get(),
            'doctors' => Doctor::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->with('baseUnit')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'prescription.prescription_number' => ['nullable', 'string', 'max:80', Rule::unique('prescriptions', 'prescription_number')->ignore($this->prescriptionId)],
            'prescription.patient_id' => ['required', 'integer', 'exists:patients,id'],
            'prescription.doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'prescription.doctor_name_snapshot' => ['nullable', 'string', 'max:180'],
            'prescription.prescription_date' => ['required', 'date', 'before_or_equal:today'],
            'prescription.valid_until' => ['nullable', 'date', 'after_or_equal:prescription.prescription_date'],
            'prescription.notes' => ['nullable', 'string', 'max:5000'],
            'prescription.pharmacist_notes' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:4096'],
            'removeAttachment' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.medicine_name_snapshot' => ['nullable', 'string', 'max:180'],
            'items.*.unit_name_snapshot' => ['nullable', 'string', 'max:80'],
            'items.*.dosage_instructions' => ['nullable', 'string', 'max:180'],
            'items.*.quantity_prescribed' => ['required', 'regex:/^(?!0+(?:\\.0+)?$)\\d{1,5}(?:\\.\\d{1,6})?$/'],
            'items.*.refill_interval_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'items.*.refill_reminder_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'items.*.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function blankItem(): array
    {
        return [
            'product_id' => '',
            'medicine_name_snapshot' => '',
            'unit_name_snapshot' => '',
            'dosage_instructions' => '',
            'quantity_prescribed' => '1.000000',
            'refill_interval_days' => '',
            'refill_reminder_days' => '0',
            'notes' => '',
        ];
    }

    private function fillFromPrescription(Prescription $prescription): void
    {
        $this->prescriptionId = $prescription->id;
        $this->readOnly = ! $prescription->isEditable();
        $this->existingAttachmentName = $prescription->attachment_original_name;
        $this->prescription = [
            'prescription_number' => $prescription->prescription_number,
            'patient_id' => (string) $prescription->patient_id,
            'doctor_id' => $prescription->doctor_id ? (string) $prescription->doctor_id : '',
            'doctor_name_snapshot' => $prescription->doctor_name_snapshot ?? '',
            'prescription_date' => $prescription->prescription_date?->format('Y-m-d') ?? '',
            'valid_until' => $prescription->valid_until?->format('Y-m-d') ?? '',
            'notes' => $prescription->notes ?? '',
            'pharmacist_notes' => $prescription->pharmacist_notes ?? '',
        ];
        $this->items = $prescription->items->map(fn ($item): array => [
            'product_id' => $item->product_id ? (string) $item->product_id : '',
            'medicine_name_snapshot' => $item->medicine_name_snapshot,
            'unit_name_snapshot' => $item->unit_name_snapshot ?? '',
            'dosage_instructions' => $item->dosage_instructions ?? '',
            'quantity_prescribed' => $item->quantity_prescribed,
            'refill_interval_days' => $item->refill_interval_days === null ? '' : (string) $item->refill_interval_days,
            'refill_reminder_days' => (string) ($item->refill_reminder_days ?? 0),
            'notes' => $item->notes ?? '',
        ])->values()->all();
    }
}
