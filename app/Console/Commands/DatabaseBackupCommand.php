<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'app:backup-database';

    protected $description = 'Create a database backup and remove expired backup files';

    public function handle(): int
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}");
        $driver = $connection['driver'] ?? null;
        $directory = config('backup.path');
        $timestamp = now()->format('Y-m-d_H-i-s');

        File::ensureDirectoryExists($directory);

        try {
            $path = match ($driver) {
                'sqlite' => $this->backupSqlite($connection, $directory, $timestamp),
                'mysql', 'mariadb' => $this->backupMysql($connection, $directory, $timestamp),
                'pgsql' => $this->backupPostgres($connection, $directory, $timestamp),
                default => throw new RuntimeException("Database driver [{$driver}] is not supported."),
            };

            $this->removeExpiredBackups($directory);
            $this->writeStatus($directory, [
                'status' => 'successful',
                'driver' => $driver,
                'path' => $path,
                'size' => File::size($path),
                'completed_at' => now()->toIso8601String(),
                'message' => null,
            ]);

            $this->info('Database backup created: '.$path);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->writeStatus($directory, [
                'status' => 'failed',
                'driver' => $driver,
                'path' => null,
                'size' => null,
                'completed_at' => now()->toIso8601String(),
                'message' => $exception->getMessage(),
            ]);
            Log::critical('Database backup failed.', [
                'driver' => $driver,
                'exception' => $exception,
            ]);
            $this->error('Database backup failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function backupSqlite(array $connection, string $directory, string $timestamp): string
    {
        $source = $connection['database'] ?? null;

        if (! is_string($source) || ! File::isFile($source)) {
            throw new RuntimeException('The configured SQLite database file does not exist.');
        }

        $path = $directory.DIRECTORY_SEPARATOR."database_{$timestamp}.sqlite";

        if (! File::copy($source, $path)) {
            throw new RuntimeException('The SQLite database file could not be copied.');
        }

        return $path;
    }

    private function backupMysql(array $connection, string $directory, string $timestamp): string
    {
        $path = $directory.DIRECTORY_SEPARATOR."database_{$timestamp}.sql";
        $process = new Process([
            config('backup.mysql_dump_binary'),
            '--host='.$connection['host'],
            '--port='.(string) $connection['port'],
            '--user='.$connection['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--events',
            $connection['database'],
        ], env: ['MYSQL_PWD' => (string) ($connection['password'] ?? '')]);

        $this->runDumpProcess($process, $path);

        return $path;
    }

    private function backupPostgres(array $connection, string $directory, string $timestamp): string
    {
        $path = $directory.DIRECTORY_SEPARATOR."database_{$timestamp}.sql";
        $process = new Process([
            config('backup.postgres_dump_binary'),
            '--host='.$connection['host'],
            '--port='.(string) $connection['port'],
            '--username='.$connection['username'],
            '--no-owner',
            '--no-privileges',
            $connection['database'],
        ], env: ['PGPASSWORD' => (string) ($connection['password'] ?? '')]);

        $this->runDumpProcess($process, $path);

        return $path;
    }

    private function runDumpProcess(Process $process, string $path): void
    {
        $stream = fopen($path, 'wb');

        if ($stream === false) {
            throw new RuntimeException('The backup destination could not be opened.');
        }

        try {
            $process->setTimeout(600);
            $process->run(function (string $type, string $buffer) use ($stream): void {
                if ($type === Process::OUT) {
                    fwrite($stream, $buffer);
                }
            });
        } finally {
            fclose($stream);
        }

        if (! $process->isSuccessful()) {
            File::delete($path);
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'The database dump process failed.');
        }
    }

    private function removeExpiredBackups(string $directory): void
    {
        $cutoff = now()->subDays(max((int) config('backup.retention_days'), 1))->getTimestamp();

        collect(File::files($directory))
            ->filter(fn ($file) => $file->getFilename() !== 'status.json')
            ->filter(fn ($file) => $file->getMTime() < $cutoff)
            ->each(fn ($file) => File::delete($file->getPathname()));
    }

    private function writeStatus(string $directory, array $status): void
    {
        File::put(
            $directory.DIRECTORY_SEPARATOR.'status.json',
            json_encode($status, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }
}
