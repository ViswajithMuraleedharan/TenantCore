<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ramsey\Uuid\Uuid;

class AuthService
{
    private string $secret;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'];
    }

    public function register(array $data): array
    {
        // Check if user already exists
        $existing = DB::table('users')->where('email', $data['email'])->first();
        if ($existing) {
            throw new \Exception('Email already registered', 409);
        }

        $now    = date('Y-m-d H:i:s');
        $userId = Uuid::uuid4()->toString();

        // Create user
        DB::table('users')->insert([
            'id'         => $userId,
            'email'      => $data['email'],
            'password'   => password_hash($data['password'], PASSWORD_ARGON2ID),
            'name'       => $data['name'] ?? '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Auto-create a personal workspace
        $tenantId = Uuid::uuid4()->toString();
        $slug     = strtolower(preg_replace('/[^a-z0-9]/i', '-', $data['name'] ?? $data['email']));
        $slug     = $slug . '-' . substr($tenantId, 0, 6);

        DB::table('tenants')->insert([
            'id'         => $tenantId,
            'name'       => ($data['name'] ?? $data['email']) . "'s Workspace",
            'slug'       => $slug,
            'plan'       => 'free',
            'status'     => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Link user to tenant as owner
        DB::table('tenant_users')->insert([
            'id'        => Uuid::uuid4()->toString(),
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
            'role'      => 'owner',
            'joined_at' => $now,
        ]);

        return $this->issueTokens($userId, $tenantId, 'owner');
    }

    public function login(array $data): array
    {
        $user = DB::table('users')->where('email', $data['email'])->first();

        if (!$user || !password_verify($data['password'], $user->password)) {
            throw new \Exception('Invalid credentials', 401);
        }

        // Get their first tenant membership
        $membership = DB::table('tenant_users')
            ->where('user_id', $user->id)
            ->first();

        if (!$membership) {
            throw new \Exception('No workspace found for this user', 404);
        }

        return $this->issueTokens($user->id, $membership->tenant_id, $membership->role);
    }

    private function issueTokens(string $userId, string $tenantId, string $role): array
    {
        $now           = time();
        $accessExpiry  = $now + (int)$_ENV['JWT_ACCESS_TTL'];   // 15 min
        $refreshExpiry = $now + (int)$_ENV['JWT_REFRESH_TTL'];  // 30 days

        $accessToken = JWT::encode([
            'sub'       => $userId,
            'tenant_id' => $tenantId,
            'role'      => $role,
            'iat'       => $now,
            'exp'       => $accessExpiry,
        ], $this->secret, 'HS256');

        // Store hashed refresh token
        $refreshToken = bin2hex(random_bytes(40));
        DB::table('refresh_tokens')->insert([
            'id'         => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'user_id'    => $userId,
            'token_hash' => hash('sha256', $refreshToken),
            'expires_at' => date('Y-m-d H:i:s', $refreshExpiry),
            'revoked'    => false,
        ]);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => $accessExpiry,
            'token_type'    => 'Bearer',
        ];
    }

    public function refresh(string $refreshToken): array
    {
        $hash = hash('sha256', $refreshToken);
        $token = DB::table('refresh_tokens')
            ->where('token_hash', $hash)
            ->where('revoked', false)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();

        if (!$token) {
            throw new \Exception('Invalid or expired refresh token', 401);
        }

        // Rotate — revoke old, issue new
        DB::table('refresh_tokens')
            ->where('token_hash', $hash)
            ->update(['revoked' => true]);

        $membership = DB::table('tenant_users')
            ->where('user_id', $token->user_id)
            ->first();

        return $this->issueTokens($token->user_id, $membership->tenant_id, $membership->role);
    }
}