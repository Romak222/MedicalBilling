<?php

namespace App\Providers;

use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        Window::open('main')
            ->title(config('pharmacy.nativephp.window_title', config('app.name')))
            ->route('dashboard')
            ->width((int) config('pharmacy.nativephp.window_width', 1366))
            ->height((int) config('pharmacy.nativephp.window_height', 768))
            ->minWidth(1024)
            ->minHeight(700)
            ->rememberState()
            ->suppressNewWindows();
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'max_execution_time' => '0',
        ];
    }
}
