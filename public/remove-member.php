<?php
require_once __DIR__ . '/helpers.php';
requireAuth();
verifyCsrf();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as DB;

$payload  = json_decode(base64_decode(strtr(explode('.', $_SESSION['access_token'])[1], '-_', '+/')), true);
$tenantId = $payload['tenant_id'];
$myRole   = $payload['role'];

if (!in_array($myRole, ['owner', 'admin'])) {
    setFlash('error', 'You do not have permission to remove members.');
    header('Location: /team.php');
    exit;
}

$targetUserId = $_POST['user_id'] ?? '';

// Cannot remove yourself
if ($targetUserId === $payload['sub']) {
    setFlash('error', 'You cannot remove yourself.');
    header('Location: /team.php');
    exit;
}

// Cannot remove owner
$target = DB::table('tenant_users')
    ->where('tenant_id', $tenantId)
    ->where('user_id', $targetUserId)
    ->first();

if (!$target || $target->role === 'owner') {
    setFlash('error', 'Cannot remove the workspace owner.');
    header('Location: /team.php');
    exit;
}

DB::table('tenant_users')
    ->where('tenant_id', $tenantId)
    ->where('user_id', $targetUserId)
    ->delete();

DB::table('audit_log')->insert([
    'tenant_id'  => $tenantId,
    'user_id'    => $payload['sub'],
    'action'     => 'member.removed',
    'entity'     => 'user',
    'entity_id'  => $targetUserId,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'created_at' => date('Y-m-d H:i:s'),
]);

setFlash('success', 'Member removed from workspace.');
header('Location: /team.php');
exit;
