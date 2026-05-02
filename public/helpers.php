<?php
// public/helpers.php

function auth(): ?object {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $token = $_SESSION['access_token'] ?? null;
    if (!$token) return null;

    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    if (!$payload) return null;

    $u = $_SESSION['user'] ?? [];
    return (object)[
        'name'           => $u['name']           ?? 'User',
        'email'          => $u['email']          ?? '',
        'role'           => $payload['role']     ?? 'member',
        'plan'           => $u['plan']           ?? 'free',
        'workspace_name' => $u['workspace_name'] ?? 'My Workspace',
    ];
}

function e(mixed $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfField(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="_csrf" value="' . e($_SESSION['csrf']) . '">';
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
