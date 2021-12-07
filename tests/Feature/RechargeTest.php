<?php

use App\Models\User;

use function Pest\Laravel\{postJson, actingAs};
use function Pest\Faker\faker;

test('Deve retornar um erro 401 se não houver um usuário logado', function () {
    postJson(route('recharge.post'))
        ->assertUnauthorized();
});

test('Deve retornar um erro 422 se não houver um campo amount', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    actingAs($user)
        ->postJson(route('recharge.post'), [])
        ->assertJsonValidationErrors([
            'amount' => 'required'
        ])
        ->assertUnprocessable();
});

test('Deve retornar um erro 422 se o campo amount não for um valor válido', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => 'hello'
        ])
        ->assertJsonValidationErrors([
            'amount' => 'only_numbers'
        ])
        ->assertUnprocessable();

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => '1.000'
        ])
        ->assertJsonValidationErrors([
            'amount' => 'only_numbers'
        ])
        ->assertUnprocessable();

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => '10,00'
        ])
        ->assertJsonValidationErrors([
            'amount' => 'only_numbers'
        ])
        ->assertUnprocessable();
});

test('Deve retornar um erro 422 se tentar usar um amount menor que $1', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => 0
        ])
        ->assertJsonValidationErrors([
            'amount' => 'min.numeric'
        ])
        ->assertUnprocessable();
});

test('Deve criar uma conta de cliente no Stripe', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => 1_000
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->stripe_customer_id)->not->toBeNull();
    expect($user->stripe_customer_id)->toStartWith('cus_');
});

test('Deve redirecionar o usuário de volta se ele não tiver um método de pagamento cadastrado', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    actingAs($user)
        ->from(route('recharge.post'))
        ->postJson(route('recharge.post'), [
            'amount' => 1_000
        ])
        ->assertSessionHas('show_payment_form', true)
        ->assertRedirect(route('recharge.post'));
});

test('Deve retornar um erro 422 se usar um "payment_method" inválido', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => 1_000,
            'payment_method' => 'fake_id_123'
        ])
        ->assertJsonValidationErrors([
            'payment_method' => 'starts_with'
        ])
        ->assertUnprocessable();
});

test('Deve adicionar as informações do cartão na conta do cliente no Stripe', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    $pm = createTestCreditCard();

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => 1_000,
            'payment_method' => $pm->id
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->default_payment_method_id)->not->toBeNull();
    expect($user->default_payment_method_id)->toStartWith('pm_');

    $userPaymentMethods = \Stripe\Customer::allPaymentMethods($user->stripe_customer_id, [
        'type' => 'card'
    ])->data;
    expect($userPaymentMethods)->toHaveLength(1);
    expect($userPaymentMethods[0])->toHaveKey('id', $pm->id);
});

test('Deve aumentar os créditos do usuário se estiver tudo ok', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    $pm = createTestCreditCard();
    $amount = faker()->numberBetween(10, 10_000);

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => $amount,
            'payment_method' => $pm->id
        ])
        ->assertSessionMissing('error')
        ->assertSessionHas('success')
        ->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->credit)->toBe($amount);

    $prevCreditBalance = $user->credit;
    $amount = faker()->numberBetween(10, 10_000);

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => $amount,
            'payment_method' => $pm->id
        ])
        ->assertSessionMissing('error')
        ->assertSessionHas('success')
        ->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->credit)->toBe($prevCreditBalance + $amount);
});

test('Deve conseguir realizar a cobrança corretamente', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    $pm = createTestCreditCard();

    $amount = faker()->numberBetween(10, 10_000);
    $amountInCents = $amount * 100;

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => $amount,
            'payment_method' => $pm->id
        ])
        ->assertSessionMissing('error')
        ->assertSessionHas('success')
        ->assertRedirect(route('dashboard'));

    // Checar a última cobrança do usuário
    $userCharges = \Stripe\Charge::all([
        'customer' => $user->stripe_customer_id,
    ])->data;
    expect($userCharges)->toHaveLength(1);
    expect($userCharges[0])->toHaveKey('amount', $amountInCents);

    // Checar a última transação feita na conta do Stripe
    $lastTransaction = \Stripe\BalanceTransaction::all([
        'limit' => 1
    ])->data[0];
    expect($lastTransaction->reporting_category)->toBe('charge');
    expect($lastTransaction->amount)->toBe($amountInCents);
});

test('Deve informar ao usuário se a cobrança foi mal sucedida', function () {
    /** @var \App\Models\User */
    $user = User::factory()->create();

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => faker()->numberBetween(10, 10_000),
            'payment_method' => createTestCreditCard('insufficient_funds')->id
        ])
        ->assertSessionHas([
            'error' => 'Unable to add this card in your account. Your card has insufficient funds.'
        ])
        ->assertRedirect();

    actingAs($user)
        ->postJson(route('recharge.post'), [
            'amount' => faker()->numberBetween(10, 10_000),
            'payment_method' => createTestCreditCard('expired')->id
        ])
        ->assertSessionHas([
            'error' => 'Unable to add this card in your account. Your card has expired.'
        ])
        ->assertRedirect();

    $userCharges = \Stripe\Charge::all(['customer' => $user->stripe_customer_id])->data;
    expect($userCharges)->toHaveLength(0);
});
