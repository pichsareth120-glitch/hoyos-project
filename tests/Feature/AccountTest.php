<?php

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('unauthenticated user is redirected to login when visiting account hub', function () {
    $response = $this->get('/account');

    $response->assertRedirect('/login');
});

test('authenticated user can view account hub without route errors', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/account');

    $response->assertOk();
    $response->assertSee($user->name);
    $response->assertSee($user->email);
});

test('user can update personal profile information', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/account/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'title' => 'Mr.',
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '012345678',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account?tab=personal')
        ->assertSessionHas('success', 'Personal information updated successfully.');

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('updated@example.com')
        ->and($user->phone)->toBe('012345678')
        ->and($user->first_name)->toBe('Updated')
        ->and($user->last_name)->toBe('Name')
        ->and($user->title)->toBe('Mr.');
});

test('email verification is cleared when email changes in account hub', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/account/profile', [
            'name' => $user->name,
            'email' => 'new-unique-email@example.com',
        ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->email)->toBe('new-unique-email@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

test('user cannot update profile with existing email belonging to another user', function () {
    $userA = User::factory()->create(['email' => 'user.a@example.com']);
    $userB = User::factory()->create(['email' => 'user.b@example.com']);

    $response = $this
        ->actingAs($userB)
        ->from('/account?tab=personal')
        ->put('/account/profile', [
            'name' => 'User B',
            'email' => 'user.a@example.com',
        ]);

    $response
        ->assertSessionHasErrors(['email'])
        ->assertRedirect('/account?tab=personal');

    expect($userB->fresh()->email)->toBe('user.b@example.com');
});

test('user can update date of birth with valid calendar date', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/account/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'birth_day' => '15',
            'birth_month' => '8',
            'birth_year' => '1995',
        ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->date_of_birth?->format('Y-m-d'))->toBe('1995-08-15');
});

test('user receives validation error when submitting incomplete birth date', function () {
    $user = User::factory()->create([
        'date_of_birth' => '1990-01-01',
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/account?tab=personal')
        ->put('/account/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'birth_day' => '',
            'birth_month' => '5',
            'birth_year' => '1998',
        ]);

    $response
        ->assertSessionHasErrors(['birth_day'])
        ->assertRedirect('/account?tab=personal');

    // Original birth date must not be overwritten or wiped out
    expect($user->fresh()->date_of_birth?->format('Y-m-d'))->toBe('1990-01-01');
});

test('user receives validation error when submitting impossible calendar date without 500 error', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/account?tab=personal')
        ->put('/account/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'birth_day' => '31',
            'birth_month' => '2', // Feb 31 does not exist
            'birth_year' => '2000',
        ]);

    $response
        ->assertSessionHasErrors(['birth_day'])
        ->assertRedirect('/account?tab=personal');
});

test('user can update password with valid current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this
        ->actingAs($user)
        ->put('/account/password', [
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account?tab=personal')
        ->assertSessionHas('success', 'Password updated successfully.');

    $user->refresh();
    expect(Hash::check('new-password-456', $user->password))->toBeTrue();
});

test('user cannot update password with invalid current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('real-password-123'),
    ]);

    $response = $this
        ->actingAs($user)
        ->from('/account?tab=personal')
        ->put('/account/password', [
            'current_password' => 'wrong-password-999',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ]);

    $response
        ->assertSessionHasErrors(['current_password'])
        ->assertRedirect('/account?tab=personal');

    expect(Hash::check('real-password-123', $user->fresh()->password))->toBeTrue();
});

test('user can add and delete shipping address', function () {
    $user = User::factory()->create();

    // 1. Add Address
    $response = $this
        ->actingAs($user)
        ->post('/account/address', [
            'recipient_name' => 'John Doe',
            'recipient_phone' => '012345678',
            'province' => 'Phnom Penh',
            'address_line' => 'Street 2004, Sen Sok',
            'notes' => 'Ring bell twice',
            'is_default' => '1',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account?tab=addresses')
        ->assertSessionHas('success', 'Address added successfully.');

    $address = $user->addresses()->first();
    expect($address)->not->toBeNull()
        ->and($address->recipient_name)->toBe('John Doe')
        ->and($address->province)->toBe('Phnom Penh')
        ->and($address->is_default)->toBeTrue();

    // 2. Delete Address
    $deleteResponse = $this
        ->actingAs($user)
        ->delete('/account/address/' . $address->id);

    $deleteResponse
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account?tab=addresses')
        ->assertSessionHas('success', 'Address deleted successfully.');

    expect(Address::find($address->id))->toBeNull();
});

test('address store validates required fields', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/account?tab=addresses')
        ->post('/account/address', [
            'recipient_name' => '',
            'recipient_phone' => '',
            'province' => '',
            'address_line' => '',
        ]);

    $response
        ->assertSessionHasErrors(['recipient_name', 'recipient_phone', 'province', 'address_line'])
        ->assertRedirect('/account?tab=addresses');
});
