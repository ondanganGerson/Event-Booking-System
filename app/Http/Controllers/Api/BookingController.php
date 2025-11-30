<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Booking;
use App\Ticket;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Display a listing of the authenticated user's bookings.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $bookings = Booking::with(['ticket.event.creator', 'payment'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ], 200);
    }

    /**
     * Store a newly created booking for a ticket (customer only).
     *
     * @param  Request  $request
     * @param  int  $ticketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $ticketId)
    {
        // Check if user is a customer
        if (!$request->user()->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Only customers can create bookings',
            ], 403);
        }

        $ticket = Ticket::with('event')->find($ticketId);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if event date is in the future
        if ($ticket->event->date <= now()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot book tickets for past events',
            ], 400);
        }

        // Check ticket availability
        $bookedQuantity = $ticket->bookings()
            ->where('status', '!=', 'cancelled')
            ->sum('quantity');

        $availableQuantity = $ticket->quantity - $bookedQuantity;

        if ($request->quantity > $availableQuantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough tickets available',
                'data' => [
                    'requested' => $request->quantity,
                    'available' => $availableQuantity,
                ],
            ], 400);
        }

        // Calculate total amount
        $totalAmount = $ticket->price * $request->quantity;

        // Create the booking within a transaction
        DB::beginTransaction();

        try {
            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'ticket_id' => $ticketId,
                'quantity' => $request->quantity,
                'status' => 'pending',
            ]);

            DB::commit();

            // Clear events cache
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => [
                    'booking' => $booking->load(['ticket.event', 'payment']),
                    'total_amount' => $totalAmount,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel the specified booking (customer only - own bookings).
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Request $request, $id)
    {
        $booking = Booking::with(['ticket.event', 'payment'])->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // Check if user owns the booking
        if ($booking->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to cancel this booking',
            ], 403);
        }

        // Check if booking is already cancelled
        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Booking is already cancelled',
            ], 400);
        }

        // Check if booking is already confirmed (with payment)
        if ($booking->status === 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel confirmed booking. Please contact support for refund.',
            ], 400);
        }

        // Check if event date has passed
        if ($booking->ticket->event->date <= now()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel booking for past events',
            ], 400);
        }

        // Cancel the booking
        $booking->update(['status' => 'cancelled']);

        // Clear events cache
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'data' => $booking,
        ], 200);
    }
}
