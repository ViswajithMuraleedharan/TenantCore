<?php
require_once __DIR__ . '/helpers.php';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — SaaSKit</title>
  <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="logo-mark">S</div>
      SaaSKit
    </div>
    <div class="auth-title">Welcome back</div>
    <div class="auth-sub">Sign in to your account</div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= e($flash['type']) ?>" style="margin-bottom:16px"><?= e($flash['message']) ?></div>
    <?php endif ?>

    <form method="POST" action="/login-action.php">
      <?= csrfField() ?>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">
          Password
          <a href="/forgot-password.php" style="float:right;color:var(--accent);font-weight:500">Forgot?</a>
        </label>
        <input type="password" id="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:10px">Sign In</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="/register.php">Create one</a>
    </div>
  </div>
</div>
</body>
</html>
