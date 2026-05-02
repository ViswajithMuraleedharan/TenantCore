<?php
require_once __DIR__ . '/helpers.php';
$title      = 'Dashboard';
$activePage = 'dashboard';

ob_start(); ?>

<div class="page-header">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-subtitle">Welcome back, <?= e(auth()->name ?? 'User') ?>. Here's what's happening.</div>
  </div>
  <button class="btn btn-primary">
    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Project
  </button>
</div>

<!-- Stats -->
<div class="stats-grid">
  <?php
  $stats = [
    ['label'=>'Monthly Revenue',  'value'=>'$12,480', 'change'=>'+8.2% vs last month'],
    ['label'=>'Active Users',     'value'=>'1,284',   'change'=>'+3.1% vs last month'],
    ['label'=>'Open Tickets',     'value'=>'24',      'change'=>'-5 since yesterday', 'down'=>true],
    ['label'=>'Uptime',           'value'=>'99.98%',  'change'=>'Last 30 days'],
  ];
  foreach ($stats as $s): ?>
    <div class="card">
      <div class="card-title"><?= e($s['label']) ?></div>
      <div class="stat-value"><?= e($s['value']) ?></div>
      <div class="stat-change <?= !empty($s['down']) ? 'down' : '' ?>"><?= e($s['change']) ?></div>
    </div>
  <?php endforeach ?>
</div>

<!-- Recent Activity + Usage -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:16px;padding:24px 32px">

  <!-- Recent Activity -->
  <div class="card">
    <div class="section-header">
      <div class="section-title">Recent Activity</div>
      <a href="#" class="btn btn-outline btn-sm">View all</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Event</th><th>User</th><th>Time</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php
          $events = [
            ['New signup',       'alice@example.com', '2 min ago',  'success'],
            ['Plan upgraded',    'bob@example.com',   '14 min ago', 'success'],
            ['Payment failed',   'carol@example.com', '1 hr ago',   'error'],
            ['API key created',  'dave@example.com',  '3 hr ago',   'info'],
            ['Member invited',   'eve@example.com',   '5 hr ago',   'info'],
          ];
          $map = ['success'=>'badge-green','error'=>'badge-red','info'=>'badge-blue'];
          foreach ($events as $ev): ?>
            <tr>
              <td><?= e($ev[0]) ?></td>
              <td style="color:var(--muted)"><?= e($ev[1]) ?></td>
              <td style="color:var(--muted)"><?= e($ev[2]) ?></td>
              <td><span class="badge <?= $map[$ev[3]] ?>"><?= e($ev[3]) ?></span></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Usage -->
  <div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
      <div class="section-title" style="margin-bottom:16px">Plan Usage</div>
      <?php
      $usages = [
        ['API Calls',   7200, 10000],
        ['Team Members', 6,   10],
        ['Storage',     3.2,  5, 'GB'],
      ];
      foreach ($usages as $u):
        $pct = round($u[1] / $u[2] * 100);
        $unit = $u[3] ?? '';
      ?>
        <div style="margin-bottom:14px">
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
            <span><?= e($u[0]) ?></span>
            <span style="color:var(--muted)"><?= e($u[1].$unit) ?> / <?= e($u[2].$unit) ?></span>
          </div>
          <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
        </div>
      <?php endforeach ?>
      <a href="/billing.php" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;margin-top:4px">Upgrade Plan</a>
    </div>

    <div class="card">
      <div class="section-title" style="margin-bottom:12px">Quick Links</div>
      <?php
      $links = [
        ['Invite team member', '/team.php'],
        ['Manage billing',     '/billing.php'],
        ['API documentation',  '#'],
        ['Support center',     '#'],
      ];
      foreach ($links as $l): ?>
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
