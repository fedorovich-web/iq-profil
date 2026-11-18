<?php

declare(strict_types=1);

$source = dirname(__DIR__).'/database/database.sqlite';
$destinationDir = dirname(__DIR__).'/database/backups';
if (! is_dir($destinationDir) && ! mkdir($destinationDir, 0700, true) && ! is_dir($destinationDir)) {
    throw new RuntimeException('Unable to create database backup directory');
}
$destination = $destinationDir.'/before-question-replacement-'.date('Ymd-His').'.sqlite';
$database = new PDO('sqlite:'.$source, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database->exec('VACUUM INTO '.$database->quote($destination));
$backup = new PDO('sqlite:'.$destination);
$tables = ['questions', 'answer_options', 'quiz_attempts', 'attempt_answers', 'demo_payments', 'users'];
foreach ($tables as $table) {
    $sourceCount = (int) $database->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
    $backupCount = (int) $backup->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
    if ($sourceCount !== $backupCount) {
        throw new RuntimeException("Backup check failed for {$table}");
    }
}
echo "Backup verified: {$destination}".PHP_EOL;
