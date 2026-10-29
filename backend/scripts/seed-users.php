<?php
declare(strict_types=1);

/**
 * QAFlow — Seed user passwords.
 *
 * The SQL seed file (database/seed.sql) creates users with an empty
 * password_hash. Run this script once after importing the seed to set every
 * seeded account to the password "Password123!".
 *
 * Usage:
 *   php backend/scripts/seed-users.php
 *
 * The script is idempotent: re-running it rewrites the hash on every seeded
 * account. It does not touch any other columns.
 */

// ---------------------------------------------------------------------------
// Bootstrap: autoloader + config + database.
// ---------------------------------------------------------------------------
require __DIR__ . '/../bootstrap.php';

use QAFlow\Config\Database;
use QAFlow\Config\Config;

// A tiny CLI guard so this cannot be triggered from the browser.
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

// ---------------------------------------------------------------------------
// Configuration.
// ---------------------------------------------------------------------------
$defaultPassword = 'Password123!';

$seedEmails = [
    'admin@qaflow.local',
    'hassan@qaflow.local',
    'ahmed@qaflow.local',
    'sara@qaflow.local',
    'bilal@qaflow.local',
    'nadia@qaflow.local',
];

// ---------------------------------------------------------------------------
// Generate the bcrypt hash once (bcrypt uses a random salt, so the same
// plaintext produces a different hash every time — that's fine; we share the
// same hash across all seeded accounts on purpose).
// ---------------------------------------------------------------------------
$hash = password_hash($defaultPassword, PASSWORD_BCRYPT);
if ($hash === false) {
    fwrite(STDERR, "Failed to generate password hash.\n");
    exit(1);
}

echo "QAFlow user seed\n";
echo "----------------\n";
echo "Environment: " . Config::string('APP_ENV', 'unknown') . "\n";
echo "Database:    " . Config::string('DB_NAME', 'unknown') . "@" . Config::string('DB_HOST', 'unknown') . "\n";
echo "\n";

// ---------------------------------------------------------------------------
// Update each seeded email.
// ---------------------------------------------------------------------------
$pdo = Database::connection();

$update = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE email = :email');
$select = $pdo->prepare('SELECT id, name, email FROM users WHERE email = :email LIMIT 1');

$updated = 0;
$missing = [];

foreach ($seedEmails as $email) {
    $select->execute([':email' => $email]);
    $row = $select->fetch();

    if ($row === false) {
        $missing[] = $email;
        continue;
    }

    $update->execute([
        ':hash'  => $hash,
        ':email' => $email,
    ]);

    $updated++;
    printf("  ✓ %-25s %s\n", $email, $row['name']);
}

echo "\n";
echo "Updated: $updated / " . count($seedEmails) . "\n";

if ($missing !== []) {
    echo "Not found in database:\n";
    foreach ($missing as $email) {
        echo "  - $email\n";
    }
    echo "\nRun database/seed.sql first if these accounts are missing.\n";
    exit(0);
}

echo "\nAll seeded accounts now use password: $defaultPassword\n";
echo "Change this password before any non-local deployment.\n";