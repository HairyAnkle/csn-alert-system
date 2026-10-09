<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSN Incident Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 20px;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #005B96;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #005B96;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
        }
        .header-subtitle {
            font-size: 11px;
            color: #64748b;
            margin: 0;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 16px;
            font-size: 10px;
        }
        .meta-table td {
            padding: 3px 0;
        }
        .meta-label {
            font-weight: bold;
            color: #475569;
            width: 120px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-align: left;
            padding: 8px 6px;
            border-bottom: 1px solid #cbd5e1;
            font-size: 10px;
            text-transform: uppercase;
        }
        table.data-table td {
            padding: 7px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 10px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-medical { background: #fee2e2; color: #991b1b; }
        .badge-seizure { background: #fef3c7; color: #92400e; }
        .badge-wandering { background: #e0f2fe; color: #075985; }
        .badge-fire { background: #ffedd5; color: #9a3412; }
        .badge-default { background: #f1f5f9; color: #475569; }
        .footer {
            margin-top: 25px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="header-title">City of Parañaque &bull; CSN Emergency Alert System</h1>
        <p class="header-subtitle">Official Incident Resolution & After-Action Report (AAR)</p>
    </div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Report Period:</td>
            <td>{{ $dateRangeText ?? 'All Recorded Incidents' }}</td>
            <td class="meta-label" style="text-align: right; width: 140px;">Total Resolved Incidents:</td>
            <td style="text-align: right; width: 50px; font-weight: bold;">{{ count($incidents) }}</td>
        </tr>
        <tr>
            <td class="meta-label">Generated On:</td>
            <td>{{ $generatedAt ?? now()->format('F d, Y h:i A') }}</td>
            <td class="meta-label" style="text-align: right;">Classification:</td>
            <td style="text-align: right; color: #005B96; font-weight: bold;">CONFIDENTIAL</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Incident ID</th>
                <th style="width: 16%;">Child</th>
                <th style="width: 14%;">Type</th>
                <th style="width: 12%;">Resolution Time</th>
                <th style="width: 16%;">Responder Unit</th>
                <th style="width: 14%;">Resolved Date</th>
                <th style="width: 16%;">Summary Logs</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incidents as $inc)
                <tr>
                    <td style="font-family: monospace; font-weight: bold; color: #005B96;">{{ $inc['id'] }}</td>
                    <td>
                        <strong>{{ $inc['child'] }}</strong><br>
                        <span style="color: #64748b; font-size: 9px;">{{ $inc['child_condition'] ?? '' }}</span>
                    </td>
                    <td>
                        <span class="badge badge-default">{{ $inc['type'] }}</span>
                    </td>
                    <td>{{ $inc['resolvedIn'] }}</td>
                    <td>{{ $inc['unit'] }}</td>
                    <td>{{ $inc['date'] }}</td>
                    <td>
                        <span style="color: #334155;">{{ $inc['incident_summary'] ?? 'No summary filed.' }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">
                        No incidents found matching the specified filter criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Confidential Document &bull; Parañaque CSN Emergency Dispatch &bull; Compliant with the Data Privacy Act of 2012 (RA 10173).
    </div>
</body>
</html>

