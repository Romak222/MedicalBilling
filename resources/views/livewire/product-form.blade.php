<x-app-shell :page-title="$productId ? 'Edit Product' : 'Add New Product'" section-label="Product Master">
    <x-slot:actions>
        <a href="{{ route('products.index') }}" class="btn-secondary">Back to Products</a>
        <a href="{{ route('catalogue.masters') }}" class="btn-secondary">Product Options</a>
        <button type="button" wire:click="save" class="btn-primary">{{ $productId ? 'Save Changes' : 'Create Product' }}</button>
    </x-slot>

    <form wire:submit="save" class="space-y-5">
        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Identity</p>
                <h2 class="text-lg font-semibold text-ink-950">Core Product Details</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Product Name</span>
                    <input wire:model="product.name" type="text" class="field-control mt-1">
                    @error('product.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">SKU</span>
                    <input wire:model="product.sku" type="text" class="field-control mt-1">
                    @error('product.sku') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Generic Name</span>
                    <input wire:model="product.generic_name" type="text" class="field-control mt-1">
                    @error('product.generic_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-medium text-ink-700">Composition</span>
                    <textarea wire:model="product.composition" rows="3" class="field-control mt-1"></textarea>
                    @error('product.composition') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Masters</p>
                <h2 class="text-lg font-semibold text-ink-950">Category, Manufacturer and Tax</h2>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-3">
                <div class="space-y-3">
                    <label class="block">
                        <span class="text-sm font-medium text-ink-700">Manufacturer</span>
                        <select wire:model="manufacturer.id" class="field-control mt-1">
                            <option value="">Create or type below</option>
                            @foreach ($manufacturers as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-ink-700">New Manufacturer</span>
                        <input wire:model="manufacturer.name" type="text" class="field-control mt-1" @disabled($manufacturer['id'] !== '')>
                        @error('manufacturer.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>
                </div>

                <div class="space-y-3">
                    <label class="block">
                        <span class="text-sm font-medium text-ink-700">Category</span>
                        <select wire:model="category.id" class="field-control mt-1">
                            <option value="">Create or type below</option>
                            @foreach ($categories as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-ink-700">New Category</span>
                        <input wire:model="category.name" type="text" class="field-control mt-1" @disabled($category['id'] !== '')>
                        @error('category.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>
                </div>

                <div class="space-y-3">
                    <label class="block">
                        <span class="text-sm font-medium text-ink-700">Tax Rate</span>
                        <select wire:model="tax_rate.id" class="field-control mt-1">
                            <option value="">Create or type below</option>
                            @foreach ($taxRates as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} / {{ $item->rate_percent }}%</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">New Tax Label</span>
                            <input wire:model="tax_rate.name" type="text" class="field-control mt-1" @disabled($tax_rate['id'] !== '')>
                            @error('tax_rate.name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-ink-700">Rate %</span>
                            <input wire:model="tax_rate.rate_percent" type="text" inputmode="decimal" class="field-control mt-1" @disabled($tax_rate['id'] !== '')>
                            @error('tax_rate.rate_percent') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </div>
            </div>
        </section>

        <section class="surface-panel p-5">
            <div class="flex flex-col gap-1 border-b border-slate-200 pb-4">
                <p class="section-kicker">Medicine Metadata</p>
                <h2 class="text-lg font-semibold text-ink-950">Form, Pack, Regulatory and Unit Details</h2>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                <div class="block">
                    <span class="text-sm font-medium text-ink-700">Product Type</span>
                    <select
                        class="field-control mt-1"
                        wire:model="selectedProductTypeMasterId"
                        wire:change="useProductTypeMaster"
                    >
                        <option value="">Choose saved type</option>
                        @foreach ($productTypes as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <input wire:model="product.product_type" type="text" class="field-control mt-2">
                    @error('product.product_type') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </div>

                <div class="block">
                    <span class="text-sm font-medium text-ink-700">Form</span>
                    <select
                        class="field-control mt-1"
                        wire:model="selectedDosageFormMasterId"
                        wire:change="useDosageFormMaster"
                    >
                        <option value="">Choose saved form</option>
                        @foreach ($dosageForms as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <input wire:model="product.form" type="text" class="field-control mt-2">
                    @error('product.form') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </div>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Strength</span>
                    <input wire:model="product.strength" type="text" class="field-control mt-1">
                    @error('product.strength') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Pack Size</span>
                    <input wire:model="product.pack_size" type="text" class="field-control mt-1">
                    @error('product.pack_size') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">HSN Code</span>
                    <input wire:model="product.hsn_code" type="text" class="field-control mt-1">
                    @error('product.hsn_code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <div class="block">
                    <span class="text-sm font-medium text-ink-700">Schedule Label</span>
                    <select
                        class="field-control mt-1"
                        wire:model="selectedScheduleLabelMasterId"
                        wire:change="useScheduleLabelMaster"
                    >
                        <option value="">Choose saved label</option>
                        @foreach ($scheduleLabels as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <input wire:model="product.schedule_label" type="text" class="field-control mt-2">
                    @error('product.schedule_label') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </div>

                <div class="block">
                    <span class="text-sm font-medium text-ink-700">Base Unit</span>
                    <select
                        class="field-control mt-1"
                        wire:model="selectedUnitMasterId"
                        wire:change="useUnitMaster"
                    >
                        <option value="">Choose saved unit</option>
                        @foreach ($units as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} / {{ $item->code }}</option>
                        @endforeach
                    </select>
                    <input wire:model="unit.unit_name" type="text" placeholder="Unit name" class="field-control mt-2">
                    @error('unit.unit_name') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </div>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Unit Code</span>
                    <input wire:model="unit.unit_code" type="text" class="field-control mt-1 uppercase">
                    @error('unit.unit_code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Conversion Factor</span>
                    <input wire:model="unit.conversion_factor" type="text" inputmode="decimal" class="field-control mt-1">
                    @error('unit.conversion_factor') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <div x-data="barcodeScanner()" class="block lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-medium text-ink-700">Barcode</span>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" x-on:click="focusBarcodeInput()" class="btn-secondary px-3 py-1.5 text-xs">USB Scan</button>
                            <button type="button" x-on:click="startCamera($wire)" class="btn-secondary px-3 py-1.5 text-xs">Scan Barcode</button>
                        </div>
                    </div>
                    <input
                        x-ref="barcodeInput"
                        wire:model="barcode.barcode"
                        x-on:keydown.enter.prevent="markKeyboardScan()"
                        type="text"
                        class="field-control mt-1"
                    >
                    <p x-cloak x-show="scannerStatus" x-text="scannerStatus" class="mt-2 text-xs font-medium text-medical-700"></p>
                    <p x-cloak x-show="scannerError" x-text="scannerError" class="mt-2 text-xs font-medium text-red-700"></p>
                    @error('barcode.barcode') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror

                    <div
                        x-cloak
                        x-show="open"
                        x-transition.opacity
                        x-on:click.self="stop()"
                        x-on:keydown.escape.window="stop()"
                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4"
                    >
                        <div class="surface-panel w-full max-w-xl p-5">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                                <div>
                                    <p class="section-kicker">Camera Scanner</p>
                                    <h3 class="mt-1 text-lg font-semibold text-ink-950">Scan Barcode</h3>
                                </div>
                                <button type="button" x-on:click="stop()" class="btn-secondary px-3 py-1.5 text-xs">Close</button>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-lg border border-slate-300 bg-slate-950">
                                <video x-ref="video" playsinline muted class="aspect-video w-full object-cover"></video>
                            </div>

                            <p x-cloak x-show="scannerStatus" x-text="scannerStatus" class="mt-3 text-sm font-medium text-medical-700"></p>
                            <p x-cloak x-show="scannerError" x-text="scannerError" class="mt-3 text-sm font-medium text-red-700"></p>

                            <div class="mt-4 flex flex-wrap justify-end gap-2">
                                <button type="button" x-on:click="focusBarcodeInput(); stop()" class="btn-secondary">Use USB Scanner</button>
                                <button type="button" x-on:click="startCamera($wire)" class="btn-primary">Retry Camera</button>
                            </div>
                        </div>
                    </div>
                </div>

                <label class="block">
                    <span class="text-sm font-medium text-ink-700">Barcode Type</span>
                    <input wire:model="barcode.barcode_type" type="text" class="field-control mt-1">
                    @error('barcode.barcode_type') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                <div class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50/80 p-4 sm:grid-cols-2 lg:col-span-4">
                    <label class="flex items-center gap-3 text-sm font-medium text-ink-700">
                        <input wire:model="product.prescription_required" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-medical-700 focus:ring-medical-600">
                        Prescription Required
                    </label>

                    <label class="flex items-center gap-3 text-sm font-medium text-ink-700">
                        <input wire:model="product.controlled_medicine" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-medical-700 focus:ring-medical-600">
                        Controlled Medicine
                    </label>
                </div>
            </div>
        </section>

        <div class="sticky bottom-0 flex items-center justify-end gap-3 rounded-lg border border-white/80 bg-white/95 p-4 shadow-lg shadow-slate-900/10 backdrop-blur">
            <a href="{{ route('products.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $productId ? 'Save Changes' : 'Create Product' }}</button>
        </div>
    </form>
</x-app-shell>
