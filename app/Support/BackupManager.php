<?php

namespace App\Support;

use App\Models\BackupJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class BackupManager
{
    public function createDatabaseBackup(User $actor): BackupJob
    {
        $backupDirectory = app(SystemStatus::class)->backupPath();
        File::ensureDirectoryExists($backupDirectory);

        $fileName = 'medstore-database-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.sqlite';
        $path = $backupDirectory.DIRECTORY_SEPARATOR.$fileName;
        $job = BackupJob::query()->create([
            'job_type' => BackupJob::TYPE_DATABASE,
            'status' => BackupJob::STATUS_RUNNING,
            'file_name' => $fileName,
            'file_path' => $path,
            'started_at' => now(),
            'created_by' => $actor->id,
        ]);

        try {
            $this->exportDatabase($path);
            $job->update([
                'status' => BackupJob::STATUS_COMPLETED,
                'checksum' => hash_file('sha256', $path),
                'file_size' => filesize($path) ?: 0,
                'completed_at' => now(),
                'message' => 'Database backup completed.',
            ]);

            app(AuditLogger::class)->record('backup.created', $actor, $job, [
                'file_name' => $fileName,
                'file_size' => $job->file_size,
                'checksum' => $job->checksum,
            ]);
        } catch (Throwable $exception) {
            $job->update([
                'status' => BackupJob::STATUS_FAILED,
                'message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        }

        return $job->refresh();
    }

    public function restoreDatabaseBackup(BackupJob $job, User $actor): BackupJob
    {
        abort_unless($job->status === BackupJob::STATUS_COMPLETED, 422, 'Only a completed backup can be restored.');
        abort_unless(File::exists($job->file_path), 422, 'The selected backup file is no longer available.');
        abort_unless($this->isValidSqliteFile($job->file_path), 422, 'The selected backup is not a valid MedStore database.');

        $databasePath = $this->databasePath();
        abort_unless($databasePath && $databasePath !== ':memory:', 422, 'Database restore is available only for a file-backed local database.');

        $safetyPath = dirname($databasePath).DIRECTORY_SEPARATOR.'medstore-pre-restore-'.now()->format('Ymd-His').'.sqlite';
        $this->exportDatabase($safetyPath);

        DB::disconnect();
        File::copy($job->file_path, $databasePath);
        DB::purge();

        app(AuditLogger::class)->record('backup.restored', $actor, $job, [
            'file_name' => $job->file_name,
            'safety_backup' => $safetyPath,
        ]);

        return $job->refresh();
    }

    public function summary(): array
    {
        $path = app(SystemStatus::class)->backupPath();

        return [
            'path' => $path,
            'directory_exists' => File::isDirectory($path),
            'writable' => File::isDirectory($path) ? is_writable($path) : false,
            'latest' => BackupJob::query()->where('status', BackupJob::STATUS_COMPLETED)->latest('completed_at')->first(),
        ];
    }

    private function exportDatabase(string $path): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            $pdo->exec('PRAGMA wal_checkpoint(FULL)');
            $pdo->exec('VACUUM INTO '.$pdo->quote($path));

            return;
        }

        $source = $this->databasePath();
        abort_unless($source && File::exists($source), 422, 'The configured database cannot be copied for backup.');
        File::copy($source, $path);
    }

    private function isValidSqliteFile(string $path): bool
    {
        try {
            $pdo = new \PDO('sqlite:'.$path);
            $result = $pdo->query("select count(*) from sqlite_master where type = 'table' and name = 'migrations'");

            return (int) $result?->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function databasePath(): ?string
    {
        $path = config('database.connections.'.config('database.default').'.database');

        return is_string($path) ? $path : null;
    }
}
