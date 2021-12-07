<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'stripe_customer_id',
        'default_payment_method_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'stripe_customer_id',
        'default_payment_method_id'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function addCredits(int $amount)
    {
        $user = $this;

        DB::transaction(function () use ($amount, $user) {
            $user->transactions()->create([
                'amount' => $amount,
                'description' => 'Added credits'
            ]);

            $user->increment('credit', $amount);
        });
    }

    public function createOrGetStripeCustomer()
    {
        if (isset($this->stripe_customer_id)) {
            return \Stripe\Customer::retrieve($this->stripe_customer_id);
        } else {
            $customer = \Stripe\Customer::create([
                'name' => $this->name,
                'email' => $this->email,
                // 'phone' => $this->phone,
                // 'metadata' => []
            ]);

            $this->update([
                'stripe_customer_id' => $customer->id
            ]);

            return $customer;
        }
    }

    public function attachPaymentMethod(string $payment_method_id)
    {
        \Stripe\PaymentMethod::retrieve($payment_method_id)->attach([
            'customer' => $this->stripe_customer_id,
        ]);

        $this->update([
            'default_payment_method_id' => $payment_method_id
        ]);
    }
}
