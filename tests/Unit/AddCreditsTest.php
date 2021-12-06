<?php

use App\Models\User;

uses(Tests\TestCase::class);

test('A função "addCredits" deve incrementar o crédito do usuário e registrar uma transação', function () {
    $user = User::factory()->create();

    $user->addCredits(100);

    $user->refresh();
    expect($user->credit)->toBe(100);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'amount' => 100,
        'description' => 'Added credits'
    ]);

    $user->addCredits(50);

    $user->refresh();
    expect($user->credit)->toBe(150);
});
