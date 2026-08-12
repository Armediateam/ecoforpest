<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class XenditPaymentService
{
    protected $client;
    protected $baseUrl = 'https://api.xendit.co';

    public function __construct()
    {
        $secretKey = config('services.xendit.secret_key');
        if (empty($secretKey)) {
            throw new \Exception('Xendit secret key is not configured');
        }

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'verify' => !app()->environment('local'),
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    public function createPayment(Invoice $invoice)
    {
        try {
            Log::info('Creating Xendit payment for invoice: ' . $invoice->invoice_number, [
                'amount' => $invoice->total,
                'secret_key_starts_with' => substr(config('services.xendit.secret_key'), 0, 15) . '...'
            ]);

            $payload = [
                'external_id' => $invoice->invoice_number,
                'amount' => $invoice->total,
                'description' => "Payment for invoice {$invoice->invoice_number}",
                'invoice_duration' => 86400,
                'success_redirect_url' => route('payment.success'),
                'failure_redirect_url' => route('payment.failure'),
                'currency' => 'IDR',
                'customer' => [
                    'given_names' => $invoice->customer->name,
                    'email' => $invoice->customer->email,
                ],
                'customer_notification_preference' => [
                    'invoice_created' => ['email'],
                    'invoice_paid' => ['email'],
                    'invoice_expired' => ['email'],
                ],
            ];

            $response = $this->client->post('/v2/invoices', [
                'json' => $payload
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info('Xendit response:', $responseBody);

            // Update invoice with payment details and raw data
            $invoice->update([
                'payment_url' => $responseBody['invoice_url'],
                'payment_id' => $responseBody['id'],
                'payment_status' => 'PENDING',
                'xendit_data_raw' => $responseBody
            ]);

            return $responseBody;
        } catch (GuzzleException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $errorBody = json_decode($e->getResponse()->getBody()->getContents(), true);
                $errorMessage = $errorBody['message'] ?? $errorMessage;
            }
            Log::error('Xendit payment creation failed', [
                'error' => $errorMessage,
                'invoice_number' => $invoice->invoice_number
            ]);
            throw new \Exception('Failed to create Xendit payment: ' . $errorMessage);
        }
    }

    public function handleCallback($payload)
    {
        try {
            Log::info('Received Xendit callback:', $payload);

            if ($payload['status'] === 'PAID') {
                $invoice = Invoice::where('payment_id', $payload['id'])->firstOrFail();
                
                // Update invoice status
                $invoice->update([
                    'payment_status' => 'PAID',
                    'status' => 'Paid',
                    'paid_at' => now(),
                    'xendit_data_raw' => $payload
                ]);

                // Create payment record
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $payload['amount'],
                    'payment_date' => now(),
                    'payment_mode' => $payload['payment_method'] ?? 'Online Payment',
                    'transaction_id' => $payload['id'],
                    'note' => "Payment received via Xendit - " . ($payload['payment_channel'] ?? 'Online Channel'),
                    'receipt' => $payload['paid_at'] ?? null,
                    'metadata' => [
                        'xendit_id' => $payload['id'],
                        'xendit_status' => $payload['status'],
                        'payment_channel' => $payload['payment_channel'] ?? null,
                        'paid_amount' => $payload['paid_amount'] ?? $payload['amount'],
                        'bank_code' => $payload['bank_code'] ?? null,
                        'payment_source' => $payload['payment_source'] ?? null,
                        'payment_method' => $payload['payment_method'] ?? null,
                        'payment_destination' => $payload['payment_destination'] ?? null,
                        'success_redirect_url' => $payload['success_redirect_url'] ?? null,
                        'failure_redirect_url' => $payload['failure_redirect_url'] ?? null,
                        'paid_at' => $payload['paid_at'] ?? null,
                        'payer_email' => $payload['payer_email'] ?? null,
                        'adjusted_received_amount' => $payload['adjusted_received_amount'] ?? null,
                        'fees_paid_amount' => $payload['fees_paid_amount'] ?? null,
                        'payment_timestamp' => $payload['payment_timestamp'] ?? now(),
                        'created' => $payload['created'] ?? null,
                        'updated' => $payload['updated'] ?? null
                    ]
                ]);
            } elseif ($payload['status'] === 'EXPIRED') {
                $invoice = Invoice::where('payment_id', $payload['id'])->firstOrFail();
                $invoice->update([
                    'payment_status' => 'EXPIRED',
                    'xendit_data_raw' => $payload
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Xendit callback processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            throw $e;
        }
    }
}
