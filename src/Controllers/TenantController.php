<?php

namespace App\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TenantController
{
    public function show(Request $request, Response $response): Response
    {
        $tenant = $request->getAttribute('tenant');

        $memberCount = DB::table('tenant_users')->where('tenant_id', $tenant->id)->count();
        $sub         = DB::table('subscriptions')->where('tenant_id', $tenant->id)->first();

        return $this->json($response, [
            'status' => 'success',
            'data'   => [
                'id'           => $tenant->id,
                'name'         => $tenant->name,
                'slug'         => $tenant->slug,
                'plan'         => $tenant->plan,
                'status'       => $tenant->status,
                'settings'     => $tenant->settings ? json_decode($tenant->settings, true) : [],
                'member_count' => $memberCount,
                'subscription' => $sub ? [
                    'stripe_sub_id'        => $sub->stripe_sub_id,
                    'status'               => $sub->status,
                    'current_period_end'   => $sub->current_period_end,
                    'cancel_at_period_end' => (bool)$sub->cancel_at_period_end,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request, Response $response): Response
    {
        $tenant  = $request->getAttribute('tenant');
        $body    = $request->getParsedBody() ?? [];
        $allowed = ['name', 'settings'];
        $data    = array_intersect_key($body, array_flip($allowed));

        if (isset($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }

        if (empty($data)) {
            return $this->json($response, ['status' => 'error', 'message' => 'No valid fields provided'], 422);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('tenants')->where('id', $tenant->id)->update($data);

        $updated = DB::table('tenants')->where('id', $tenant->id)->first();
        return $this->json($response, [
            'status' => 'success',
            'data'   => ['id' => $updated->id, 'name' => $updated->name, 'slug' => $updated->slug],
        ]);
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
