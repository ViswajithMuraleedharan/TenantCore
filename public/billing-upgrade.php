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
$myRole   = $payload['role'];

if (!in_array($myRole, ['owner', 'admin'])) {
    setFlash('error', 'Only owners and admins can change the plan.');
    header('Location: /billing.php');
    exit;
}

$plan = $_POST['plan'] ?? '';

// Downgrade to free — update DB directly
if ($plan === 'free') {
    $now = date('Y-m-d H:i:s');
    DB::table('tenants')->where('id', $tenantId)->update(['plan' => 'free', 'updated_at' => $now]);
    DB::table('subscriptions')->where('tenant_id', $tenantId)->update(['status' => 'cancelled', 'updated_at' => $now]);
    unset($_SESSION['user']);
    setFlash('success', 'You have been downgraded to the Free plan.');
    header('Location: /billing.php');
    exit;
}

// Map plan to Stripe price ID from .env
$priceMap = [
    'pro'        => $_ENV['STRIPE_PRICE_PRO']        ?? '',
    'enterprise' => $_ENV['STRIPE_PRICE_ENTERPRISE'] ?? '',
];

$priceId = $priceMap[$plan] ?? '';

if (!$priceId) {
    setFlash('error', 'Stripe price ID not configured for this plan. Set STRIPE_PRICE_PRO / STRIPE_PRICE_ENTERPRISE in .env');
    header('Location: /billing.php');
    exit;
}

try {
    $tenant     = DB::table('tenants')->where('id', $tenantId)->first();
    $checkoutUrl = (new BillingService())->createCheckoutSession($tenant, $priceId);
    header('Location: ' . $checkoutUrl);
    exit;
} catch (\Exception $e) {
    setFlash('error', 'Could not create checkout session: ' . $e->getMessage());
    header('Location: /billing.php');
    exit;
}
