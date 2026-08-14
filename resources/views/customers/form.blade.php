<x-layouts.app :title="config('app.name').' Customer'">
    @isset($customer)
        <livewire:customer-form :record="$customer" />
    @else
        <livewire:customer-form />
    @endisset
</x-layouts.app>
