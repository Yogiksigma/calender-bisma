<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function index()
    {
        return view('calendar');
    }

    public function getEvents()
    {
        try {
            $events = Event::all()->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start,
                    'end' => $event->end,
                    'color' => $event->color ?? '#3788d8',
                    'description' => $event->description,
                ];
            });
            
            return response()->json($events);
        } catch (\Exception $e) {
            Log::error('Error fetching events: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Check if user is admin
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthorized - Not logged in'], 401);
            }

            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'Unauthorized - Admin only'], 403);
            }

            // Validate input
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'start' => 'required|date',
                'end' => 'nullable|date',
                'color' => 'nullable|string',
                'description' => 'nullable|string',
            ]);

            // Create event
            $event = Event::create([
                'title' => $validated['title'],
                'start' => $validated['start'],
                'end' => $validated['end'] ?? null,
                'color' => $validated['color'] ?? '#3788d8',
                'description' => $validated['description'] ?? null,
            ]);

            Log::info('Event created: ' . $event->id);
            
            return response()->json([
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start,
                'end' => $event->end,
                'color' => $event->color,
                'description' => $event->description,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating event: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to create event',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $event = Event::findOrFail($id);
            
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'start' => 'sometimes|date',
                'end' => 'nullable|date',
                'color' => 'nullable|string',
                'description' => 'nullable|string',
            ]);
            
            $event->update($validated);
            
            return response()->json([
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start,
                'end' => $event->end,
                'color' => $event->color,
                'description' => $event->description,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating event: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $event = Event::findOrFail($id);
            $event->delete();
            
            return response()->json(['message' => 'Event deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting event: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}