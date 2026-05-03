<?php
require_once __DIR__ . '/helpers.php';
requireAuth();
verifyCsrf();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as DB;
use Ramsey\Uuid\Uuid;

$payload  = json_decode(base64_decode(strtr(explode('.', $_SESSION['access_token'])[1], '-_', '+/')), true);
$tenantId = $payload['tenant_id'];
$myRole   = $payload['role'];

if (!in_array($myRole, ['owner', 'admin'])) {
    setFlash('error', 'You do not have permission to invite members.');
    header('Location: /team.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$role  = $_POST['role'] ?? 'member';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Invalid email address.');
    header('Location: /team.php');
    exit;
}

if (!in_array($role, ['admin', 'member', 'viewer'])) {
    setFlash('error', 'Invalid role.');
    header('Location: /team.php');
    exit;
}

$user = DB::table('users')->where('email', $email)->first();
if (!$user) {
    $now    = date('Y-m-d H:i:s');
    $userId = Uuid::uuid4()->toString();
    DB::table('users')->insert([
        'id'         => $userId,
        'email'      => $email,
        'password'   => password_hash(bin2hex(random_bytes(16)), PASSWORD_ARGON2ID),
        'name'       => explode('@', $email)[0],
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $user = DB::table('users')->where('id', $userId)->first();
}

$existing = DB::table('tenant_users')
    ->where('tenant_id', $tenantId)
    ->where('user_id', $user->id)
    ->first();

if ($existing) {
    setFlash('error', "$email is already a member of this workspace.");
    header('Location: /team.php');
    exit;
}

DB::table('tenant_users')->insert([
    'id'         => Uuid::uuid4()->toString(),
    'tenant_id'  => $tenantId,
    'user_id'    => $user->id,
    'role'       => $role,
    'invited_by' => $payload['sub'],
    'joined_at'  => date('Y-m-d H:i:s'),
]);

// Log to audit
DB::table('audit_log')->insert([
    'tenant_id'  => $tenantId,
    'user_id'    => $payload['sub'],
    'action'     => 'member.invited',
    'entity'     => 'user',
    'entity_id'  => $user->id,
    'meta'       => json_encode(['email' => $email, 'role' => $role]),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'created_at' => date('Y-m-d H:i:s'),
]);

// Clear user cache so sidebar refreshes
unset($_SESSION['user']);

setFlash('success', "$email has been added to your workspace as $role.");
header('Location: /team.php');
exit;
