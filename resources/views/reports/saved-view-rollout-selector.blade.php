@extends('layouts.app')

@section('content')
    @php
        $rolloutPlan = $rolloutPlan ?? [];
        $rolloutMarkdown = $rolloutMarkdown ?? '';
        $rolloutWebLinks = $rolloutWebLinks ?? [];
        $rolloutCommandExamples = $rolloutCommandExamples ?? [];
        $nextCandidate = $rolloutPlan['next_candidate'] ?? null;
        $prioritizedCandidates = $rolloutPlan['prioritized_candidates'] ?? [];
        $recommendedSteps = $rolloutPlan['recommended_steps'] ?? [];
    @endphp

    <div class="container py-4" data-testid="report-saved-view-rollout-selector-page">
        <div class="mb-4">
            <h1 class="h3 mb-2">Report Saved View Rollout Selector</h1>
            <p class="text-muted mb-0">
                اختيار التقرير التالي المرشح لتفعيل Saved Views بناءً على أولوية الفلاتر ودرجة الجاهزية.
            </p>
        </div>

        <div class="row g-3 mb-4" data-testid="report-saved-view-rollout-selector-summary">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Candidate Count</div>
                        <div class="fs-4 fw-bold">{{ $rolloutPlan['candidate_count'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Unregistered Candidate Count</div>
                        <div class="fs-4 fw-bold">{{ $rolloutPlan['unregistered_candidate_count'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Has Next Candidate</div>
                        <div class="fs-4 fw-bold">{{ ($rolloutPlan['has_next_candidate'] ?? false) ? 'yes' : 'no' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-selector-web-links">
            <div class="card-header">
                Web Links
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    @foreach ($rolloutWebLinks as $rolloutWebLink)
                        <li>
                            <span class="fw-semibold">{{ $rolloutWebLink['label'] }}</span>:
                            <code>{{ $rolloutWebLink['route'] }}</code>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-selector-cli-commands">
            <div class="card-header">
                CLI Commands
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    @foreach ($rolloutCommandExamples as $rolloutCommandExample)
                        <li><code>{{ $rolloutCommandExample }}</code></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-selector-next-candidate">
            <div class="card-header">
                Next Candidate
            </div>
            <div class="card-body">
                @if ($nextCandidate)
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Key</dt>
                        <dd class="col-sm-9">{{ $nextCandidate['key'] }}</dd>

                        <dt class="col-sm-3">View Path</dt>
                        <dd class="col-sm-9"><code>{{ $nextCandidate['view_path'] }}</code></dd>

                        <dt class="col-sm-3">Priority Score</dt>
                        <dd class="col-sm-9">{{ $nextCandidate['priority_score'] }}</dd>
                    </dl>
                @else
                    <p class="text-muted mb-0">No unregistered candidate found.</p>
                @endif
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-selector-recommended-steps">
            <div class="card-header">
                Recommended Steps
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    @foreach ($recommendedSteps as $recommendedStep)
                        <li>{{ $recommendedStep }}</li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-selector-export-actions">
            <div class="card-header">
                Export
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-rollout-selector.markdown') }}">
                    View Markdown
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-rollout-selector.json') }}">
                    View JSON
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-candidates.index') }}">
                    Candidates
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-diagnostics.index') }}">
                    Diagnostics
                </a>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-selector-table">
            <div class="card-header">
                Prioritized Candidates
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Priority Score</th>
                            <th>GET Form</th>
                            <th>Filters</th>
                            <th>View Path</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($prioritizedCandidates as $candidate)
                            <tr>
                                <td>{{ $candidate['key'] }}</td>
                                <td>{{ $candidate['priority_score'] }}</td>
                                <td>{{ $candidate['has_get_form'] ? 'yes' : 'no' }}</td>
                                <td>{{ $candidate['has_filter_terms'] ? 'yes' : 'no' }}</td>
                                <td><code>{{ $candidate['view_path'] }}</code></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" data-testid="report-saved-view-rollout-selector-markdown">
            <div class="card-header">
                Markdown Snapshot
            </div>
            <div class="card-body">
                <pre class="mb-0"><code>{{ $rolloutMarkdown }}</code></pre>
            </div>
        </div>
    </div>
@endsection
