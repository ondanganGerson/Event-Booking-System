<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Ticket;
use App\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class TicketController extends Controller
{
    /**
     * Store a newly created ticket for an event (organizer only).
     *
     * @param  Request  $request
     * @param  int  $eventId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $eventId)
    {
        $event = Event::find($eventId);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        // Check if user owns the event
        if ($event->created_by !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to create tickets for this event',
            ], 403);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create the ticket
        $ticket = Ticket::create([
            'type' => $request->type,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'event_id' => $eventId,
        ]);

        // Clear events cache
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Ticket created successfully',
            'data' => $ticket->load('event'),
        ], 201);
    }

    /**
     * Update the specified ticket (organizer only - own events).
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::with('event')->find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        // Check if user owns the event
        if ($ticket->event->created_by !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this ticket',
            ], 403);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update the ticket
        $ticket->update($request->only(['type', 'price', 'quantity']));

        // Clear events cache
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Ticket updated successfully',
            'data' => $ticket->load('event'),
        ], 200);
    }

    /**
     * Remove the specified ticket (organizer only - own events).
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $ticket = Ticket::with('event')->find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        // Check if user owns the event
        if ($ticket->event->created_by !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this ticket',
            ], 403);
        }

        // Check if ticket has any bookings
        if ($ticket->bookings()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete ticket with existing bookings',
            ], 400);
        }

        $ticket->delete();

        // Clear events cache
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Ticket deleted successfully',
        ], 200);
    }
}
