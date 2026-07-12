@extends('layouts.app')

@section('content')
    @php
        $candidateSummary = $candidateSummary ?? [];
        $candidates = $candidates ?? [];
        $candidateMarkdown = $candidateMarkdown ?? '';

        $candidateCount = $candidateSummary['candidate_count'] ?? 0;
        $registeredCount = $candidateSummary['registered_count'] ?? 0;
        $unregisteredCount = $candidateSummary['unregistered_count'] ?? 0;
    @endphp

    <div class="container py-4" data-testid="report-saved-view-candidates-page">
        <div class="mb-4">
            <h1 class="h3 mb-2">Report Saved View Candidates</h1>
            <p class="text-muted mb-0">
                قائمة التقارير المرشحة لتفعيل Saved Views بناءً على ملفات Blade والفلاتر الموجودة.
            </p>
        </div>

        <div class="row g-3 mb-4" data-testid="report-saved-view-candidates-summary">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Candidate Count</div>
                        <div class="fs-4 fw-bold">{{ $candidateCount }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Registered Count</div>
                        <div class="fs-4 fw-bold">{{ $registeredCount }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Unregistered Count</div>
                        <div class="fs-4 fw-bold">{{ $unregisteredCount }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-candidates-export-actions">
            <div class="card-header">
                Export
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-candidates.markdown') }}">
                    View Markdown
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-candidates.json') }}">
                    View JSON
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-diagnostics.index') }}">
                    Diagnostics
                </a>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-candidates-table">
            <div class="card-header">
                Candidate Rows
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Registered</th>
                            <th>GET Form</th>
                            <th>Filters</th>
                            <th>Saved View Controls</th>
                            <th>Priority Score</th>
                            <th>View Path</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($candidates as $candidate)
                            <tr data-testid="report-saved-view-candidate-row-{{ $candidate['key'] }}">
                                <td>{{ $candidate['key'] }}</td>
                                <td>{{ $candidate['registered'] ? 'yes' : 'no' }}</td>
                                <td>{{ $candidate['has_get_form'] ? 'yes' : 'no' }}</td>
                                <td>{{ $candidate['has_filter_terms'] ? 'yes' : 'no' }}</td>
                                <td>{{ $candidate['has_saved_view_controls'] ? 'yes' : 'no' }}</td>
                                <td>{{ $candidate['priority_score'] }}</td>
                                <td><code>{{ $candidate['view_path'] }}</code></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" data-testid="report-saved-view-candidates-markdown">
            <div class="card-header">
                Markdown Snapshot
            </div>
            <div class="card-body">
                <pre class="mb-0"><code>{{ $candidateMarkdown }}</code></pre>
            </div>
        </div>
    </div>
@endsection
