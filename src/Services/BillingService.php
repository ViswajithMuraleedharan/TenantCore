<?php

namespace App\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Ramsey\Uuid\Uuid;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Customer;
use Stripe\Webhook;

class BillingService
{
    public function __construct()
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET']);
    }

    public function getSubscription(string $tenantId): array
    {
        $sub = DB::table('subscriptions')->where('tenant_id', $tenantId)->first();

        if (!$sub) {
            return ['plan' => 'free', 'status' => 'active', 'subscription' => null];
        }

        return [
            'plan'                 => $sub->plan,
            'status'               => $sub->status,
            'trial_ends_at'        => $sub->trial_ends_at,
            'current_period_end'   => $sub->current_period_end,
            'cancel_at_period_end' => (bool)$sub->cancel_at_period_end,
            'stripe_sub_id'        => $sub->stripe_sub_id,
        ];
    }

    public function createCheckoutSession(object $tenant, string $priceId): string
    {
        $customerId = $this->ensureStripeCustomer($tenant);

        $session = Session::create([
            'customer'    => $customerId,
            'mode'        => 'subscription',
            'line_items'  => [['price' => $priceId, 'quantity' => 1]],
            'success_url' => $_ENV['APP_URL'] . '/billing.php?success=1',
            'cancel_url'  => $_ENV['APP_URL'] . '/billing.php',
            'metadata'    => ['tenant_id' => $tenant->id],
        ]);

        return $session->url;
    }

    public function createPortalSession(object $tenant): string
    {
        $customerId = $this->ensureStripeCustomer($tenant);

        $session = PortalSession::create([
            'customer'   => $customerId,
            'return_url' => $_ENV['APP_URL'] . '/billing.php',
        ]);

        return $session->url;
    }

    public function handleWebhook(string $payload, string $sig): void
    {
        $event = Webhook::constructEvent($payload, $sig, $_ENV['STRIPE_WEBHOOK_SECRET']);

        match ($event->type) {
            'customer.subscription.created',
            'customer.subscription.updated' => $this->syncSubscription($event->data->object),
            'customer.subscription.deleted' => $this->cancelSubscription($event->data->object),
            'invoice.payment_failed'        => $this->handlePaymentFailed($event->data->object),
            default                         => null,
        };
    }

    private function ensureStripeCustomer(object $tenant): string
    {
        $sub = DB::table('subscriptions')->where('tenant_id', $tenant->id)->first();

        if ($sub && $sub->stripe_customer_id) {
            return $sub->stripe_customer_id;
        }

        // Get owner email
        $owner = DB::table('tenant_users')
            ->join('users', 'users.id', '=', 'tenant_users.user_id')
            ->where('tenant_users.tenant_id', $tenant->id)
            ->where('tenant_users.role', 'owner')
            ->select('users.email', 'users.name')
            ->first();

        $customer = Customer::create([
            'email'    => $owner->email ?? '',
            'name'     => $tenant->name,
            'metadata' => ['tenant_id' => $tenant->id],
        ]);

        $now = date('Y-m-d H:i:s');
        if ($sub) {
            DB::table('subscriptions')
                ->where('tenant_id', $tenant->id)
                ->update(['stripe_customer_id' => $customer->id, 'updated_at' => $now]);
        } else {
            DB::table('subscriptions')->insert([
                'id'                 => Uuid::uuid4()->toString(),
                'tenant_id'          => $tenant->id,
                'stripe_customer_id' => $customer->id,
                'plan'               => 'free',
                'status'             => 'active',
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }

        return $customer->id;
    }

    private function syncSubscription(object $sub): void
    {
        $tenantId = $sub->metadata->tenant_id ?? null;
        if (!$tenantId) return;

        $plan = $this->resolvePlanFromStripe($sub);
        $now  = date('Y-m-d H:i:s');

        DB::table('subscriptions')->where('tenant_id', $tenantId)->update([
            'stripe_sub_id'        => $sub->id,
            'plan'                 => $plan,
            'status'               => $sub->status,
            'current_period_end'   => date('Y-m-d H:i:s', $sub->current_period_end),
            'cancel_at_period_end' => $sub->cancel_at_period_end,
            'updated_at'           => $now,
        ]);

        DB::table('tenants')->where('id', $tenantId)->update(['plan' => $plan, 'updated_at' => $now]);
    }

    private function cancelSubscription(object $sub): void
    {
        $tenantId = $sub->metadata->tenant_id ?? null;
        if (!$tenantId) return;

        $now = date('Y-m-d H:i:s');
        DB::table('subscriptions')->where('tenant_id', $tenantId)->update([
            'status'     => 'cancelled',
            'updated_at' => $now,
        ]);
        DB::table('tenants')->where('id', $tenantId)->update(['plan' => 'free', 'updated_at' => $now]);
    }

    private function handlePaymentFailed(object $invoice): void
    {
        $customerId = $invoice->customer;
        $sub = DB::table('subscriptions')->where('stripe_customer_id', $customerId)->first();
        if (!$sub) return;

        DB::table('subscriptions')->where('id', $sub->id)->update([
            'status'     => 'past_due',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function resolvePlanFromStripe(object $sub): string
    {
        $priceId = $sub->items->data[0]->price->id ?? '';
        $map = [
            $_ENV['STRIPE_PRICE_PRO']        ?? '' => 'pro',
            $_ENV['STRIPE_PRICE_ENTERPRISE'] ?? '' => 'enterprise',
        ];
        return $map[$priceId] ?? 'free';
    }
}
