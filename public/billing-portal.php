<?php
require_once __DIR__ . '/helpers.php';
requireAuth();
verifyCsrf();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as DB;
use App\Services\BillingService;

$payload  = json_decode(base64_decode(strtr(explode('.', $_SESSION['access_token'])[1], '-_', '+/')), true);
$tenantId = $payload['tenant_id'];
$tenant   = DB::table('tenants')->where('id', $tenantId)->first();

try {
    $url = (new BillingService())->createPortalSession($tenant);
    header('Location: ' . $url);
    exit;
} catch (\Exception $e) {
    setFlash('error', 'Could not open billing portal: ' . $e->getMessage());
    header('Location: /billing.php');
    exit;
}
