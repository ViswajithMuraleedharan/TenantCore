<?php

namespace App\Controllers;

use App\Services\BillingService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StripeWebhookController
{
    public function handle(Request $request, Response $response): Response
    {
        $payload = (string)$request->getBody();
        $sig     = $request->getHeaderLine('Stripe-Signature');

        if (!$sig) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Missing signature']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            (new BillingService())->handleWebhook($payload, $sig);
            $response->getBody()->write(json_encode(['status' => 'success']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Invalid signature']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
