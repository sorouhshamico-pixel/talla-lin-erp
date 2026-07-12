@extends('layouts.app')

@section('content')
    @php
        $summary = $diagnosticReport['summary'] ?? [];
        $reportCount = $summary['report_count'] ?? 0;
        $invalidCount = $summary['invalid_count'] ?? 0;
        $isHealthy = (bool) ($summary['valid'] ?? false);
        $validReportKeys = $diagnosticReport['valid_report_keys'] ?? [];
        $rows = $diagnosticReport['rows'] ?? [];
    @endphp

    <div class="container py-4" data-testid="report-saved-view-diagnostics-page">
        <div class="mb-4">
            <h1 class="h3 mb-2">Report Saved View Diagnostics</h1>
            <p class="text-muted mb-0">
                تشخيص تقارير Saved Views المسجلة في النظام.
            </p>
        </div>

        <div class="row g-3 mb-4" data-testid="report-saved-view-diagnostics-summary">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Report Count</div>
                        <div class="fs-4 fw-bold">{{ $reportCount }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Invalid Count</div>
                        <div class="fs-4 fw-bold">{{ $invalidCount }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Registry Health</div>
                        <div class="fs-4 fw-bold">
                            {{ $isHealthy ? 'Healthy' : 'Needs Review' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-diagnostics-export-actions">
            <div class="card-header">
                Export
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-diagnostics.markdown') }}">
                    View Markdown
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-diagnostics.json') }}">
                    View JSON
                </a>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-diagnostics-valid-keys">
            <div class="card-header">
                Valid Report Keys
            </div>
            <div class="card-body">
                @forelse ($validReportKeys as $validReportKey)
                    <span class="badge bg-success me-1">{{ $validReportKey }}</span>
                @empty
                    <span class="text-muted">No valid report keys.</span>
                @endforelse
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-diagnostics-table">
            <div class="card-header">
                Diagnostic Rows
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Label</th>
                            <th>Status</th>
                            <th>Errors</th>
                            <th>View Path</th>
                            <th>Config Partial</th>
                            <th>Hidden Fields</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $rowKey = $row['key'] ?? '';
                                $rowLabel = $row['label'] ?? '';
                                $rowValid = (bool) ($row['valid'] ?? false);
                                $rowErrorCount = $row['error_count'] ?? 0;
                                $rowViewPath = $row['view_path'] ?? '';
                                $rowConfigPartialPath = $row['config_partial_path'] ?? '';
                                $rowHiddenFields = implode(', ', $row['hidden_fields'] ?? []);
                            @endphp

                            <tr data-testid="report-saved-view-diagnostics-row-{{ $rowKey }}">
                                <td>{{ $rowKey }}</td>
                                <td>{{ $rowLabel }}</td>
                                <td>
                                    @if ($rowValid)
                                        <span class="badge bg-success">Healthy</span>
                                    @else
                                        <span class="badge bg-danger">Invalid</span>
                                    @endif
                                </td>
                                <td>{{ $rowErrorCount }}</td>
                                <td><code>{{ $rowViewPath }}</code></td>
                                <td><code>{{ $rowConfigPartialPath }}</code></td>
                                <td>{{ $rowHiddenFields }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" data-testid="report-saved-view-diagnostics-markdown">
            <div class="card-header">
                Markdown Snapshot
            </div>
            <div class="card-body">
                <pre class="mb-0"><code>{{ $diagnosticMarkdown }}</code></pre>
            </div>
        </div>
    </div>
@endsection
