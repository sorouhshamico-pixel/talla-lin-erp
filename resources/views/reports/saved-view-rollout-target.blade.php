@extends('layouts.app')

@section('content')
    @php
        $rolloutTargetSummary = $rolloutTargetSummary ?? [];
        $rolloutTargetMarkdown = $rolloutTargetMarkdown ?? '';
        $candidateFilterFields = $rolloutTargetSummary['candidate_filter_fields'] ?? [];
        $routeNames = $rolloutTargetSummary['route_names'] ?? [];
        $includeNames = $rolloutTargetSummary['include_names'] ?? [];
    @endphp

    <div class="container py-4" data-testid="report-saved-view-rollout-target-page">
        <div class="mb-4">
            <h1 class="h3 mb-2">Report Saved View Rollout Target</h1>
            <p class="text-muted mb-0">
                التقرير المقفل حاليًا لتنفيذ Saved View rollout.
            </p>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-target-summary">
            <div class="card-header">
                Locked Target
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Key</dt>
                    <dd class="col-sm-9">{{ $rolloutTargetSummary['key'] ?? 'none' }}</dd>

                    <dt class="col-sm-3">View Path</dt>
                    <dd class="col-sm-9"><code>{{ $rolloutTargetSummary['view_path'] ?? 'none' }}</code></dd>

                    <dt class="col-sm-3">Priority Score</dt>
                    <dd class="col-sm-9">{{ $rolloutTargetSummary['priority_score'] ?? 'none' }}</dd>

                    <dt class="col-sm-3">View Exists</dt>
                    <dd class="col-sm-9">{{ ($rolloutTargetSummary['view_exists'] ?? false) ? 'yes' : 'no' }}</dd>

                    <dt class="col-sm-3">Config Partial</dt>
                    <dd class="col-sm-9"><code>{{ $rolloutTargetSummary['recommended_config_partial'] ?? 'none' }}</code></dd>

                    <dt class="col-sm-3">Config Partial Path</dt>
                    <dd class="col-sm-9"><code>{{ $rolloutTargetSummary['recommended_config_partial_path'] ?? 'none' }}</code></dd>
                </dl>
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-target-filter-fields">
            <div class="card-header">
                Candidate Filter Fields
            </div>
            <div class="card-body">
                @if ($candidateFilterFields)
                    <ul class="mb-0">
                        @foreach ($candidateFilterFields as $candidateFilterField)
                            <li><code>{{ $candidateFilterField }}</code></li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No filter fields detected.</p>
                @endif
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-target-routes">
            <div class="card-header">
                Route Names
            </div>
            <div class="card-body">
                @if ($routeNames)
                    <ul class="mb-0">
                        @foreach ($routeNames as $routeName)
                            <li><code>{{ $routeName }}</code></li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No route names detected.</p>
                @endif
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-target-includes">
            <div class="card-header">
                Includes
            </div>
            <div class="card-body">
                @if ($includeNames)
                    <ul class="mb-0">
                        @foreach ($includeNames as $includeName)
                            <li><code>{{ $includeName }}</code></li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No includes detected.</p>
                @endif
            </div>
        </div>

        <div class="card mb-4" data-testid="report-saved-view-rollout-target-export-actions">
            <div class="card-header">
                Export
            </div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-rollout-target.markdown') }}">
                    View Markdown
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-rollout-target.json') }}">
                    View JSON
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('reports.saved-view-rollout-selector.index') }}">
                    Rollout Selector
                </a>
            </div>
        </div>

        <div class="card" data-testid="report-saved-view-rollout-target-markdown">
            <div class="card-header">
                Markdown Snapshot
            </div>
            <div class="card-body">
                <pre class="mb-0"><code>{{ $rolloutTargetMarkdown }}</code></pre>
            </div>
        </div>
    </div>
@endsection
