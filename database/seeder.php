<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as DB;
use Ramsey\Uuid\Uuid;

$now = date('Y-m-d H:i:s');

// Create tenant
$tenantId = Uuid::uuid4()->toString();
DB::table('tenants')->insert([
    'id'         => $tenantId,
    'name'       => 'Acme Corp',
    'slug'       => 'acme',
    'plan'       => 'pro',
    'status'     => 'active',
    'created_at' => $now,
    'updated_at' => $now,
]);
echo "Created tenant: Acme Corp\n";

// Create user
$userId = Uuid::uuid4()->toString();
DB::table('users')->insert([
    'id'                => $userId,
    'email'             => 'owner@acme.com',
    'password'          => password_hash('password', PASSWORD_ARGON2ID),
    'name'              => 'Alice Owner',
    'email_verified_at' => $now,
    'created_at'        => $now,
    'updated_at'        => $now,
]);
echo "Created user: owner@acme.com\n";

// Link user to tenant as owner
DB::table('tenant_users')->insert([
    'id'        => Uuid::uuid4()->toString(),
    'tenant_id' => $tenantId,
    'user_id'   => $userId,
    'role'      => 'owner',
    'joined_at' => $now,
]);
echo "Linked user to tenant as owner\n";

echo "\nDone! Login with: owner@acme.com / password\n";