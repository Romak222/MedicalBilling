<x-layouts.app :title="config('app.name').' Product Option'">
    <livewire:catalogue-option-manager :type="$type" :record="$record ?? null" />
</x-layouts.app>
