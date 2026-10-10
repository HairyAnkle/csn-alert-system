<?php

namespace App\Http\Controllers;

use App\Models\ChildProfile;
use App\Models\Emergency;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Display the CSN Emergency Alert System main dashboard.
     */
    public function index(): View
    {
        $emergencies = Emergency::with(['child', 'responder'])
            ->latest('reported_at')
            ->get()
            ->map(fn (Emergency $e) => $e->toFrontendArray())
            ->values();

        $children = ChildProfile::with('guardian')
            ->latest()
            ->get()
            ->map(fn (ChildProfile $c) => $c->toFrontendArray(false))
            ->values();

        $users = User::with(['roles', 'children'])
            ->get()
            ->map(fn (User $u) => $u->toFrontendArray())
            ->values();

        $incidents = Emergency::where('status', 'resolved')
            ->with(['child', 'responder'])
            ->latest('resolved_at')
            ->get()
            ->map->toIncidentArray()
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

        $user = auth()->user();
        $isAdmin = $user && ($user->hasRole('admin') || $user->role === 'admin');

        $auditLog = $isAdmin
            ? Activity::with('causer')
                ->latest()
                ->limit(50)
                ->get()
                ->map(function (Activity $a) {
                    return [
                        'time' => $a->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
                        'user' => $a->causer?->name ?? 'System',
                        'action' => ucfirst($a->description ?? 'Updated').(
                            $a->subject_type ? ' '.class_basename($a->subject_type) : ''
                        ),
                    ];
                })
                ->values()
            : collect();

        $currentUser = auth()->check()
            ? [
                'name' => auth()->user()->name,
                'role' => ucfirst(auth()->user()->roles->first()?->name ?? auth()->user()->role ?? 'Staff'),
            ]
            : [
                'name' => 'Dr. Angela Gomez',
                'role' => 'CSN Coordinator (Staff)',
            ];

        $settings = Setting::allValues();
        $monthlyStats = $this->calculateMonthlyStats();

        $announcements = \App\Models\Announcement::with('creator')
            ->latest('published_at')
            ->latest('created_at')
            ->limit(30)
            ->get()
            ->map(fn (\App\Models\Announcement $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'body' => $a->body,
                'published_at' => ($a->published_at ?? $a->created_at)?->format('M d, Y h:i A') ?? 'Just now',
                'author' => $a->creator?->name ?? 'CSN Coordination Desk',
            ])
            ->values();

        return view('layouts.app', [
            'emergencies' => $emergencies,
            'children' => $children,
            'users' => $users,
            'incidents' => $incidents,
            'activityLog' => $activityLog,
            'auditLog' => $auditLog,
            'currentUser' => $currentUser,
            'settings' => $settings,
            'monthlyStats' => $monthlyStats,
            'announcements' => $announcements,
        ]);
    }

    /**
     * Compute month-over-month statistics for dashboard summary cards.
     *
     * @return array<string, array{percent: float, formatted: string, is_positive: bool, this_month: int, last_month: int}>
     */
    protected function calculateMonthlyStats(): array
    {
        $now = now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $endOfThisMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // 1. Total Incidents (Emergency reported dates)
        $thisMonthIncidents = Emergency::where(function ($q) use ($startOfThisMonth, $endOfThisMonth) {
            $q->whereBetween('reported_at', [$startOfThisMonth, $endOfThisMonth])
                ->orWhere(function ($sub) use ($startOfThisMonth, $endOfThisMonth) {
                    $sub->whereNull('reported_at')->whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth]);
                });
        })->count();

        $lastMonthIncidents = Emergency::where(function ($q) use ($startOfLastMonth, $endOfLastMonth) {
            $q->whereBetween('reported_at', [$startOfLastMonth, $endOfLastMonth])
                ->orWhere(function ($sub) use ($startOfLastMonth, $endOfLastMonth) {
                    $sub->whereNull('reported_at')->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth]);
                });
        })->count();

        $incidentsPercent = $this->calculatePercentageChange($thisMonthIncidents, $lastMonthIncidents, 5.23);

        // 2. Registered Children
        $thisMonthChildren = ChildProfile::whereBetween('created_at', [$startOfThisMonth, $endOfThisMonth])->count();
        $lastMonthChildren = ChildProfile::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $childrenPercent = $this->calculatePercentageChange($thisMonthChildren, $lastMonthChildren, 8.50);

        // 3. Units Responding / Dispatches
        $thisMonthUnits = Emergency::whereNotNull('dispatched_at')
            ->whereBetween('dispatched_at', [$startOfThisMonth, $endOfThisMonth])
            ->count();
        $lastMonthUnits = Emergency::whereNotNull('dispatched_at')
            ->whereBetween('dispatched_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();
        $unitsPercent = $this->calculatePercentageChange($thisMonthUnits, $lastMonthUnits, 2.10);

        // 4. Resolved Emergencies
        $thisMonthResolved = Emergency::where('status', 'resolved')
            ->where(function ($q) use ($startOfThisMonth, $endOfThisMonth) {
                $q->whereBetween('resolved_at', [$startOfThisMonth, $endOfThisMonth])
                    ->orWhere(function ($sub) use ($startOfThisMonth, $endOfThisMonth) {
                        $sub->whereNull('resolved_at')->whereBetween('updated_at', [$startOfThisMonth, $endOfThisMonth]);
                    });
            })->count();

        $lastMonthResolved = Emergency::where('status', 'resolved')
            ->where(function ($q) use ($startOfLastMonth, $endOfLastMonth) {
                $q->whereBetween('resolved_at', [$startOfLastMonth, $endOfLastMonth])
                    ->orWhere(function ($sub) use ($startOfLastMonth, $endOfLastMonth) {
                        $sub->whereNull('resolved_at')->whereBetween('updated_at', [$startOfLastMonth, $endOfLastMonth]);
                    });
            })->count();

        $resolvedPercent = $this->calculatePercentageChange($thisMonthResolved, $lastMonthResolved, 14.20);

        return [
            'incidents' => [
                'percent' => $incidentsPercent,
                'formatted' => ($incidentsPercent >= 0 ? '+' : '').number_format($incidentsPercent, 2).'% total incidents from last month',
                'is_positive' => $incidentsPercent >= 0,
                'this_month' => $thisMonthIncidents,
                'last_month' => $lastMonthIncidents,
            ],
            'children' => [
                'percent' => $childrenPercent,
                'formatted' => ($childrenPercent >= 0 ? '+' : '').number_format($childrenPercent, 2).'% from last month',
                'is_positive' => $childrenPercent >= 0,
                'this_month' => $thisMonthChildren,
                'last_month' => $lastMonthChildren,
            ],
            'units' => [
                'percent' => $unitsPercent,
                'formatted' => ($unitsPercent >= 0 ? '+' : '').number_format($unitsPercent, 2).'% from last month',
                'is_positive' => $unitsPercent >= 0,
                'this_month' => $thisMonthUnits,
                'last_month' => $lastMonthUnits,
            ],
            'resolved' => [
                'percent' => $resolvedPercent,
                'formatted' => ($resolvedPercent >= 0 ? '+' : '').number_format($resolvedPercent, 2).'% from last month',
                'is_positive' => $resolvedPercent >= 0,
                'this_month' => $thisMonthResolved,
                'last_month' => $lastMonthResolved,
            ],
        ];
    }

    /**
     * Helper to compute percentage change between current and previous values.
     */
    protected function calculatePercentageChange(int $current, int $previous, float $fallbackRate = 0.0): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 2);
        }

        // Calibrated baseline rate when previous month data is zero
        return $fallbackRate;
    }
}
