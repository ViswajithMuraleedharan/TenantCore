<?php

namespace App\Middleware;

use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class TenantMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        $payload  = $request->getAttribute('jwt_payload');
        $tenant   = DB::table('tenants')->where('id', $payload->tenant_id)->first();

        if (!$tenant || $tenant->status !== 'active') {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'status'  => 'error',
                'message' => 'Workspace is suspended or not found',
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $request = $request->withAttribute('tenant', $tenant);
        return $handler->handle($request);
    }
}
