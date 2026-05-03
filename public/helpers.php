<?php
// public/helpers.php

function auth(): ?object
{
    if (session_status() === PHP_SESSION_NONE) session_start();

    $token = $_SESSION['access_token'] ?? null;
    if (!$token) return null;

    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    if (!$payload || ($payload['exp'] ?? 0) < time()) {
        // Token expired — clear session
        session_destroy();
        return null;
    }

    // Populate user cache from DB if not set
    if (empty($_SESSION['user'])) {
        require_once __DIR__ . '/../vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        require_once __DIR__ . '/../config/database.php';

        $user   = \Illuminate\Database\Capsule\Manager::table('users')
                    ->where('id', $payload['sub'])->first();
        $member = \Illuminate\Database\Capsule\Manager::table('tenant_users')
                    ->where('user_id', $payload['sub'])->first();
        $tenant = $member
                    ? \Illuminate\Database\Capsule\Manager::table('tenants')
                        ->where('id', $member->tenant_id)->first()
                    : null;

        $_SESSION['user'] = [
            'name'           => $user->name           ?? 'User',
            'email'          => $user->email          ?? '',
            'role'           => $member->role         ?? 'member',
            'plan'           => $tenant->plan         ?? 'free',
            'workspace_name' => $tenant->name         ?? 'My Workspace',
        ];
    }

    $u = $_SESSION['user'];
    return (object)[
        'name'           => $u['name'],
        'email'          => $u['email'],
        'role'           => $u['role'],
        'plan'           => $u['plan'],
        'workspace_name' => $u['workspace_name'],
    ];
}

function requireAuth(): void
{
    if (!auth()) {
        header('Location: /login.php');
        exit;
    }
}

function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfField(): string
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return '<input type="hidden" name="_csrf" value="' . e($_SESSION['csrf']) . '">';
}

function verifyCsrf(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
