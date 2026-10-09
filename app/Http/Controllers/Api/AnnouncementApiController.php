<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementApiController extends Controller
{
    /**
     * List announcements for guardians.
     */
    public function index(Request $request): JsonResponse
    {
        $announcements = Announcement::latest()
            ->get()
            ->map(fn (Announcement $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'body' => $a->body,
                'created_at' => $a->created_at?->toISOString(),
            ])
            ->values();

        return response()->json([
            'announcements' => $announcements,
        ]);
    }
}

