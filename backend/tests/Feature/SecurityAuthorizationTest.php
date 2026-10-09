<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\User;

test('user A cannot access user B order', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $orderB = Order::factory()->create([
        'user_id' => $userB->id,
        'guest_token' => null,
    ]);

    // User A attempts to view User B's order receipt
    $response = $this->actingAs($userA)->get('/orders/' . $orderB->order_number);
    $response->assertStatus(403);

    // User A attempts to view User B's order tracking
    $trackResponse = $this->actingAs($userA)->get('/orders/' . $orderB->order_number . '/track');
    $trackResponse->assertStatus(403);
});

test('user A cannot delete user B address', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $addressB = Address::factory()->create([
        'user_id' => $userB->id,
    ]);

    // User A attempts to delete User B's address
    $response = $this->actingAs($userA)->delete('/account/address/' . $addressB->id);
    $response->assertStatus(403);

    // Ensure address was not deleted
    expect(Address::find($addressB->id))->not->toBeNull();
});

test('guest A cannot access guest B order with mismatched or missing token', function () {
    $orderB = Order::factory()->create([
        'guest_token' => 'legitimate-token-for-buyer-b-12345',
    ]);

    // Request with no token
    $noTokenResponse = $this->get('/orders/' . $orderB->order_number);
    $noTokenResponse->assertStatus(403);

    // Request with attacker token
    $wrongTokenResponse = $this->get('/orders/' . $orderB->order_number . '?token=attacker-token-99999');
    $wrongTokenResponse->assertStatus(403);

    // Request with legitimate token succeeds
    $validTokenResponse = $this->get('/orders/' . $orderB->order_number . '?token=legitimate-token-for-buyer-b-12345');
    $validTokenResponse->assertStatus(200);
});
