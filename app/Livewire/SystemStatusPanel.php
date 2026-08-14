<?php

namespace App\Livewire;

use App\Support\SystemStatus;
use Livewire\Component;

class SystemStatusPanel extends Component
{
    public array $status = [];

    public function mount(SystemStatus $systemStatus): void
    {
        $this->status = $systemStatus->summary();
    }

    public function refreshStatus(): void
    {
        $this->status = app(SystemStatus::class)->summary();
    }

    public function render()
    {
        return view('livewire.system-status-panel');
    }
}
