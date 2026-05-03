<?php
require_once __DIR__ . '/helpers.php';
requireAuth();

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../config/database.php';

use Illuminate\Database\Capsule\Manager as DB;

$user    = auth();
$payload = json_decode(base64_decode(strtr(explode('.', $_SESSION['access_token'])[1], '-_', '+/')), true);
$tenantId = $payload['tenant_id'];

$tenant      = DB::table('tenants')->where('id', $tenantId)->first();
$memberCount = DB::table('tenant_users')->where('tenant_id', $tenantId)->count();
$sub         = DB::table('subscriptions')->where('tenant_id', $tenantId)->first();

$recentActivity = DB::table('audit_log')
    ->where('tenant_id', $tenantId)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

$title      = 'Dashboard';
$activePage = 'dashboard';

ob_start(); ?>

<div class="page-header">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-subtitle">Welcome back, <?= e($user->name) ?>. Here's what's happening.</div>
  </div>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="card">
    <div class="card-title">Current Plan</div>
    <div class="stat-value"><?= e(ucfirst($tenant->plan ?? 'Free')) ?></div>
    <div class="stat-change"><?= e(ucfirst($sub->status ?? 'active')) ?></div>
  </div>
  <div class="card">
    <div class="card-title">Team Members</div>
    <div class="stat-value"><?= e($memberCount) ?></div>
    <div class="stat-change">In your workspace</div>
  </div>
  <div class="card">
    <div class="card-title">Workspace</div>
    <div class="stat-value" style="font-size:18px"><?= e($tenant->name ?? 'My Workspace') ?></div>
    <div class="stat-change"><?= e($tenant->slug ?? '') ?></div>
  </div>
  <div class="card">
    <div class="card-title">Your Role</div>
    <div class="stat-value" style="font-size:18px"><?= e(ucfirst($user->role)) ?></div>
    <div class="stat-change">In this workspace</div>
  </div>
</div>

<!-- Activity + Sidebar -->
<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;padding:24px 32px">

  <div class="card">
    <div class="section-header">
      <div class="section-title">Recent Activity</div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Action</th><th>Entity</th><th>Time</th></tr>
        </thead>
        <tbody>
          <?php if ($recentActivity->isEmpty()): ?>
            <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:24px">No activity yet</td></tr>
          <?php else: ?>
            <?php foreach ($recentActivity as $log): ?>
              <tr>
                <td><?= e($log->action) ?></td>
                <td style="color:var(--muted)"><?= e($log->entity ?? '—') ?></td>
                <td style="color:var(--muted)"><?= e($log->created_at) ?></td>
              </tr>
            <?php endforeach ?>
          <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
      <div class="section-title" style="margin-bottom:14px">Subscription</div>
      <?php if ($sub): ?>
        <div style="font-size:13px;margin-bottom:8px">
          <span style="color:var(--muted)">Status:</span>
          <span class="badge <?= $sub->status === 'active' ? 'badge-green' : 'badge-yellow' ?>" style="margin-left:6px"><?= e(ucfirst($sub->status)) ?></span>
        </div>
        <?php if ($sub->current_period_end): ?>
          <div style="font-size:12px;color:var(--muted)">Renews <?= e(date('M j, Y', strtotime($sub->current_period_end))) ?></div>
        <?php endif ?>
      <?php else: ?>
        <div style="font-size:13px;color:var(--muted);margin-bottom:12px">You're on the free plan.</div>
      <?php endif ?>
      <a href="/billing.php" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;margin-top:10px">Manage Billing</a>
    </div>

    <div class="card">
      <div class="section-title" style="margin-bottom:12px">Quick Links</div>
      <?php foreach ([
        ['Invite team member', '/team.php'],
        ['Manage billing',     '/billing.php'],
      ] as $l): ?>
        <a href="<?= e($l[1]) ?>" style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px;color:var(--text)">
          <?= e($l[0]) ?>
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      <?php endforeach ?>
    </div>
  </div>

</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/app.php';
