<?php

namespace App\Controllers;

use App\Services\BillingService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BillingController
{
    private BillingService $billing;

    public function __construct()
    {
        $this->billing = new BillingService();
    }

    public function show(Request $request, Response $response): Response
    {
        $tenant = $request->getAttribute('tenant');
        $sub    = $this->billing->getSubscription($tenant->id);

        return $this->json($response, ['status' => 'success', 'data' => $sub]);
    }

    public function createCheckout(Request $request, Response $response): Response
    {
        $tenant  = $request->getAttribute('tenant');
        $body    = $request->getParsedBody() ?? [];
        $priceId = $body['price_id'] ?? '';

        if (!$priceId) {
            return $this->json($response, ['status' => 'error', 'message' => 'price_id is required'], 422);
        }

        try {
            $url = $this->billing->createCheckoutSession($tenant, $priceId);
            return $this->json($response, ['status' => 'success', 'data' => ['url' => $url]]);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function createPortalSession(Request $request, Response $response): Response
    {
        $tenant = $request->getAttribute('tenant');

        try {
            $url = $this->billing->createPortalSession($tenant);
            return $this->json($response, ['status' => 'success', 'data' => ['url' => $url]]);
        } catch (\Exception $e) {
            return $this->json($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
