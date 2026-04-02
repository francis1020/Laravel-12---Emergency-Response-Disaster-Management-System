@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0"><i class="bi bi-graph-up me-2"></i>Analytics Dashboard</h2>
                    <p class="text-muted">Comprehensive system analytics and insights</p>
                </div>
                <div>
                    <select id="periodSelect" class="form-select" onchange="changePeriod(this.value)">
                        <option value="7" {{ $period == 7 ? 'selected' : '' }}>Last 7 days</option>
                        <option value="30" {{ $period == 30 ? 'selected' : '' }}>Last 30 days</option>
                        <option value="90" {{ $period == 90 ? 'selected' : '' }}>Last 90 days</option>
                        <option value="365" {{ $period == 365 ? 'selected' : '' }}>Last year</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Reports</h6>
                    <h3 class="mb-0">{{ $analytics['overview']['total_reports'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Pending</h6>
                    <h3 class="mb-0 text-warning">{{ $analytics['overview']['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">In Progress</h6>
                    <h3 class="mb-0 text-info">{{ $analytics['overview']['in_progress'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Resolved</h6>
                    <h3 class="mb-0 text-success">{{ $analytics['overview']['resolved'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Reports by Type -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Reports by Type</h5>
                </div>
                <div class="card-body">
                    @if(count($analytics['by_type']) > 0)
                        <canvas id="analyticsByTypeChart" style="max-height: 300px;"></canvas>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analytics['by_type'] as $item)
                                    <tr>
                                        <td>{{ ucfirst(str_replace('_', ' ', $item['type'])) }}</td>
                                        <td class="text-end"><strong>{{ $item['count'] }}</strong></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Reports by Severity -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Reports by Severity</h5>
                </div>
                <div class="card-body">
                    @if(count($analytics['by_severity']) > 0)
                        <canvas id="analyticsBySeverityChart" style="max-height: 300px;"></canvas>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Severity</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analytics['by_severity'] as $item)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $item['severity'] == 'critical' ? 'danger' : ($item['severity'] == 'high' ? 'warning' : ($item['severity'] == 'moderate' ? 'info' : 'success')) }}">
                                                {{ ucfirst($item['severity']) }}
                                            </span>
                                        </td>
                                        <td class="text-end"><strong>{{ $item['count'] }}</strong></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No data available</p>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Reports by Status -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-pie-chart-fill me-2"></i>Reports by Status</h5>
                </div>
                <div class="card-body">
                    @if(count($analytics['by_status']) > 0)
                        <canvas id="analyticsByStatusChart" style="max-height: 300px;"></canvas>
                    @else
                        <p class="text-muted mb-0">No data available</p>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Reports Trend Over Time -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Reports Trend</h5>
                </div>
                <div class="card-body">
                    @if(count($analytics['trends']) > 0)
                        <canvas id="analyticsTrendsChart" style="max-height: 300px;"></canvas>
                    @else
                        <p class="text-muted mb-0">No data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Response Time Statistics -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Response Time Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted">Average</small>
                            <h4>{{ number_format($analytics['response_times']['average'], 1) }} min</h4>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Median</small>
                            <h4>{{ number_format($analytics['response_times']['median'], 1) }} min</h4>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Min</small>
                            <h4>{{ number_format($analytics['response_times']['min'], 1) }} min</h4>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Max</small>
                            <h4>{{ number_format($analytics['response_times']['max'], 1) }} min</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resource Utilization -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Resource Utilization</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted">Total</small>
                            <h4>{{ $analytics['resource_utilization']['total'] }}</h4>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">In Use</small>
                            <h4 class="text-warning">{{ $analytics['resource_utilization']['in_use'] }}</h4>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Available</small>
                            <h4 class="text-success">{{ $analytics['resource_utilization']['available'] }}</h4>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Utilization Rate</small>
                            <h4>{{ number_format($analytics['resource_utilization']['utilization_rate'], 1) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Responders -->
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Top Responders</h5>
                </div>
                <div class="card-body">
                    @if(count($analytics['responder_performance']) > 0)
                        <canvas id="topRespondersChart" style="max-height: 400px;"></canvas>
                        <div class="table-responsive mt-3">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Responder</th>
                                        <th class="text-end">Assignments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analytics['responder_performance'] as $responder)
                                    <tr>
                                        <td>{{ $responder['responder_name'] }}</td>
                                        <td class="text-end"><strong>{{ $responder['assignments'] }}</strong></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0 text-center">No data available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function changePeriod(period) {
    window.location.href = '{{ route("analytics.index") }}?period=' + period;
}

document.addEventListener('DOMContentLoaded', function() {
    // Reports by Type - Pie Chart
    @if(count($analytics['by_type']) > 0)
    const analyticsByTypeCtx = document.getElementById('analyticsByTypeChart');
    if (analyticsByTypeCtx) {
        new Chart(analyticsByTypeCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    @foreach($analytics['by_type'] as $item)
                    '{{ ucfirst(str_replace('_', ' ', $item['type'])) }}',
                    @endforeach
                ],
                datasets: [{
                    data: [
                        @foreach($analytics['by_type'] as $item)
                        {{ $item['count'] }},
                        @endforeach
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(199, 199, 199, 0.8)',
                        'rgba(83, 102, 255, 0.8)',
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)',
                        'rgba(83, 102, 255, 1)',
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' reports';
                            }
                        }
                    }
                }
            }
        });
    }
    @endif

    // Reports by Severity - Bar Chart
    @if(count($analytics['by_severity']) > 0)
    const analyticsBySeverityCtx = document.getElementById('analyticsBySeverityChart');
    if (analyticsBySeverityCtx) {
        const severityColorMap = {
            'critical': { bg: 'rgba(220, 53, 69, 0.8)', border: 'rgba(220, 53, 69, 1)' },
            'high': { bg: 'rgba(255, 193, 7, 0.8)', border: 'rgba(255, 193, 7, 1)' },
            'moderate': { bg: 'rgba(13, 202, 240, 0.8)', border: 'rgba(13, 202, 240, 1)' },
            'low': { bg: 'rgba(25, 135, 84, 0.8)', border: 'rgba(25, 135, 84, 1)' }
        };
        
        new Chart(analyticsBySeverityCtx, {
            type: 'bar',
            data: {
                labels: [
                    @foreach($analytics['by_severity'] as $item)
                    '{{ ucfirst($item['severity']) }}',
                    @endforeach
                ],
                datasets: [{
                    label: 'Number of Reports',
                    data: [
                        @foreach($analytics['by_severity'] as $item)
                        {{ $item['count'] }},
                        @endforeach
                    ],
                    backgroundColor: [
                        @foreach($analytics['by_severity'] as $item)
                        severityColorMap['{{ $item['severity'] }}']?.bg || 'rgba(108, 117, 125, 0.8)',
                        @endforeach
                    ],
                    borderColor: [
                        @foreach($analytics['by_severity'] as $item)
                        severityColorMap['{{ $item['severity'] }}']?.border || 'rgba(108, 117, 125, 1)',
                        @endforeach
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    @endif

    // Reports by Status - Pie Chart
    @if(count($analytics['by_status']) > 0)
    const analyticsByStatusCtx = document.getElementById('analyticsByStatusChart');
    if (analyticsByStatusCtx) {
        new Chart(analyticsByStatusCtx, {
            type: 'pie',
            data: {
                labels: [
                    @foreach($analytics['by_status'] as $item)
                    '{{ ucfirst(str_replace('_', ' ', $item['status'])) }}',
                    @endforeach
                ],
                datasets: [{
                    data: [
                        @foreach($analytics['by_status'] as $item)
                        {{ $item['count'] }},
                        @endforeach
                    ],
                    backgroundColor: [
                        'rgba(108, 117, 125, 0.8)',
                        'rgba(13, 202, 240, 0.8)',
                        'rgba(0, 123, 255, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(25, 135, 84, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                    ],
                    borderColor: [
                        'rgba(108, 117, 125, 1)',
                        'rgba(13, 202, 240, 1)',
                        'rgba(0, 123, 255, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(220, 53, 69, 1)',
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' reports';
                            }
                        }
                    }
                }
            }
        });
    }
    @endif

    // Reports Trend - Line Chart
    @if(count($analytics['trends']) > 0)
    const analyticsTrendsCtx = document.getElementById('analyticsTrendsChart');
    if (analyticsTrendsCtx) {
        new Chart(analyticsTrendsCtx, {
            type: 'line',
            data: {
                labels: [
                    @foreach($analytics['trends'] as $trend)
                    '{{ \Carbon\Carbon::parse($trend['date'])->format('M d') }}',
                    @endforeach
                ],
                datasets: [{
                    label: 'Reports per Day',
                    data: [
                        @foreach($analytics['trends'] as $trend)
                        {{ $trend['count'] }},
                        @endforeach
                    ],
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    @endif

    // Top Responders - Bar Chart
    @if(count($analytics['responder_performance']) > 0)
    const topRespondersCtx = document.getElementById('topRespondersChart');
    if (topRespondersCtx) {
        new Chart(topRespondersCtx, {
            type: 'bar',
            data: {
                labels: [
                    @foreach($analytics['responder_performance'] as $responder)
                    '{{ $responder['responder_name'] }}',
                    @endforeach
                ],
                datasets: [{
                    label: 'Assignments',
                    data: [
                        @foreach($analytics['responder_performance'] as $responder)
                        {{ $responder['assignments'] }},
                        @endforeach
                    ],
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    @endif
});
</script>
@endsection

