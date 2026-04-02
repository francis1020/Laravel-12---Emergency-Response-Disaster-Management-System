<!DOCTYPE html>
<html>
<head>
    <title>Emergency Reports Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .badge-success { background-color: #28a745; color: white; }
        .badge-warning { background-color: #ffc107; color: black; }
        .badge-danger { background-color: #dc3545; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }
        .badge-secondary { background-color: #6c757d; color: white; }
    </style>
</head>
<body>
    <h1>Emergency Reports Export</h1>
    <p><strong>Generated:</strong> {{ now()->format('F d, Y g:i:s A') }}</p>
    <p><strong>User:</strong> {{ $user->name }} ({{ $user->email }})</p>
    <p><strong>Total Reports:</strong> {{ $reports->count() }}</p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Description</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Reported At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $report)
            <tr>
                <td>#{{ $report->id }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $report->type)) }}</td>
                <td>{{ ucfirst($report->severity_level) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $report->status)) }}</td>
                <td>{{ Str::limit($report->description ?? 'N/A', 50) }}</td>
                <td>{{ $report->contact_name ?? 'N/A' }}<br>{{ $report->contact_number ?? 'N/A' }}</td>
                <td>{{ Str::limit($report->address ?? 'N/A', 40) }}</td>
                <td>{{ $report->created_at->format('M d, Y g:i A') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

