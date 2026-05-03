<?php

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

class TeamController
{
    public function index(Request $request, Response $response): Response
    {
        $tenant  = $request->getAttribute('tenant');

        $members = DB::table('tenant_users')
            ->join('users', 'users.id', '=', 'tenant_users.user_id')
            ->where('tenant_users.tenant_id', $tenant->id)
            ->select([
                'users.id', 'users.name', 'users.email',
                'tenant_users.role', 'tenant_users.joined_at',
            ])
            ->get()
            ->map(fn($m) => (array)$m)
            ->toArray();

        return $this->json($response, ['status' => 'success', 'data' => $members]);
    }

    public function invite(Request $request, Response $response): Response
    {
        $tenant  = $request->getAttribute('tenant');
        $payload = $request->getAttribute('jwt_payload');
        $body    = $request->getParsedBody() ?? [];

        $email = trim($body['email'] ?? '');
        $role  = $body['role'] ?? 'member';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json($response, ['status' => 'error', 'message' => 'Invalid email address'], 422);
        }

        $validRoles = ['admin', 'member', 'viewer'];
        if (!in_array($role, $validRoles)) {
            return $this->json($response, ['status' => 'error', 'message' => 'Invalid role'], 422);
        }

        // Find or create user
        $user = DB::table('users')->where('email', $email)->first();
        if (!$user) {
            $now    = date('Y-m-d H:i:s');
            $userId = Uuid::uuid4()->toString();
            DB::table('users')->insert([
                'id'         => $userId,
                'email'      => $email,
                'password'   => password_hash(bin2hex(random_bytes(16)), PASSWORD_ARGON2ID),
                'name'       => explode('@', $email)[0],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $user = DB::table('users')->where('id', $userId)->first();
        }

        // Check not already a member
        $existing = DB::table('tenant_users')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return $this->json($response, ['status' => 'error', 'message' => 'User is already a member'], 409);
        }

        DB::table('tenant_users')->insert([
            'id'         => Uuid::uuid4()->toString(),
            'tenant_id'  => $tenant->id,
            'user_id'    => $user->id,
            'role'       => $role,
            'invited_by' => $payload->sub,
            'joined_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->json($response, [
            'status'  => 'success',
            'message' => "Invitation sent to $email",
            'data'    => ['email' => $email, 'role' => $role],
        ], 201);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
