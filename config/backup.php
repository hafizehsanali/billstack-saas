<?php

return [
    'path' => env('BACKUP_PATH', storage_path('app/backups')),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
    'mysql_dump_binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
    'postgres_dump_binary' => env('PG_DUMP_BINARY', 'pg_dump'),
];
