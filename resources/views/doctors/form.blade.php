<x-layouts.app :title="config('app.name').' Doctor'">
    @isset($doctor)
        <livewire:doctor-form :record="$doctor" />
    @else
        <livewire:doctor-form />
    @endisset
</x-layouts.app>
