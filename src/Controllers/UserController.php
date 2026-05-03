<?php

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function me(Request $request, Response $response): Response
    {
        $payload = $request->getAttribute('jwt_payload');
        $user    = DB::table('users')->where('id', $payload->sub)->first();

        if (!$user) {
            return $this->json($response, ['status' => 'error', 'message' => 'User not found'], 404);
        }

        $membership = DB::table('tenant_users')->where('user_id', $user->id)->first();
        $tenant     = $membership ? DB::table('tenants')->where('id', $membership->tenant_id)->first() : null;

        return $this->json($response, [
            'status' => 'success',
            'data'   => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'role'           => $membership->role ?? 'member',
                'plan'           => $tenant->plan ?? 'free',
                'workspace_name' => $tenant->name ?? 'My Workspace',
                'email_verified' => !empty($user->email_verified_at),
            ],
        ]);
    }

    public function update(Request $request, Response $response): Response
    {
        $payload = $request->getAttribute('jwt_payload');
        $body    = $request->getParsedBody() ?? [];
        $allowed = ['name'];
        $data    = array_intersect_key($body, array_flip($allowed));

        if (empty($data)) {
            return $this->json($response, ['status' => 'error', 'message' => 'No valid fields provided'], 422);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('users')->where('id', $payload->sub)->update($data);

        $user = DB::table('users')->where('id', $payload->sub)->first();
        return $this->json($response, [
            'status' => 'success',
            'data'   => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
        ]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
