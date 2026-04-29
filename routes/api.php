<?php

// routes/api.php
$app->group('/api/v1', function (RouteCollectorProxy $group) {

    // Public routes
    $group->post('/auth/register', [AuthController::class, 'register']);
    $group->post('/auth/login',    [AuthController::class, 'login']);
    $group->post('/auth/refresh',  [AuthController::class, 'refresh']);

    // Protected routes
    $group->group('', function (RouteCollectorProxy $g) {

        $g->get('/me',           [UserController::class, 'me']);
        $g->patch('/me',         [UserController::class, 'update']);

        // Tenant workspace
        $g->get('/tenants/current',        [TenantController::class, 'show']);
        $g->patch('/tenants/current',      [TenantController::class, 'update'])
          ->setArgument('permission', 'settings.update');
        $g->get('/tenants/current/members', [TeamController::class, 'index']);
        $g->post('/tenants/invites',        [TeamController::class, 'invite'])
          ->setArgument('permission', 'users.invite');

        // Billing
        $g->get('/billing/subscription',    [BillingController::class, 'show']);
        $g->post('/billing/checkout',       [BillingController::class, 'createCheckout']);
        $g->post('/billing/portal',         [BillingController::class, 'createPortalSession']);

    })->addMiddlewares([JwtMiddleware::class, TenantMiddleware::class, RbacMiddleware::class]);

})->addMiddleware(RateLimitMiddleware::class);

// Stripe webhook (no JWT — uses Stripe signature)
$app->post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);