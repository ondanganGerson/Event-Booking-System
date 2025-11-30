<?php

namespace App\Services;

use App\Booking;
use App\Payment;

class PaymentService
{
    /**
     * Process a payment for a booking.
     *
     * @param  Booking  $booking
     * @param  float  $amount
     * @return Payment
     */
    public function processPayment(Booking $booking, $amount)
    {
        // Simulate payment processing (70% success rate)
        $isSuccess = rand(1, 100) <= 70;

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => $isSuccess ? 'success' : 'failed',
        ]);

        // Update booking status based on payment result
        if ($isSuccess) {
            $booking->update(['status' => 'confirmed']);
        } else {
            $booking->update(['status' => 'cancelled']);
        }

        return $payment;
    }

    /**
     * Refund a payment.
     *
     * @param  Payment  $payment
     * @return Payment
     */
    public function refundPayment(Payment $payment)
    {
        if ($payment->status !== 'success') {
            throw new \Exception('Only successful payments can be refunded.');
        }

        $payment->update(['status' => 'refunded']);

        // Update the associated booking status
        $payment->booking->update(['status' => 'cancelled']);

        return $payment;
    }

    /**
     * Calculate total amount for a booking.
     *
     * @param  Booking  $booking
     * @return float
     */
    public function calculateAmount(Booking $booking)
    {
        return $booking->ticket->price * $booking->quantity;
    }
}
