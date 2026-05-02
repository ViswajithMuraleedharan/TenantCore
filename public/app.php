<?php
// views/layouts/app.php
// Requires: $title, $activePage, $content
require_once __DIR__ . '/helpers.php';

$user   = auth();
$name   = $user->name ?? 'User';
$email  = $user->email ?? '';
$role   = $user->role ?? 'member';
$plan   = $user->plan ?? 'free';
$initials = strtoupper(substr($name, 0, 1) . (strpos($name, ' ') ? substr($name, strpos($name,' ')+1, 1) : ''));

$nav = [
    ['id'=>'dashboard', 'label'=>'Dashboard', 'href'=>'/dashboard.php',
     'icon'=>'<svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'],
    ['id'=>'team',      'label'=>'Team',      'href'=>'/team.php',
     'icon'=>'<svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'],
    ['id'=>'billing',   'label'=>'Billing',   'href'=>'/billing.php',
     'icon'=>'<svg class="nav-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Dashboard') ?> — SaaSKit</title>
  <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="app-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <a href="/dashboard.php" class="sidebar-logo">
      <div class="logo-mark">S</div>
      SaaSKit
    </a>
    <div class="sidebar-workspace">
      <div class="workspace-label">Workspace</div>
      <div class="workspace-name">
        <?= e($user->workspace_name ?? 'My Workspace') ?>
        <span class="plan-badge plan-<?= e($plan) ?>"><?= e(ucfirst($plan)) ?></span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <?php foreach ($nav as $item): ?>
        <a href="<?= e($item['href']) ?>" class="nav-item <?= $activePage === $item['id'] ? 'active' : '' ?>">
          <?= $item['icon'] ?>
          <?= e($item['label']) ?>
        </a>
      <?php endforeach ?>
    </nav>
    <div class="sidebar-footer">
      <div class="user-row">
        <div class="avatar"><?= e($initials ?: '?') ?></div>
        <div class="user-info">
          <div class="user-name"><?= e($name) ?></div>
          <div class="user-email"><?= e($email) ?></div>
        </div>
        <form method="POST" action="/logout.php" style="margin:0">
          <?= csrfField() ?>
          <button type="submit" class="logout-btn" title="Sign out">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main-content">
    <?php $flash = getFlash(); if ($flash): ?>
      <div style="padding: 16px 32px 0">
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      </div>
    <?php endif ?>
    <?= $content ?>
  </main>

</div>
</body>
</html>
