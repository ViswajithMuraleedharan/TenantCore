<?php

// src/Services/BillingService.php
class BillingService
{
    public function createCheckoutSession(Tenant $tenant, string $priceId): string
    {
        $customer = $this->ensureStripeCustomer($tenant);

        $session = Session::create([
            'customer'            => $customer,
            'mode'                => 'subscription',
            'line_items'          => [['price' => $priceId, 'quantity' => 1]],
            'success_url'         => config('app.url') . '/billing/success',
            'cancel_url'          => config('app.url') . '/billing',
            'metadata'            => ['tenant_id' => $tenant->id],
        ]);

        return $session->url;
    }

    public function handleWebhook(string $payload, string $sig): void
    {
        $event = Webhook::constructEvent($payload, $sig, config('stripe.webhook_secret'));

        match ($event->type) {
            'customer.subscription.created',
            'customer.subscription.updated'  => $this->syncSubscription($event->data->object),
            'customer.subscription.deleted'  => $this->cancelSubscription($event->data->object),
            'invoice.payment_failed'         => $this->handlePaymentFailed($event->data->object),
            default                          => null,
        };
    }
}