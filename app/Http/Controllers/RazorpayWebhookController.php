<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $webhookSecret = config('razorpay.webhook_secret');
        $signature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();

        if (!$webhookSecret || !$signature) {
            Log::warning('Razorpay webhook missing secret or signature.');
            return response()->json(['message' => 'Invalid webhook'], 400);
        }

        $generatedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        if (!hash_equals($generatedSignature, $signature)) {
            Log::warning('Razorpay webhook signature mismatch.');
            return response()->json(['message' => 'Signature mismatch'], 400);
        }

        $data = $request->all();
        $event = $data['event'] ?? null;

        if ($event === 'payment.captured') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? null;

            if ($paymentEntity) {
                $paymentId = $paymentEntity['id'] ?? null;
                $amount = isset($paymentEntity['amount']) ? ($paymentEntity['amount'] / 100) : null;
                $notes = $paymentEntity['notes'] ?? [];

                // If business_id passed in Razorpay notes
                $businessId = $notes['business_id'] ?? null;
                $plan = $notes['plan'] ?? null;

                if ($businessId) {
                    $business = Business::find($businessId);

                    if ($business) {
                        $updateData = [
                            'payment_status' => 'paid',
                            'payment_id' => $paymentId,
                        ];

                        if ($amount) {
                            $updateData['payment_amount'] = $amount;
                        }

                        if ($plan) {
                            $updateData['plan'] = $plan;
                            $updateData['payment_plan'] = $plan;

                            if ($plan === 'featured') {
                                $updateData['featured'] = 1;
                            } elseif ($plan === 'premium') {
                                $updateData['featured'] = 0;
                            }
                        }

                        $business->update($updateData);

                        Log::info('Razorpay webhook payment updated for business ID: ' . $businessId);
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
