<?php

namespace App\Livewire;

use App\Support\HardwareStatus;
use Livewire\Component;

class HardwareStatusPanel extends Component
{
    public array $hardware = [];

    public function mount(HardwareStatus $status): void
    {
        abort_unless(auth()->user()?->hasPermission('system.status.view'), 403);
        $this->hardware = $status->summary();
    }

    public function refreshHardware(): void
    {
        $this->hardware = app(HardwareStatus::class)->summary();
    }

    public function render()
    {
        return view('livewire.hardware-status-panel');
    }
}
