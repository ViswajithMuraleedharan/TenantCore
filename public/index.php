<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../config/database.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\AuthService;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Helper to send JSON responses
function json_response(Response $response, array $data, int $status = 200): Response
{
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

// Health check
$app->get('/', function (Request $request, Response $response) {
    return json_response($response, [
        'message' => 'SaaS Boilerplate API',
        'status'  => 'running',
        'version' => 'v1',
    ]);
});

$app->get('/api/v1/health', function (Request $request, Response $response) {
    return json_response($response, [
        'status' => 'ok',
        'time'   => date('Y-m-d H:i:s'),
    ]);
});

// Register
$app->post('/api/v1/auth/register', function (Request $request, Response $response) {
    try {
        $body   = $request->getParsedBody();
        $tokens = (new AuthService())->register($body);
        return json_response($response, ['status' => 'success', 'data' => $tokens], 201);
    } catch (\Exception $e) {
        return json_response($response, ['status' => 'error', 'message' => $e->getMessage()], $e->getCode() ?: 400);
    }
});

// Login
$app->post('/api/v1/auth/login', function (Request $request, Response $response) {
    try {
        $body   = $request->getParsedBody();
        $tokens = (new AuthService())->login($body);
        return json_response($response, ['status' => 'success', 'data' => $tokens], 200);
    } catch (\Exception $e) {
        return json_response($response, ['status' => 'error', 'message' => $e->getMessage()], $e->getCode() ?: 400);
    }
});

// Refresh token
$app->post('/api/v1/auth/refresh', function (Request $request, Response $response) {
    try {
        $body         = $request->getParsedBody();
        $refreshToken = $body['refresh_token'] ?? '';
        $tokens       = (new AuthService())->refresh($refreshToken);
        return json_response($response, ['status' => 'success', 'data' => $tokens], 200);
    } catch (\Exception $e) {
        return json_response($response, ['status' => 'error', 'message' => $e->getMessage()], $e->getCode() ?: 400);
    }
});

$app->run();