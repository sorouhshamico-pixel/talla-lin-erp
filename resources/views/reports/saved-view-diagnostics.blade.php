@extends('layouts.app')

@section('content')
    @php
        $summary = $diagnosticReport['summary'] ?? [];
        $reportCount = $summary['report_count'] ?? 0;
        $invalidCount = $summary['invalid_count'] ?? 0;
        $isHealthy = (bool) ($summary['valid'] ?? false);
        $validReportKeys = $diagnosticReport['valid_report_keys'] ?? [];
        $rows = $diagnosticReport['rows'] ?? [];
        $diagnosticWebLinks = $diagnosticWebLinks ?? [];
        $diagnosticCommandExamples = $diagnosticCommandExamples ?? [];
    @endphp

    <div class="container py-4" data-testid="report-saved-view-diagnostics-page">
        <div class="mb-4">
            <h1 class="h3 mb-2">Report Saved View Diagnostics</h1>
            <p class="text-muted mb-0">
                تشخيص تقارير Saved Views المسجلة في النظام.
            </p>
        </div>

        @if (session('status'))
            <div class="alert alert-success" data-testid="report-saved-view-diagnostics-flash-status">
                {{ session('status') }}
            </div>
        @endif

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

        <div class="card mb-4" data-testid="report-saved-view-diagnostics-snapshot-actions">
            <div class="card-header">
                Snapshot Actions
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('reports.saved-view-diagnostics.snapshots.markdown') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        Write Markdown Snapshot
                    </button>
                </form>

                <form method="POST" action="{{ route('reports.saved-view-diagnostics.snapshots.json') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        Write JSON Snapshot
                    </button>
                </form>

                <form method="POST" action="{{ route('reports.saved-view-diagnostics.snapshots.prune') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        Prune Snapshots
                    </button>
                </form>

                <form method="POST" action="{{ route('reports.saved-view-diagnostics.snapshots.prune') }}">
                    @csrf
                    <input type="hidden" name="include_manifest" value="1">
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        Prune Snapshots And Manifest
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-diagnostics-web-links">
            <div class="card-header">
                Web Links
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    @foreach ($diagnosticWebLinks as $diagnosticWebLink)
                        <li>
                            <span class="fw-semibold">{{ $diagnosticWebLink['label'] }}</span>:
                            <code>{{ $diagnosticWebLink['route'] }}</code>
                        </li>
                    @endforeach
                </ul>
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

        <div class="card mb-4" data-testid="report-saved-view-diagnostics-cli-commands">
            <div class="card-header">
                CLI Commands
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    @foreach ($diagnosticCommandExamples as $diagnosticCommandExample)
                        <li><code>{{ $diagnosticCommandExample }}</code></li>
                    @endforeach
                </ul>
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
