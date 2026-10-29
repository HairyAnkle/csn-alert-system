<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    /**
     * Authorize that only admin or staff can post announcements.
     */
    protected function authorizeAnnouncementManagement(?User $user): void
    {
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $role = strtolower($user->roles->first()?->name ?? $user->role ?? '');

        if (! in_array($role, ['superadmin', 'admin'], true)) {
            abort(403, 'Unauthorized to publish announcements.');
        }
    }

    /**
     * Store a newly created announcement and broadcast it to guardians.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeAnnouncementManagement($request->user());

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'published_at' => now(),
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'id' => $announcement->id,
            'title' => $announcement->title,
            'body' => $announcement->body,
            'published_at' => $announcement->published_at?->format('M d, Y h:i A') ?? 'Just now',
            'author' => $request->user()?->name ?? 'CSN Staff',
        ], 201);
    }
}
