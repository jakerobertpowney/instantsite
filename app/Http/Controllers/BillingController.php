<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Checkout;

class BillingController extends Controller
{
    /**
     * Redirect the user to Stripe Checkout to start a subscription.
     */
    public function checkout(Request $request): Checkout
    {
        $priceId = config('cashier.price_id');

        return $request->user()
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('dashboard'),
            ]);
    }

    /**
     * Handle the return from a successful Stripe Checkout session.
     */
    public function success(Request $request): RedirectResponse
    {
        return redirect()->route('dashboard')->with('premium_activated', true);
    }

    /**
     * Redirect the user to the Stripe Customer Billing Portal.
     * From here they can manage or cancel their subscription.
     */
    public function portal(Request $request): RedirectResponse
    {
        return $request->user()->redirectToBillingPortal(route('dashboard'));
    }
}
