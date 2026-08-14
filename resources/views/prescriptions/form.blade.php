<x-layouts.app :title="config('app.name').' Prescription'">
    @isset($prescription)
        <livewire:prescription-form :record="$prescription" />
    @else
        <livewire:prescription-form />
    @endisset
</x-layouts.app>
