<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class Khalti
{
    public $amount;
    public $base_url;
    public $purchase_order_id;
    public $purchase_order_name;
    public $inquiry_response;
    public $customer_name;
    public $customer_phone;
    public $customer_email;
    private $secret_key;

    public function __construct()
    {
        // Use account mode from config settings
        $account_mode = config('gatewaySettings.khalti_account_mode', 'sandbox');
        $this->base_url = $account_mode === 'sandbox' ? 'https://a.khalti.com/api/v2/' : 'https://khalti.com/api/v2/';

        // Use secret key from config settings
        $this->secret_key = config('gatewaySettings.khalti_secret_key', '604dadde20c84a22932f206899857602');
    }

    /**
     * Set customer information for the payment
     */
    public function byCustomer($name, $email, $phone)
    {
        $this->customer_name = $name;
        $this->customer_email = $email;
        $this->customer_phone = $phone;
        return $this;
    }

    /**
     * Initiate payment with Khalti
     */
    public function pay(float $amount, string $return_url, string $purchase_order_id, string $purchase_order_name)
    {
        $this->purchase_order_id = $purchase_order_id;
        $this->purchase_order_name = $purchase_order_name;

        try {
            $response = $this->initiate($amount, $return_url);

            if (isset($response['payment_url'])) {
                return redirect()->away($response['payment_url']);
            } else {
                Log::error('Khalti Payment Error: No payment URL received', $response);
                return redirect()->route('company.payment.error')
                    ->withErrors(['error' => 'Payment initialization failed']);
            }
        } catch (Exception $e) {
            Log::error('Khalti Payment Exception: ' . $e->getMessage());
            return redirect()->route('company.payment.error')
                ->withErrors(['error' => 'Payment service unavailable']);
        }
    }

    /**
     * Initiate payment request to Khalti API
     */
    public function initiate(float $amount, string $return_url, ?array $arguments = null)
    {
        // Convert amount to paisa (Khalti uses paisa as base unit)
        // Always use the actual plan amount so users see the correct amount on Khalti page
        // Development testing will be handled through Khalti's sandbox environment
        $this->amount = $amount * 100;

        $process_url = $this->base_url . 'epayment/initiate/';
        $website_url = url('/');

        // Build the data array following Khalti API specification
        $data = [
            "return_url" => $return_url,
            "website_url" => $website_url,
            "amount" => $this->amount,
            "purchase_order_id" => $this->purchase_order_id,
            "purchase_order_name" => $this->purchase_order_name,
            "customer_info" => [
                "name" => $this->customer_name,
                "email" => $this->customer_email,
                "phone" => $this->customer_phone
            ]
        ];

        // Add any additional arguments
        if ($arguments) {
            $data = array_merge($data, $arguments);
        }

        Log::info('Khalti Payment Initiation', [
            'amount' => $this->amount,
            'order_id' => $this->purchase_order_id,
            'customer' => $this->customer_name
        ]);

        // Make HTTP request to Khalti API
        $response = Http::withHeaders([
            'Authorization' => 'key ' . $this->secret_key,
            'Content-Type' => 'application/json',
        ])->post($process_url, $data);

        if ($response->successful()) {
            $responseData = $response->json();
            Log::info('Khalti Payment Initiated Successfully', $responseData);
            return $responseData;
        } else {
            $error = $response->json();
            Log::error('Khalti Payment Initiation Failed', [
                'status' => $response->status(),
                'error' => $error
            ]);
            throw new Exception('Payment initiation failed: ' . ($error['detail'] ?? 'Unknown error'));
        }
    }

    /**
     * Verify payment status with Khalti
     */
    public function inquiry(string $pidx)
    {
        try {
            $inquiry_url = $this->base_url . 'epayment/lookup/';

            $response = Http::withHeaders([
                'Authorization' => 'key ' . $this->secret_key,
                'Content-Type' => 'application/json',
            ])->post($inquiry_url, [
                'pidx' => $pidx
            ]);

            if ($response->successful()) {
                $this->inquiry_response = $response->json();
                Log::info('Khalti Payment Inquiry Successful', [
                    'pidx' => $pidx,
                    'status' => $this->inquiry_response['status'] ?? 'unknown'
                ]);
                return $this->inquiry_response;
            } else {
                $error = $response->json();
                Log::error('Khalti Payment Inquiry Failed', [
                    'pidx' => $pidx,
                    'status' => $response->status(),
                    'error' => $error
                ]);
                throw new Exception('Payment inquiry failed: ' . ($error['detail'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            Log::error('Khalti Payment Inquiry Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if payment was successful
     */
    public function isSuccess($inquiry_response = null): bool
    {
        $response = $inquiry_response ?? $this->inquiry_response;

        if (!$response) {
            return false;
        }

        // Khalti considers payment successful when status is 'Completed'
        return isset($response['status']) && $response['status'] === 'Completed';
    }

    /**
     * Get the requested amount from inquiry response
     */
    public function requestedAmount($inquiry_response = null): float
    {
        $response = $inquiry_response ?? $this->inquiry_response;

        if (!$response || !isset($response['total_amount'])) {
            return 0;
        }

        // Return amount in paisa (Khalti's base unit)
        return (float) $response['total_amount'];
    }

    /**
     * Get transaction ID from inquiry response
     */
    public function getTransactionId($inquiry_response = null): ?string
    {
        $response = $inquiry_response ?? $this->inquiry_response;

        return $response['transaction_id'] ?? null;
    }

    /**
     * Get payment method used
     */
    public function getPaymentMethod($inquiry_response = null): ?string
    {
        $response = $inquiry_response ?? $this->inquiry_response;

        return $response['payment_method'] ?? 'khalti';
    }
}
