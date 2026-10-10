<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementApiController extends Controller
{
    /**
     * List announcements and broadcasts from CSN Center (Section 7.3).
     */
    public function index(Request $request): JsonResponse
    {
        $announcements = Announcement::where(function ($q) {
            $q->whereNotNull('published_at')->where('published_at', '<=', now())
                ->orWhereNull('published_at');
        })
            ->latest('published_at')
            ->latest('created_at')
            ->limit(50)
            ->get()
            ->map(fn (Announcement $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'created_at' => ($n->published_at ?? $n->created_at)?->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'announcements' => $announcements,
        ]);
    }
}
