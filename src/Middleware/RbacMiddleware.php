<?php

// src/Middleware/RbacMiddleware.php
class RbacMiddleware
{
    private array $permissions = [
        'owner'  => ['*'],                                    // everything
        'admin'  => ['users.*', 'settings.*', 'billing.read'],
        'member' => ['content.*', 'profile.*'],
        'viewer' => ['content.read', 'profile.read'],
    ];

    public function process(Request $request, Handler $next): Response
    {
        $token    = $this->extractToken($request);
        $payload  = JWT::decode($token, $this->secret);
        $role     = $payload->role;
        $required = $request->getAttribute('permission'); // set per route

        if (!$this->can($role, $required)) {
            throw new ForbiddenException("Role '$role' cannot '$required'");
        }

        return $next->handle($request);
    }

    private function can(string $role, string $permission): bool
    {
        $allowed = $this->permissions[$role] ?? [];
        if (in_array('*', $allowed)) return true;

        [$resource, $action] = explode('.', $permission);
        return in_array($permission, $allowed)
            || in_array("$resource.*", $allowed);
    }
}