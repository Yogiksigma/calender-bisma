<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
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
            $user = auth()->user();
            
            // Admin bisa lihat semua event
            if ($user->isAdmin()) {
                $events = Event::with('users')->get();
            } else {
                // User biasa hanya lihat:
                // 1. Event publik (is_public = true)
                // 2. Event private yang di-assign ke mereka
                $events = Event::where(function($query) use ($user) {
                    $query->where('is_public', true)
                        ->orWhereHas('users', function($q) use ($user) {
                        $q->where('users.id', $user->id);
                        });
                })
                ->with('users')
                ->get();
            }
            
            $events = $events->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start,
                    'end' => $event->end,
                    'color' => $event->color ?? '#3788d8',
                    'description' => $event->description,
                    'is_public' => $event->is_public,
                    'assigned_users' => $event->users->pluck('name')->toArray(),
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
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthorized - Not logged in'], 401);
            }

            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'Unauthorized - Admin only'], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'start' => 'required|date',
                'end' => 'nullable|date',
                'color' => 'nullable|string',
                'description' => 'nullable|string',
                'is_public' => 'required|boolean',
                'user_ids' => 'nullable|array',
                'user_ids.*' => 'exists:users,id',
            ]);

            $event = Event::create([
                'title' => $validated['title'],
                'start' => $validated['start'],
                'end' => $validated['end'] ?? null,
                'color' => $validated['color'] ?? '#3788d8',
                'description' => $validated['description'] ?? null,
                'is_public' => $validated['is_public'],
            ]);

            // Jika event private, bisa tugas ke user tertentu
            if (!$validated['is_public'] && isset($validated['user_ids'])) {
                $event->users()->attach($validated['user_ids']);
            }

            Log::info('Event created: ' . $event->id);
            
            return response()->json([
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start,
                'end' => $event->end,
                'color' => $event->color,
                'description' => $event->description,
                'is_public' => $event->is_public,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating event: ' . $e->getMessage());
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
                'is_public' => 'sometimes|boolean',
                'user_ids' => 'nullable|array',
                'user_ids.*' => 'exists:users,id',
            ]);
            
            $event->update($validated);

            // Update event users jika ada
            if (isset($validated['is_public'])) {
                if ($validated['is_public']) {
                    // Jika diubah jadi public, hapus semua event
                    $event->users()->detach();
                } elseif (isset($validated['user_ids'])) {
                    // Jika private, sync user yang dipilih
                    $event->users()->sync($validated['user_ids']);
                }
            } elseif (isset($validated['user_ids'])) {
                // Update user event tanpa ubah is_public
                $event->users()->sync($validated['user_ids']);
            }
            
            return response()->json([
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start,
                'end' => $event->end,
                'color' => $event->color,
                'description' => $event->description,
                'is_public' => $event->is_public,
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

    public function getUsers()
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $users = User::where('role', 'user')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }
}