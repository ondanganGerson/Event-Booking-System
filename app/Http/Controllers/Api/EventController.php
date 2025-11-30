<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    /**
     * Display a listing of events with caching, pagination, search, and filters.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $date = $request->get('date');
        $location = $request->get('location');

        $cacheKey = 'events_' . md5(json_encode($request->all()));

        $events = Cache::remember($cacheKey, 60, function () use ($search, $date, $location, $perPage) {
            $query = Event::with('creator', 'tickets');

            if ($search) {
                $query->searchByTitle($search);
            }

            if ($date) {
                $query->filterByDate($date);
            }

            if ($location) {
                $query->filterByLocation($location);
            }

            return $query->orderBy('date', 'asc')->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $events,
        ], 200);
    }

    /**
     * Display the specified event with tickets.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $event = Event::with('creator', 'tickets')->find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $event,
        ], 200);
    }

    /**
     * Store a newly created event (organizer only).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date|after:now',
            'location' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $event = Event::create([
            'title' => $request->title,
            'description' => $request->description,
            'date' => $request->date,
            'location' => $request->location,
            'created_by' => $request->user()->id,
        ]);

        // Clear events cache
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event->load('creator', 'tickets'),
        ], 201);
    }

    /**
     * Update the specified event (organizer only - own events).
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $event = Event::find($id);

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
                'message' => 'Unauthorized to update this event',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'date' => 'sometimes|required|date|after:now',
            'location' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $event->update($request->only(['title', 'description', 'date', 'location']));

        // Clear events cache
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event->load('creator', 'tickets'),
        ], 200);
    }

    /**
     * Remove the specified event (organizer only - own events).
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        $event = Event::find($id);

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
                'message' => 'Unauthorized to delete this event',
            ], 403);
        }

        $event->delete();

        // Clear events cache
        Cache::flush();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully',
        ], 200);
    }
}
