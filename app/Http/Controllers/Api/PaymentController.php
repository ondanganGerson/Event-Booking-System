<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Booking;
use App\Payment;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * The payment service instance.
     *
     * @var PaymentService
     */
    protected $paymentService;

    /**
     * Create a new controller instance.
     *
     * @param  PaymentService  $paymentService
     * @return void
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Process payment for a booking.
     *
     * @param  int  $bookingId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store($bookingId)
    {
        // Validate that the booking exists
        $booking = Booking::find($bookingId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.'
            ], 404);
        }

        // Check if booking already has a payment
        if ($booking->payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already exists for this booking.',
                'payment' => $booking->payment
            ], 400);
        }

        // Check if booking status is valid for payment
        if ($booking->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending bookings can be paid.'
            ], 400);
        }

        try {
            // Calculate the total amount
            $amount = $this->paymentService->calculateAmount($booking);

            // Process the payment
            $payment = $this->paymentService->processPayment($booking, $amount);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully.',
                'payment' => $payment,
                'booking' => $booking->fresh()
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Find the payment
        $payment = Payment::with(['booking.user', 'booking.ticket.event'])->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'payment' => $payment
        ], 200);
    }
}
