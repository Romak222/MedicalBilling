<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SystemStatus
{
    public function summary(): array
    {
        $this->ensureLocalDirectories();

        return [
            'application' => [
                'name' => config('app.name'),
                'version' => config('pharmacy.version'),
                'environment' => app()->environment(),
                'deployment_mode' => config('pharmacy.deployment_mode'),
                'store_code' => config('pharmacy.store_code'),
            ],
            'database' => $this->databaseStatus(),
            'offline' => [
                'label' => config('pharmacy.online_integrations_enabled')
                    ? 'Online integrations enabled'
                    : 'Offline core mode',
                'ready' => true,
                'outbox_required' => ! config('pharmacy.online_integrations_enabled'),
            ],
            'paths' => [
                'application_data' => $this->applicationDataPath(),
                'backup' => $this->backupPath(),
            ],
            'runtime' => [
                'php' => PHP_VERSION,
                'os' => PHP_OS_FAMILY,
            ],
        ];
    }

    public function applicationDataPath(): string
    {
        if ($path = config('pharmacy.paths.app_data')) {
            return $this->trimPath($path);
        }

        $localAppData = $_SERVER['LOCALAPPDATA'] ?? getenv('LOCALAPPDATA') ?: null;

        if (PHP_OS_FAMILY === 'Windows' && $localAppData) {
            return $this->trimPath($localAppData.DIRECTORY_SEPARATOR.'MedStore');
        }

        return storage_path('app/pharmacy-data');
    }

    public function backupPath(): string
    {
        if ($path = config('pharmacy.paths.backup')) {
            return $this->trimPath($path);
        }

        return $this->applicationDataPath().DIRECTORY_SEPARATOR.'backups';
    }

    private function ensureLocalDirectories(): void
    {
        File::ensureDirectoryExists($this->applicationDataPath());
        File::ensureDirectoryExists($this->backupPath());
    }

    private function databaseStatus(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $connection->select('select 1 as ok');

            return [
                'connected' => true,
                'connection' => $connection->getName(),
                'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
                'database' => $connection->getDatabaseName(),
                'message' => 'Connected',
            ];
        } catch (Throwable $exception) {
            return [
                'connected' => false,
                'connection' => config('database.default'),
                'driver' => null,
                'database' => null,
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function trimPath(string $path): string
    {
        return rtrim($path, '\\/');
    }
}
