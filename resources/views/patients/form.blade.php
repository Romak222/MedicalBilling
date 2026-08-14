<x-layouts.app :title="config('app.name').' Patient'">
    @isset($patient)
        <livewire:patient-form :record="$patient" />
    @else
        <livewire:patient-form />
    @endisset
</x-layouts.app>
