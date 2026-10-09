<?php

namespace App\Http\Controllers;

use App\Models\Emergency;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentReportController extends Controller
{
    /**
     * Authorize that the current user has report access.
     */
    protected function authorizeReportAccess(?User $user): void
    {
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $role = strtolower($user->roles->first()?->name ?? $user->role ?? '');

        if (! in_array($role, ['admin', 'staff', 'responder'], true)) {
            abort(403, 'Unauthorized. Incident reporting is restricted to authorized personnel.');
        }
    }

    /**
     * Build the filtered query for resolved incidents.
     *
     * @return Builder<Emergency>
     */
    protected function buildFilteredQuery(Request $request): Builder
    {
        $query = Emergency::where('status', 'resolved')
            ->with(['child', 'responder'])
            ->latest('resolved_at');

        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->query('start_date'))->startOfDay();
            $query->where('resolved_at', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->query('end_date'))->endOfDay();
            $query->where('resolved_at', '<=', $endDate);
        }

        if ($request->filled('type') && $request->query('type') !== 'all') {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->query('q'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('child', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('incident_summary', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Display a listing of resolved incident reports (JSON).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeReportAccess($request->user());

        $incidents = $this->buildFilteredQuery($request)
            ->get()
            ->map->toIncidentArray()
            ->values();

        return response()->json($incidents);
    }

    /**
     * Update post-incident summary notes for a resolved emergency.
     */
    public function updateSummary(Request $request, Emergency $emergency): JsonResponse
    {
        $this->authorizeReportAccess($request->user());

        $validated = $request->validate([
            'incident_summary' => ['required', 'string', 'max:5000'],
        ]);

        $emergency->update([
            'incident_summary' => $validated['incident_summary'],
        ]);

        $emergency->load(['child', 'responder']);

        return response()->json([
            'message' => 'Incident summary updated successfully.',
            'incident' => $emergency->toIncidentArray(),
        ]);
    }

    /**
     * Export filtered incident reports as a formatted PDF.
     */
    public function exportPdf(Request $request): Response
    {
        $this->authorizeReportAccess($request->user());

        $incidents = $this->buildFilteredQuery($request)
            ->get()
            ->map->toIncidentArray()
            ->values();

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $dateRangeText = 'All Recorded Incidents';
        if ($startDate && $endDate) {
            $dateRangeText = Carbon::parse($startDate)->format('M d, Y').' to '.Carbon::parse($endDate)->format('M d, Y');
        } elseif ($startDate) {
            $dateRangeText = 'From '.Carbon::parse($startDate)->format('M d, Y');
        } elseif ($endDate) {
            $dateRangeText = 'Up to '.Carbon::parse($endDate)->format('M d, Y');
        }

        $pdf = Pdf::loadView('reports.incidents-pdf', [
            'incidents' => $incidents,
            'dateRangeText' => $dateRangeText,
            'generatedAt' => now()->format('F d, Y h:i A'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('incident-report-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Export filtered incident reports as a CSV stream.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $this->authorizeReportAccess($request->user());

        $incidents = $this->buildFilteredQuery($request)
            ->get()
            ->map->toIncidentArray()
            ->values();

        $csv = Writer::fromString('');

        $csv->insertOne([
            'Incident ID',
            'Child Name',
            'Child Condition',
            'Emergency Type',
            'Urgency',
            'Location',
            'Resolution Time',
            'Responder Unit',
            'Resolved Date & Time',
            'Summary Logs',
        ]);

        foreach ($incidents as $inc) {
            $csv->insertOne([
                $inc['id'],
                $inc['child'],
                $inc['child_condition'] ?? '—',
                $inc['type'],
                ucfirst($inc['urgency'] ?? 'High'),
                $inc['location'] ?? '—',
                $inc['resolvedIn'],
                $inc['unit'],
                $inc['date'],
                $inc['raw_summary'] ?? '',
            ]);
        }

        $filename = 'incident-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($csv) {
            echo $csv->toString();
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
