<?php

namespace App\Http\Controllers;

use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Display the CSN Emergency Alert System main dashboard.
     */
    public function index(): View
    {
        $emergencies = Emergency::with('child')
            ->latest('reported_at')
            ->get()
            ->map(fn (Emergency $e) => $e->toFrontendArray())
            ->values();

        $children = ChildProfile::with('guardian')
            ->latest()
            ->get()
            ->map(fn (ChildProfile $c) => $c->toFrontendArray())
            ->values();

        $users = User::all()
            ->map(fn (User $u) => $u->toFrontendArray())
            ->values();

        $incidents = Emergency::where('status', 'resolved')
            ->with('child')
            ->latest('resolved_at')
            ->get()
            ->map(function (Emergency $e) {
                $duration = ($e->reported_at && $e->resolved_at)
                    ? $e->reported_at->diff($e->resolved_at)->format('%h hr %i min')
                    : '24 mins';

                return [
                    'id' => 'INC-'.str_pad((string) $e->id, 4, '0', STR_PAD_LEFT),
                    'child' => $e->child?->name ?? 'Unknown',
                    'type' => $e->type,
                    'resolvedIn' => $duration,
                    'unit' => 'PDRRMO Unit 4',
                    'date' => $e->resolved_at?->format('M d, Y h:i A') ?? $e->updated_at->format('M d, Y h:i A'),
                ];
            })
            ->values();

        $activityLog = Emergency::with('child')
            ->latest('updated_at')
            ->take(6)
            ->get()
            ->map(function (Emergency $e) {
                $childName = $e->child?->name ?? 'Child';
                $tone = match ($e->status) {
                    'pending' => 'critical',
                    'responding' => 'warning',
                    'resolved' => 'success',
                    default => 'warning',
                };

                $text = match ($e->status) {
                    'pending' => "Emergency alert reported for {$childName} ({$e->type}) at {$e->location}.",
                    'responding' => "Responder dispatched to {$e->location} for {$childName}.",
                    'resolved' => "Emergency resolved for {$childName}. Situation stabilized.",
                    default => "Alert updated for {$childName}.",
                };

                return [
                    'tone' => $tone,
                    'text' => $text,
                    'time' => $e->updated_at->diffForHumans(),
                ];
            })
            ->values();

        $currentUser = auth()->check()
            ? [
                'name' => auth()->user()->name,
                'role' => ucfirst(auth()->user()->role ?? 'Staff'),
            ]
            : [
                'name' => 'Dr. Angela Gomez',
                'role' => 'CSN Coordinator (Staff)',
            ];

        $settings = [
            'sound_on_high' => true,
            'sound_on_low' => true,
            'hotline_pdrrmo' => '(02) 8820-7377',
            'hotline_ambulance' => '911 / (02) 8825-1022',
        ];

        return view('layouts.app', [
            'emergencies' => $emergencies,
            'children' => $children,
            'users' => $users,
            'incidents' => $incidents,
            'activityLog' => $activityLog,
            'currentUser' => $currentUser,
            'settings' => $settings,
        ]);
    }
}
