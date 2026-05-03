<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../config/database.php';

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AuthService;
use App\Controllers\UserController;
use App\Controllers\TenantController;
use App\Controllers\TeamController;
use App\Controllers\BillingController;
use App\Controllers\StripeWebhookController;
use App\Middleware\JwtMiddleware;
use App\Middleware\TenantMiddleware;
use App\Middleware\RbacMiddleware;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware((bool)($_ENV['APP_DEBUG'] ?? false), true, true);

// ── Helpers ──────────────────────────────────────────────────────────────────
function json_response(Response $response, array $data, int $status = 200): Response
{
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

// ── Public ───────────────────────────────────────────────────────────────────
$app->get('/', function (Request $req, Response $res) {
    return json_response($res, [
        'message' => 'SaaSKit API',
        'status'  => 'running',
        'version' => 'v1',
    ]);
});

$app->get('/api/v1/health', function (Request $req, Response $res) {
    return json_response($res, ['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
});

// ── Auth ─────────────────────────────────────────────────────────────────────
$app->group('/api/v1/auth', function (RouteCollectorProxy $group) {

    $group->post('/register', function (Request $req, Response $res) {
        try {
            $tokens = (new AuthService())->register($req->getParsedBody() ?? []);
            return json_response($res, ['status' => 'success', 'data' => $tokens], 201);
        } catch (\Exception $e) {
            return json_response($res, ['status' => 'error', 'message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    });

    $group->post('/login', function (Request $req, Response $res) {
        try {
            $tokens = (new AuthService())->login($req->getParsedBody() ?? []);
            return json_response($res, ['status' => 'success', 'data' => $tokens], 200);
        } catch (\Exception $e) {
            return json_response($res, ['status' => 'error', 'message' => $e->getMessage()], $e->getCode() ?: 401);
        }
    });

    $group->post('/refresh', function (Request $req, Response $res) {
        try {
            $body   = $req->getParsedBody() ?? [];
            $tokens = (new AuthService())->refresh($body['refresh_token'] ?? '');
            return json_response($res, ['status' => 'success', 'data' => $tokens], 200);
        } catch (\Exception $e) {
            return json_response($res, ['status' => 'error', 'message' => $e->getMessage()], $e->getCode() ?: 401);
        }
    });
});

// ── Protected ────────────────────────────────────────────────────────────────
$app->group('/api/v1', function (RouteCollectorProxy $group) {

    // User
    $group->get('/me',    [UserController::class, 'me']);
    $group->patch('/me',  [UserController::class, 'update']);

    // Tenant
    $group->get('/tenants/current',   [TenantController::class, 'show']);
    $group->patch('/tenants/current', [TenantController::class, 'update'])
          ->setArgument('permission', 'settings.update');

    // Team
    $group->get('/tenants/current/members', [TeamController::class, 'index']);
    $group->post('/tenants/invites',        [TeamController::class, 'invite'])
          ->setArgument('permission', 'users.invite');

    // Billing
    $group->get('/billing/subscription',  [BillingController::class, 'show']);
    $group->post('/billing/checkout',     [BillingController::class, 'createCheckout']);
    $group->post('/billing/portal',       [BillingController::class, 'createPortalSession']);

})->add(new RbacMiddleware())
  ->add(new TenantMiddleware())
  ->add(new JwtMiddleware());

// ── Stripe Webhook ────────────────────────────────────────────────────────────
$app->post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

$app->run();
