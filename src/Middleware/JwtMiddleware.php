<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class JwtMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        $auth = $request->getHeaderLine('Authorization');

        if (!$auth || !str_starts_with($auth, 'Bearer ')) {
            return $this->unauthorized('Missing token');
        }

        $token = substr($auth, 7);

        try {
            $payload = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            $request = $request->withAttribute('jwt_payload', $payload);
            return $handler->handle($request);
        } catch (\Exception $e) {
            return $this->unauthorized('Invalid or expired token');
        }
    }

    private function unauthorized(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['status' => 'error', 'message' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
}
