<?php

namespace App\Http\Middleware;

use Closure;
use App\Booking;

class PreventDoubleBooking
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Get ticket ID from route parameter
        $ticketId = $request->route('id');

        if (!$ticketId) {
            // If no ticket ID in route, proceed (not a booking request)
            return $next($request);
        }

        // Check if user already has an active booking for this ticket
        $existingBooking = Booking::where('user_id', $request->user()->id)
            ->where('ticket_id', $ticketId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($existingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active booking for this ticket.',
                'existing_booking' => [
                    'id' => $existingBooking->id,
                    'status' => $existingBooking->status,
                    'quantity' => $existingBooking->quantity,
                    'created_at' => $existingBooking->created_at
                ]
            ], 400);
        }

        return $next($request);
    }
}
