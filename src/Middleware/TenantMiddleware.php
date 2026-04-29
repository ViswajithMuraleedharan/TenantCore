<?php

// src/Middleware/TenantMiddleware.php
class TenantMiddleware
{
    public function process(Request $request, Handler $next): Response
    {
        $payload  = $request->getAttribute('jwt_payload');
        $tenant   = $this->tenants->findById($payload->tenant_id);

        if (!$tenant || $tenant->status !== 'active') {
            throw new TenantSuspendedException();
        }

        // Inject tenant into request for downstream use
        $request = $request->withAttribute('tenant', $tenant);
        return $next->handle($request);
    }
}

// src/Repositories/BaseRepository.php — scopes every query
abstract class BaseRepository
{
    protected function scopedQuery(): Builder
    {
        $tenantId = app('tenant')->id;  // or from container
        return $this->model->where('tenant_id', $tenantId);
    }
}