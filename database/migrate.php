<?php
// database/migrate.php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// ADD THIS LINE — manually require the Migrator class
require __DIR__ . '/Migrator.php';

echo "Running migrations...\n";
(new Migrator())->run();
echo "Done.\n";