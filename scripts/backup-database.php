<?php

declare(strict_types=1);

$source = dirname(__DIR__).'/database/database.sqlite';
$destinationDir = dirname(__DIR__).'/database/backups';
if (! is_dir($destinationDir) && ! mkdir($destinationDir, 0700, true) && ! is_dir($destinationDir)) {
    throw new RuntimeException('Unable to create database backup directory');
}
$label = $argv[1] ?? 'before-question-replacement';
if (! preg_match('/^[a-z0-9-]{1,60}$/', $label)) {
    throw new InvalidArgumentException('Backup label must be a short lowercase slug.');
}
$destination = $destinationDir.'/'.$label.'-'.date('Ymd-His').'.sqlite';
$database = new PDO('sqlite:'.$source, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database->exec('VACUUM INTO '.$database->quote($destination));
$backup = new PDO('sqlite:'.$destination);
$tables = ['questions', 'answer_options', 'quiz_attempts', 'attempt_answers', 'demo_payments', 'demo_subscriptions', 'users', 'personal_data_consents'];
if ($backup->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
    throw new RuntimeException('The SQLite backup failed its integrity check.');
}
foreach ($tables as $table) {
    $sourceCount = (int) $database->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
    $backupCount = (int) $backup->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
    if ($sourceCount !== $backupCount) {
        throw new RuntimeException("Backup check failed for {$table}");
    }
}
echo "Backup verified: {$destination}".PHP_EOL;
