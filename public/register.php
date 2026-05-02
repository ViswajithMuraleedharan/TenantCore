<?php
require_once __DIR__ . '/helpers.php';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — SaaSKit</title>
  <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-mark">S</div>
      SaaSKit
    </div>
    <div class="auth-title">Create your account</div>
    <div class="auth-sub">Start your 14-day free trial</div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?>" style="margin-bottom:16px"><?= e($flash['message']) ?></div>
    <?php endif ?>

    <form method="POST" action="/register-action.php" novalidate>
      <?= csrfField() ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label for="first_name">First name</label>
          <input type="text" id="first_name" name="first_name" placeholder="Jane" required>
        </div>
        <div class="form-group">
          <label for="last_name">Last name</label>
          <input type="text" id="last_name" name="last_name" placeholder="Smith" required>
        </div>
      </div>
      <div class="form-group">
        <label for="workspace">Workspace name</label>
        <input type="text" id="workspace" name="workspace" placeholder="Acme Corp" required>
      </div>
      <div class="form-group">
        <label for="email">Work email</label>
        <input type="email" id="email" name="email" placeholder="you@company.com" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Min. 8 characters" required minlength="8">
      </div>
      <div class="form-group">
        <label for="confirm-password">Confirm Password</label>
        <input type="password" id="confirm-password" name="confirm-password" placeholder="Min. 8 characters" required minlength="8">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:10px">Create Account</button>
      <p style="font-size:11px;color:var(--muted);text-align:center;margin-top:12px">
        By signing up you agree to our <a href="#" style="color:var(--accent)">Terms</a> and <a href="#" style="color:var(--accent)">Privacy Policy</a>.
      </p>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="/login.php">Sign in</a>
    </div>
  </div>
</div>
</body>
</html>
