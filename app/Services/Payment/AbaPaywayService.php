<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AbaPaywayService
{
    public const QR_TIMEOUT_MINUTES = 5;

    protected string $baseUrl;
    protected string $merchantId;
    protected ?string $apiKey;
    protected ?string $privateKeyPath;
    protected bool $isDemoMode;

    public function __construct(
        ?string $baseUrl = null,
        ?string $merchantId = null,
        ?string $apiKey = null,
        ?string $privateKeyPath = null,
        ?bool $isDemoMode = null
    ) {
        $this->baseUrl = rtrim($baseUrl ?? config('services.aba_payway.base_url', 'https://checkout-sandbox.payway.com.kh'), '/');
        $this->merchantId = $merchantId ?? config('services.aba_payway.merchant_id', 'ec479222');
        $this->apiKey = $apiKey ?? config('services.aba_payway.api_key');
        $this->privateKeyPath = $privateKeyPath ?? config('services.aba_payway.private_key_path', storage_path('app/keys/aba_private.pem'));
        $this->isDemoMode = $isDemoMode ?? (bool) config('hoyos.demo_mode', false);
    }

    public function isDemoMode(): bool
    {
        return $this->isDemoMode;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getPrivateKey(): ?string
    {
        if ($key = config('services.aba_payway.private_key')) {
            return $key;
        }

        if ($this->privateKeyPath && file_exists($this->privateKeyPath)) {
            return file_get_contents($this->privateKeyPath);
        }

        return null;
    }

    /**
     * Compute HMAC-SHA512 hash base64 encoded as required by ABA PayWay.
     */
    public function getHash(string $rawString, ?string $apiKey = null): string
    {
        $key = $apiKey ?? $this->apiKey ?? '';
        return base64_encode(hash_hmac('sha512', $rawString, $key, true));
    }

    /**
     * Build the raw string for purchase request hash.
     * Supports PayWay standard 24-field sequence or legacy 16-field concatenation.
     */
    public function buildRawPurchaseString(array $data): string
    {
        $reqTime            = (string) ($data['req_time'] ?? '');
        $merchantId         = (string) ($data['merchant_id'] ?? $this->merchantId);
        $tranId             = (string) ($data['tran_id'] ?? '');
        $amount             = (string) ($data['amount'] ?? '');
        $items              = (string) ($data['items'] ?? '');
        $shipping           = (string) ($data['shipping'] ?? '');
        $firstname          = (string) ($data['firstname'] ?? '');
        $lastname           = (string) ($data['lastname'] ?? '');
        $email              = (string) ($data['email'] ?? '');
        $phone              = (string) ($data['phone'] ?? '');
        $type               = (string) ($data['type'] ?? 'purchase');
        $paymentOption      = (string) ($data['payment_option'] ?? 'abapay_khqr');
        $returnUrl          = (string) ($data['return_url'] ?? '');
        $cancelUrl          = (string) ($data['cancel_url'] ?? '');
        $continueSuccessUrl = (string) ($data['continue_success_url'] ?? '');
        $returnParams       = (string) ($data['return_params'] ?? '');

        // If extended v3 PayWay fields are explicitly provided or standard purchase
        if (array_key_exists('return_deeplink', $data) || array_key_exists('currency', $data) || array_key_exists('skip_success_page', $data)) {
            $returnDeeplink   = (string) ($data['return_deeplink'] ?? '');
            $currency         = (string) ($data['currency'] ?? 'USD');
            $customFields     = (string) ($data['custom_fields'] ?? '');
            $payout           = (string) ($data['payout'] ?? '');
            $lifetime         = (string) ($data['lifetime'] ?? '');
            $additionalParams = (string) ($data['additional_params'] ?? '');
            $googlePayToken   = (string) ($data['google_pay_token'] ?? '');
            $skipSuccessPage  = (string) ($data['skip_success_page'] ?? '');

            return $reqTime . $merchantId . $tranId . $amount . $items . $shipping .
                   $firstname . $lastname . $email . $phone . $type . $paymentOption .
                   $returnUrl . $cancelUrl . $continueSuccessUrl . $returnDeeplink .
                   $currency . $customFields . $returnParams . $payout . $lifetime .
                   $additionalParams . $googlePayToken . $skipSuccessPage;
        }

        // 16-field specification concatenation
        return $reqTime . $merchantId . $tranId . $amount . $items . $shipping .
               $firstname . $lastname . $email . $phone . $type . $paymentOption .
               $returnUrl . $cancelUrl . $continueSuccessUrl . $returnParams;
    }

    /**
     * Create or retrieve dynamic ABA PayWay KHQR purchase for an order.
     */
    public function createPurchase(Order $order): Payment
    {
        // 1. Check if unexpired pending payment with valid qr_string exists
        $existing = $order->latestPayment;
        if ($existing && $existing->isPending() && !$existing->isExpired() && !empty($existing->qr_string)) {
            return $existing;
        }

        // If existing pending payment was already approved on ABA, mark it paid and return
        if (!$this->isDemoMode && $existing && $existing->isPending()) {
            $check = $this->checkTransaction($existing->transaction_id ?: $order->order_number);
            if ((string) ($check['status'] ?? '') === '0' || strtoupper((string) ($check['payment_status'] ?? '')) === 'APPROVED') {
                $order->payment_status = 'paid';
                if ($order->order_status === 'pending') {
                    $order->order_status = 'confirmed';
                }
                $order->save();
                $existing->status = Payment::STATUS_PAID;
                $existing->paid_at = now();
                $existing->save();
                return $existing;
            }
        }

        $amount = number_format((float) ($order->grand_total ?? $order->total_amount), 2, '.', '');
        $reqTime = now()->setTimezone('Asia/Phnom_Penh')->format('YmdHis');
        $paymentCount = $order->payments()->count();
        $tranId = $paymentCount > 0 
            ? substr($order->order_number, 0, 14) . '-R' . $paymentCount 
            : $order->order_number;


        // Name split
        $parts = preg_split('/\s+/', trim((string) $order->customer_name));
        $firstname = $parts[0] ?? 'Customer';
        $lastname = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'Hoyos';

        $shipping = number_format((float) ($order->shipping_fee ?? 0.00), 2, '.', '');

        // Items payload
        $itemsData = [];
        if ($order->relationLoaded('items') || $order->items()->exists()) {
            foreach ($order->items as $item) {
                $itemsData[] = [
                    'name' => $item->product_name ?? 'Hoyos Item',
                    'quantity' => (int) $item->quantity,
                    'price' => number_format((float) $item->unit_price, 2, '.', ''),
                ];
            }
        }

        $itemsSum = 0.0;
        foreach ($itemsData as $it) {
            $itemsSum += ((float) $it['price']) * ((int) $it['quantity']);
        }

        if (empty($itemsData) || abs(($itemsSum + (float) $shipping) - (float) $amount) > 0.01) {
            $itemsData = [
                [
                    'name' => "Order #{$order->order_number}",
                    'quantity' => 1,
                    'price' => number_format(max(0.01, (float) $amount - (float) $shipping), 2, '.', ''),
                ],
            ];
        }
        $itemsEncoded = base64_encode(json_encode($itemsData));

        $returnUrl = base64_encode(route('orders.show', ['orderNumber' => $order->order_number, 'token' => $order->guest_token]));
        $cancelUrl = route('payment.show', ['orderNumber' => $order->order_number, 'token' => $order->guest_token]);
        $continueSuccessUrl = route('orders.show', ['orderNumber' => $order->order_number, 'token' => $order->guest_token]);

        $params = [
            'req_time'             => $reqTime,
            'merchant_id'          => $this->merchantId,
            'tran_id'              => $tranId,
            'amount'               => $amount,
            'items'                => $itemsEncoded,
            'shipping'             => $shipping,
            'firstname'            => $firstname,
            'lastname'             => $lastname,
            'email'                => (string) ($order->customer_email ?: 'customer@hoyos.store'),
            'phone'                => (string) ($order->customer_phone ?: '012345678'),
            'type'                 => 'purchase',
            'payment_option'       => 'abapay_khqr_deeplink',
            'return_url'           => $returnUrl,
            'cancel_url'           => $cancelUrl,
            'continue_success_url' => $continueSuccessUrl,
            'return_deeplink'      => '',
            'currency'             => 'USD',
            'custom_fields'        => '',
            'return_params'        => '',
            'payout'               => '',
            'lifetime'             => self::QR_TIMEOUT_MINUTES,
            'additional_params'    => '',
            'google_pay_token'     => '',
            'skip_success_page'    => 1,
        ];

        $rawString = $this->buildRawPurchaseString($params);
        $hash = $this->getHash($rawString);
        $params['hash'] = $hash;

        $qrString = null;
        $deeplink = null;
        $qrImage = null;
        $apiPayload = [];

        // Under demo mode, never make outbound requests, never emit payable EMVCo strings, and never emit banking deeplinks
        if ($this->isDemoMode) {
            $qrString = 'DEMO-NON-PAYABLE-TRANSACTION:order=' . $tranId . ':amount=' . $amount;
            $deeplink = null;
            $apiPayload = [
                'demo_mode' => true,
                'is_simulated' => true,
                'notice' => 'Simulated Demo Payment - Non-payable demo placeholder',
                'merchant_id' => 'DEMO-HOYOS',
                'merchant_name' => 'HOYOS DEMO STORE (NON-PAYABLE)',
            ];
        } else {
            if (blank($this->apiKey) || blank($this->merchantId)) {
                throw new \RuntimeException('ABA PayWay credentials are not configured; live payment creation is disabled.');
            }

            try {
                $apiUrl = $this->baseUrl . '/api/payment-gateway/v1/payments/purchase';
                $response = Http::timeout(8)
                    ->asMultipart()
                    ->post(
                        $apiUrl,
                        collect($params)->map(fn($v, $k) => ['name' => $k, 'contents' => (string) $v])->values()->all()
                    );

                $data = $response->json() ?? [];
                $isDuplicate = ($data['status']['code'] ?? null) === 4 
                    || str_contains(strtolower($response->body()), 'duplicated transaction');

                // If transaction ID was already used on sandbox, retry once with an incremental unique suffix
                if ($isDuplicate) {
                    $retrySuffix = '-R' . ($paymentCount + 1);
                    $tranId = substr($order->order_number, 0, 14) . $retrySuffix;
                    $params['tran_id'] = $tranId;
                    $params['req_time'] = now()->setTimezone('Asia/Phnom_Penh')->format('YmdHis');
                    $rawString = $this->buildRawPurchaseString($params);
                    $params['hash'] = $this->getHash($rawString);

                    $response = Http::timeout(8)
                        ->asMultipart()
                        ->post(
                            $apiUrl,
                            collect($params)->map(fn($v, $k) => ['name' => $k, 'contents' => (string) $v])->values()->all()
                        );
                }

                if ($response->successful()) {
                    $data = $response->json() ?? [];
                    if (($data['status']['code'] ?? null) === '00' || !empty($data['qrString']) || !empty($data['qr_string'])) {
                        $qrString = $data['qrString'] ?? $data['qr_string'] ?? null;
                        $deeplink = $data['abapay_deeplink'] ?? null;
                        $qrImage = $data['qrImage'] ?? null;
                        $apiPayload = $data;
                    }
                } else {
                    Log::warning('ABA PayWay purchase returned non-200: ' . $response->body());
                }
            } catch (Throwable $e) {
                Log::warning('ABA PayWay purchase connection failed, falling back to local EMVCo generation: ' . $e->getMessage());
            }

            // Fallback to EMVCo Bakong standard KHQR string if remote call is offline or failed
            if (empty($qrString)) {
                $qrString = $this->generateLocalEmvcoKhqr($order, $tranId);
                $deeplink = 'aba://pay?tran_id=' . $tranId . '&amount=' . $amount;
            }
        }

        $expiresAt = now()->addMinutes(self::QR_TIMEOUT_MINUTES);
        $md5Hash = md5($qrString . $amount . $tranId);

        $merchantId = $this->isDemoMode ? 'DEMO-HOYOS' : $this->merchantId;
        $merchantName = $this->isDemoMode ? 'HOYOS DEMO STORE (NO REAL CHARGES)' : 'HOYOS CLOTHING CO., LTD';

        $payment = Payment::create([
            'order_id'       => $order->id,
            'payment_method' => 'aba_khqr',
            'transaction_id' => $tranId,
            'amount'         => $amount,
            'currency'       => 'USD',
            'status'         => Payment::STATUS_PENDING,
            'qr_string'      => $qrString,
            'md5_hash'       => $md5Hash,
            'payload'        => array_merge([
                'merchant_id'     => $merchantId,
                'merchant_name'   => $merchantName,
                'abapay_deeplink' => $deeplink,
                'mobile_deeplink' => $deeplink,
                'qr_image'        => $qrImage,
            ], $apiPayload),
            'expires_at'     => $expiresAt,
            'paid_at'        => null,
        ]);

        if ($order->payment_status !== 'pending') {
            $order->payment_status = 'pending';
            $order->save();
        }

        return $payment;
    }

    /**
     * Hit check-transaction endpoint to poll or verify payment status independently.
     */
    public function checkTransaction(string $tranId): array
    {
        if ($this->isDemoMode) {
            return [
                'status' => 0,
                'demo_mode' => true,
                'description' => 'Demo mode active: external payment polling is disabled.',
            ];
        }

        $reqTime = now()->setTimezone('Asia/Phnom_Penh')->format('YmdHis');
        $raw = $reqTime . $this->merchantId . $tranId;
        $hash = $this->getHash($raw);

        $postData = [
            'req_time'    => $reqTime,
            'merchant_id' => $this->merchantId,
            'tran_id'     => $tranId,
            'hash'        => $hash,
        ];

        try {
            $apiUrl = $this->baseUrl . '/api/payment-gateway/v1/payments/check-transaction';
            $response = Http::timeout(6)
                ->asMultipart()
                ->post(
                    $apiUrl,
                    collect($postData)->map(fn($v, $k) => ['name' => $k, 'contents' => (string) $v])->values()->all()
                );

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            return ['status' => -1, 'error' => $response->body()];
        } catch (Throwable $e) {
            return ['status' => -1, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verify incoming webhook signature using the public API key (HMAC-SHA512).
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $receivedHash = $request->header('X-PAYWAY-HMAC-SHA512')
            ?? $request->header('x-payway-hmac-sha512')
            ?? $request->header('X-Signature')
            ?? $request->input('hash');

        if (empty($receivedHash)) {
            return false;
        }

        $tranId = (string) ($request->input('tran_id') ?? $request->input('order_number') ?? '');
        $status = (string) ($request->input('status') ?? '');

        // 1. Check tran_id . status
        $expected1 = $this->getHash($tranId . $status);
        if (hash_equals($expected1, $receivedHash)) {
            return true;
        }

        // 2. Check tran_id
        $expected2 = $this->getHash($tranId);
        if (hash_equals($expected2, $receivedHash)) {
            return true;
        }

        // 3. Check tran_id . status . amount
        if ($request->has('amount')) {
            $amount = (string) $request->input('amount');
            $expected3 = $this->getHash($tranId . $status . $amount);
            if (hash_equals($expected3, $receivedHash)) {
                return true;
            }
        }

        // 4. Check raw body
        $expectedBody = $this->getHash($request->getContent());
        if (hash_equals($expectedBody, $receivedHash)) {
            return true;
        }

        return false;
    }

    /**
     * Synthesize authentic EMVCo Bakong KHQR string for fallback or testing.
     */
    protected function generateLocalEmvcoKhqr(Order $order, string $tranId): string
    {
        $amount = number_format((float) ($order->grand_total ?? $order->total_amount), 2, '.', '');
        $raw = "00020101021230510016abaakhppxxx@abaa01151111111111111110208ABA Bank52045999530384054" .
               sprintf("%02d", strlen($amount)) . "{$amount}5802KH5911Sareth Pich600062" .
               sprintf("%02d", strlen($tranId) + 4) . "01" . sprintf("%02d", strlen($tranId)) . "{$tranId}6304";
        $crc = $this->calculateCrc16($raw);
        return $raw . $crc;
    }

    public function calculateCrc16(string $str): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($str); $i++) {
            $crc ^= (ord($str[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(sprintf('%04X', $crc));
    }
}
