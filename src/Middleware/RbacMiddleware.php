<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class RbacMiddleware
{
    private array $permissions = [
        'owner'  => ['*'],
        'admin'  => ['users.*', 'settings.*', 'billing.read'],
        'member' => ['content.*', 'profile.*'],
        'viewer' => ['content.read', 'profile.read'],
    ];

    public function __invoke(Request $request, Handler $handler): Response
    {
        $payload    = $request->getAttribute('jwt_payload');
        $role       = $payload->role ?? 'viewer';
        $required   = $request->getAttribute('permission');

        if ($required && !$this->can($role, $required)) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'status'  => 'error',
                'message' => "Role '$role' is not allowed to perform '$required'",
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        return $handler->handle($request);
    }

    private function can(string $role, string $permission): bool
    {
        $allowed = $this->permissions[$role] ?? [];
        if (in_array('*', $allowed)) return true;

        $parts    = explode('.', $permission);
        $resource = $parts[0];
        return in_array($permission, $allowed) || in_array("$resource.*", $allowed);
    }
}
