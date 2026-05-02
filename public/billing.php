<?php
require_once __DIR__ . '/helpers.php';
$title      = 'Billing';
$activePage = 'billing';
$currentPlan = auth()->plan ?? 'free';

ob_start(); ?>

<div class="page-header">
  <div>
    <div class="page-title">Billing</div>
    <div class="page-subtitle">Manage your subscription and payment history.</div>
  </div>
</div>

<!-- Plans -->
<div class="section">
  <div class="section-title" style="margin-bottom:16px">Choose a Plan</div>
  <div class="plans-grid">
    <?php
    $plans = [
      ['id'=>'free',       'name'=>'Free',       'price'=>'$0',   'period'=>'/mo', 'popular'=>false,
       'features'=>['Up to 3 members','5 projects','1 GB storage','Community support']],
      ['id'=>'pro',        'name'=>'Pro',         'price'=>'$29',  'period'=>'/mo', 'popular'=>true,
       'features'=>['Up to 10 members','Unlimited projects','50 GB storage','Priority support','Custom domain']],
      ['id'=>'enterprise', 'name'=>'Enterprise',  'price'=>'$99',  'period'=>'/mo', 'popular'=>false,
       'features'=>['Unlimited members','Unlimited projects','500 GB storage','24/7 dedicated support','SSO / SAML','SLA guarantee']],
    ];
    foreach ($plans as $p):
      $isCurrent = $p['id'] === $currentPlan;
    ?>
      <div class="plan-card <?= $p['popular'] ? 'popular' : '' ?>">
        <?php if ($p['popular']): ?><div class="popular-tag">Most Popular</div><?php endif ?>
        <div class="plan-name"><?= e($p['name']) ?></div>
        <div class="plan-price"><?= e($p['price']) ?><span><?= e($p['period']) ?></span></div>
        <ul class="plan-features">
          <?php foreach ($p['features'] as $f): ?>
            <li><?= e($f) ?></li>
          <?php endforeach ?>
        </ul>
        <?php if ($isCurrent): ?>
          <button class="btn btn-outline" style="width:100%;justify-content:center" disabled>Current Plan</button>
        <?php else: ?>
          <form method="POST" action="/billing-upgrade.php">
            <?= csrfField() ?>
            <input type="hidden" name="plan" value="<?= e($p['id']) ?>">
            <button type="submit" class="btn <?= $p['popular'] ? 'btn-primary' : 'btn-outline' ?>" style="width:100%;justify-content:center">
              <?= $p['id'] === 'free' ? 'Downgrade' : 'Upgrade' ?>
            </button>
          </form>
        <?php endif ?>
      </div>
    <?php endforeach ?>
  </div>
</div>

<!-- Payment Method -->
<div class="section" style="padding-top:0">
  <div class="card" style="max-width:480px">
    <div class="section-title" style="margin-bottom:14px">Payment Method</div>
    <div style="display:flex;align-items:center;gap:14px;padding:12px;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px">
      <svg width="36" height="24" viewBox="0 0 36 24" fill="none"><rect width="36" height="24" rx="4" fill="#1a1f71"/><path d="M13.5 16.5h-3l1.875-9h3L13.5 16.5zm6.375 0h-2.813l.938-4.5c.188-.938-.375-1.313-1.125-1.313-.563 0-1.125.375-1.313.938L14.625 16.5H11.75l2.625-9h2.813l-.375 1.313c.563-.938 1.688-1.5 2.813-1.5 1.875 0 3 1.125 2.625 3L21 16.5zm6.375 0h-2.813l.188-.938c-.563.75-1.5 1.125-2.438 1.125-2.063 0-3.375-1.688-3-3.938.375-2.25 2.25-3.938 4.313-3.938.938 0 1.688.375 2.063 1.125l.375-1.125h2.813l-1.5 7.688z" fill="white"/></svg>
      <div>
        <div style="font-weight:600;font-size:13px">Visa ending in 4242</div>
        <div style="font-size:12px;color:var(--muted)">Expires 08 / 2027</div>
      </div>
      <button class="btn btn-outline btn-sm" style="margin-left:auto">Update</button>
    </div>
  </div>
</div>

<!-- Invoices -->
<div class="section" style="padding-top:0">
  <div class="card">
    <div class="section-header">
      <div class="section-title">Invoice History</div>
      <button class="btn btn-outline btn-sm">Download All</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Invoice</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php
          $invoices = [
            ['INV-2024-006', 'Jun 1, 2024', '$29.00', 'paid'],
            ['INV-2024-005', 'May 1, 2024', '$29.00', 'paid'],
            ['INV-2024-004', 'Apr 1, 2024', '$29.00', 'paid'],
            ['INV-2024-003', 'Mar 1, 2024', '$29.00', 'paid'],
            ['INV-2024-002', 'Feb 1, 2024', '$29.00', 'paid'],
          ];
          foreach ($invoices as $inv): ?>
            <tr>
              <td style="font-weight:600"><?= e($inv[0]) ?></td>
              <td style="color:var(--muted)"><?= e($inv[1]) ?></td>
              <td><?= e($inv[2]) ?></td>
              <td><span class="badge badge-green"><?= e(ucfirst($inv[3])) ?></span></td>
              <td style="text-align:right">
                <a href="#" class="btn btn-outline btn-sm">
                  <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  PDF
                </a>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/app.php';
