<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupCommandTest extends TestCase
{
    public function test_sqlite_database_can_be_backed_up(): void
    {
        $source = storage_path('framework/testing/backup-source.sqlite');
        $directory = storage_path('framework/testing/backups');

        File::ensureDirectoryExists(dirname($source));
        File::deleteDirectory($directory);
        File::put($source, 'sqlite test database');

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $source,
            'backup.path' => $directory,
            'backup.retention_days' => 14,
        ]);

        $this->artisan('app:backup-database')
            ->expectsOutputToContain('Database backup created:')
            ->assertSuccessful();

        $status = json_decode(File::get($directory.'/status.json'), true);

        $this->assertSame('successful', $status['status']);
        $this->assertFileExists($status['path']);

        File::delete($source);
        File::deleteDirectory($directory);
    }

    public function test_backup_failure_is_recorded(): void
    {
        $directory = storage_path('framework/testing/failed-backups');
        File::deleteDirectory($directory);

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => storage_path('missing.sqlite'),
            'backup.path' => $directory,
        ]);

        $this->artisan('app:backup-database')
            ->expectsOutputToContain('Database backup failed:')
            ->assertFailed();

        $status = json_decode(File::get($directory.'/status.json'), true);
        $this->assertSame('failed', $status['status']);

        File::deleteDirectory($directory);
    }
}
