<?php

namespace App\Livewire;

use App\Models\BackupJob;
use App\Support\BackupManager;
use Livewire\Component;
use Throwable;

class BackupIndex extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('settings.manage'), 403);
    }

    public function createBackup(): void
    {
        abort_unless(auth()->user()?->hasPermission('settings.manage'), 403);

        try {
            app(BackupManager::class)->createDatabaseBackup(auth()->user());
            session()->flash('status', 'Database backup created and checksum recorded.');
        } catch (Throwable $exception) {
            $this->addError('backup', $exception->getMessage());
        }
    }

    public function restore(int $backupId): void
    {
        abort_unless(auth()->user()?->hasPermission('settings.manage'), 403);

        try {
            app(BackupManager::class)->restoreDatabaseBackup(BackupJob::query()->findOrFail($backupId), auth()->user());
            session()->flash('status', 'Database restored. Reload the application before continuing.');
        } catch (Throwable $exception) {
            $this->addError('restore', $exception->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.backup-index', [
            'summary' => app(BackupManager::class)->summary(),
            'jobs' => BackupJob::query()->latest('created_at')->limit(50)->get(),
        ]);
    }
}
