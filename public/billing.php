<?php
require_once __DIR__ . '/helpers.php';
requireAuth();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as DB;

$payload     = json_decode(base64_decode(strtr(explode('.', $_SESSION['access_token'])[1], '-_', '+/')), true);
$tenantId    = $payload['tenant_id'];
$tenant      = DB::table('tenants')->where('id', $tenantId)->first();
$sub         = DB::table('subscriptions')->where('tenant_id', $tenantId)->first();
$currentPlan = $tenant->plan ?? 'free';

$title      = 'Billing';
$activePage = 'billing';

$plans = [
  ['id'=>'free',       'name'=>'Free',       'price'=>'$0',  'period'=>'/mo', 'popular'=>false,
   'features'=>['Up to 3 members','5 projects','1 GB storage','Community support']],
  ['id'=>'pro',        'name'=>'Pro',        'price'=>'$29', 'period'=>'/mo', 'popular'=>true,
   'features'=>['Up to 10 members','Unlimited projects','50 GB storage','Priority support','Custom domain']],
  ['id'=>'enterprise', 'name'=>'Enterprise', 'price'=>'$99', 'period'=>'/mo', 'popular'=>false,
   'features'=>['Unlimited members','Unlimited projects','500 GB storage','24/7 dedicated support','SSO / SAML','SLA guarantee']],
];

ob_start(); ?>

<div class="page-header">
  <div>
    <div class="page-title">Billing</div>
    <div class="page-subtitle">Manage your subscription and payment history.</div>
  </div>
</div>

<?php if ($sub && $sub->status === 'past_due'): ?>
  <div style="padding:0 32px 16px">
    <div class="alert alert-error">Your last payment failed. Please update your payment method to avoid service interruption.</div>
  </div>
<?php endif ?>

<!-- Current Subscription -->
<?php if ($sub && $sub->stripe_sub_id): ?>
<div class="section" style="padding-bottom:0">
  <div class="card" style="max-width:480px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-size:12px;color:var(--muted);margin-bottom:4px">Current subscription</div>
      <div style="font-weight:700;font-size:15px"><?= e(ucfirst($currentPlan)) ?> Plan
        <span class="badge <?= $sub->status === 'active' ? 'badge-green' : 'badge-yellow' ?>" style="margin-left:8px"><?= e(ucfirst($sub->status)) ?></span>
      </div>
      <?php if ($sub->current_period_end): ?>
        <div style="font-size:12px;color:var(--muted);margin-top:4px">
          <?= $sub->cancel_at_period_end ? 'Cancels' : 'Renews' ?> <?= e(date('M j, Y', strtotime($sub->current_period_end))) ?>
        </div>
      <?php endif ?>
    </div>
    <form method="POST" action="/billing-portal.php">
      <?= csrfField() ?>
      <button type="submit" class="btn btn-outline btn-sm">Manage in Stripe</button>
    </form>
  </div>
</div>
<?php endif ?>

<!-- Plans -->
<div class="section">
  <div class="section-title" style="margin-bottom:16px">Plans</div>
  <div class="plans-grid">
    <?php foreach ($plans as $p):
      $isCurrent = $p['id'] === $currentPlan; ?>
      <div class="plan-card <?= $p['popular'] ? 'popular' : '' ?>">
        <?php if ($p['popular']): ?><div class="popular-tag">Most Popular</div><?php endif ?>
        <div class="plan-name"><?= e($p['name']) ?></div>
        <div class="plan-price"><?= e($p['price']) ?><span><?= e($p['period']) ?></span></div>
        <ul class="plan-features">
          <?php foreach ($p['features'] as $f): ?><li><?= e($f) ?></li><?php endforeach ?>
        </ul>
        <?php if ($isCurrent): ?>
          <button class="btn btn-outline" style="width:100%;justify-content:center" disabled>Current Plan</button>
        <?php elseif ($p['id'] === 'free'): ?>
          <form method="POST" action="/billing-upgrade.php">
            <?= csrfField() ?>
            <input type="hidden" name="plan" value="free">
            <button type="submit" class="btn btn-outline" style="width:100%;justify-content:center">Downgrade</button>
          </form>
        <?php else: ?>
          <form method="POST" action="/billing-upgrade.php">
            <?= csrfField() ?>
            <input type="hidden" name="plan" value="<?= e($p['id']) ?>">
            <button type="submit" class="btn <?= $p['popular'] ? 'btn-primary' : 'btn-outline' ?>" style="width:100%;justify-content:center">Upgrade</button>
          </form>
        <?php endif ?>
      </div>
    <?php endforeach ?>
  </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/app.php';
