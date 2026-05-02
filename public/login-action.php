<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';

try {
    $tokens = (new App\Services\AuthService())->login([
        'email'    => $email,
        'password' => $password,
    ]);

    $_SESSION['access_token']  = $tokens['access_token'];
    $_SESSION['refresh_token'] = $tokens['refresh_token'];
    unset($_SESSION['user']); // clear cached user on fresh login
    header('Location: /dashboard.php');
    exit;
} catch (\Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
    header('Location: /login.php');
    exit;
}
