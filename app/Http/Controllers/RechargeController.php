<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Rules\OnlyNumbers;
use App\Events\RechargedEvent;
use Exception;

class RechargeController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'amount' => ['required', new OnlyNumbers(), 'numeric', 'min:1'],
            'payment_method' => ['sometimes', 'string', 'starts_with:pm_']
        ]);
        /** @var \App\Models\User */
        $user = Auth::user();

        $user->createOrGetStripeCustomer();

        if (
            !$user->default_payment_method_id &&
            !$request->has('payment_method')
        ) {
            return redirect()->back()->withInput()->with([
                'show_payment_form' => true,
                'setup_intent_secret' => \Stripe\SetupIntent::create()->client_secret,
            ]);
        }

        if (
            !$user->default_payment_method_id &&
            $request->has('payment_method')
        ) {
            try {
                $user->attachPaymentMethod($request->get('payment_method'));
            } catch (Exception $e) {
                return redirect()->back()->withInput()->with([
                    'error' => 'Unable to add this card in your account. ' . $e->getMessage(),
                ]);
            }
        }

        $amount = intval($request->get('amount'));
        $amountInCents = $amount * 100;

        try {
            // Charge the user
            \Stripe\PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => 'brl',
                'confirmation_method' => 'automatic',
                'confirm' => true,
                'payment_method' => $user->default_payment_method_id,
                'customer' => $user->stripe_customer_id
            ]);

            $user->increment('credit', $amount);
        } catch (Exception $e) {
            // dd($e);
            return redirect()->back()->withInput()->with([
                'error' => 'An error occurred while charging your card. Try again',
            ]);
        }

        RechargedEvent::dispatch();

        return redirect()->route('dashboard')->with([
            'success' => 'Recharged successfully!'
        ]);
    }
}
