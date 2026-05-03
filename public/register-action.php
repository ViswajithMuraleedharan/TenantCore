<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register.php');
    exit;
}

$firstName = trim($_POST['first_name']    ?? '');
$lastName  = trim($_POST['last_name']     ?? '');
$name      = trim("$firstName $lastName") ?: trim($_POST['email'] ?? '');
$email     = trim($_POST['email']         ?? '');
$password  = $_POST['password']           ?? '';
$confirm   = $_POST['confirm_password']   ?? '';
$workspace = trim($_POST['workspace']     ?? '');

if ($password !== $confirm) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Passwords do not match.'];
    header('Location: /register.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Password must be at least 8 characters.'];
    header('Location: /register.php');
    exit;
}

try {
    $tokens = (new App\Services\AuthService())->register([
        'name'           => $name,
        'email'          => $email,
        'password'       => $password,
        'workspace_name' => $workspace,
    ]);

    $_SESSION['access_token']  = $tokens['access_token'];
    $_SESSION['refresh_token'] = $tokens['refresh_token'];
    header('Location: /dashboard.php');
    exit;
} catch (\Exception $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => $e->getMessage()];
    header('Location: /register.php');
    exit;
}
