<?php

use App\Services\Payment\AbaPaywayService;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

test('aba payway service generates correct hmac-sha512 hash', function () {
    $service = new AbaPaywayService(
        baseUrl: 'https://checkout-sandbox.payway.com.kh',
        merchantId: 'ec479222',
        apiKey: 'TEST_API_KEY_12345'
    );

    $rawString = '20261009120000ec479222HOYOS-00000150.00';
    $expected = base64_encode(hash_hmac('sha512', $rawString, 'TEST_API_KEY_12345', true));

    expect($service->getHash($rawString))->toBe($expected);
});

test('aba payway service builds raw purchase string according to 24-field v3 specification', function () {
    $service = new AbaPaywayService(
        merchantId: 'ec479222',
        apiKey: 'TEST_API_KEY_12345'
    );

    $data = [
        'req_time'             => '20261009120000',
        'merchant_id'          => 'ec479222',
        'tran_id'              => 'HOYOS-000001',
        'amount'               => '50.00',
        'items'                => 'W3siaXRlbXMiOiJ0ZXN0In1d',
        'shipping'             => '2.00',
        'firstname'            => 'John',
        'lastname'             => 'Doe',
        'email'                => 'john@example.com',
        'phone'                => '012345678',
        'type'                 => 'purchase',
        'payment_option'       => 'abapay_khqr_deeplink',
        'return_url'           => 'http://example.com/return',
        'cancel_url'           => 'http://example.com/cancel',
        'continue_success_url' => 'http://example.com/continue',
        'return_deeplink'      => '',
        'currency'             => 'USD',
        'custom_fields'        => '',
        'return_params'        => '',
        'payout'               => '',
        'lifetime'             => '5',
        'additional_params'    => '',
        'google_pay_token'     => '',
        'skip_success_page'    => '1',
    ];

    $expected = '20261009120000ec479222HOYOS-00000150.00W3siaXRlbXMiOiJ0ZXN0In1d2.00JohnDoejohn@example.com012345678purchaseabapay_khqr_deeplinkhttp://example.com/returnhttp://example.com/cancelhttp://example.com/continueUSD51';

    $actual = $service->buildRawPurchaseString($data);
    expect($actual)->toBe($expected);
});

test('aba payway service builds legacy 16-field string when v3 fields are omitted', function () {
    $service = new AbaPaywayService(
        merchantId: 'ec479222',
        apiKey: 'TEST_API_KEY_12345'
    );

    $data = [
        'req_time'             => '20261009120000',
        'merchant_id'          => 'ec479222',
        'tran_id'              => 'HOYOS-000002',
        'amount'               => '25.00',
        'items'                => 'encoded_items',
        'shipping'             => '',
        'firstname'            => 'Alice',
        'lastname'             => 'Smith',
        'email'                => 'alice@example.com',
        'phone'                => '098765432',
        'type'                 => 'purchase',
        'payment_option'       => 'abapay_khqr',
        'return_url'           => 'http://example.com/return',
        'cancel_url'           => 'http://example.com/cancel',
        'continue_success_url' => 'http://example.com/continue',
        'return_params'        => '',
    ];

    $expected = '20261009120000ec479222HOYOS-00000225.00encoded_itemsAliceSmithalice@example.com098765432purchaseabapay_khqrhttp://example.com/returnhttp://example.com/cancelhttp://example.com/continue';

    $actual = $service->buildRawPurchaseString($data);
    expect($actual)->toBe($expected);
});

test('aba payway service verifies valid webhook signature', function () {
    $apiKey = 'TEST_API_KEY_12345';
    $service = new AbaPaywayService(apiKey: $apiKey);

    $tranId = 'HOYOS-000001';
    $status = '0';
    $hash = base64_encode(hash_hmac('sha512', $tranId . $status, $apiKey, true));

    $request = Request::create('/api/webhooks/aba', 'POST', [
        'tran_id' => $tranId,
        'status'  => $status,
        'hash'    => $hash,
    ]);

    expect($service->verifyWebhookSignature($request))->toBeTrue();
});

test('aba payway service rejects invalid webhook signature', function () {
    $service = new AbaPaywayService(apiKey: 'TEST_API_KEY_12345');

    $request = Request::create('/api/webhooks/aba', 'POST', [
        'tran_id' => 'HOYOS-000001',
        'status'  => '0',
        'hash'    => 'invalid_tampered_hash_value',
    ]);

    expect($service->verifyWebhookSignature($request))->toBeFalse();
});
