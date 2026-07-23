@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Saved View Sharing Activity Retention</h1>

    <dl>
        <dt>Enabled</dt>
        <dd>{{ $status['retention_enabled'] ? 'Yes' : 'No' }}</dd>
        <dt>Retention days</dt>
        <dd>{{ $status['retention_days'] ?? 'Retain forever' }}</dd>
        <dt>Chunk size</dt>
        <dd>{{ $status['chunk_size'] }}</dd>
        <dt>Schedule</dt>
        <dd>{{ $status['schedule'] }}</dd>
        <dt>Candidate count</dt>
        <dd>{{ $status['candidate_count'] ?? 'Not configured' }}</dd>
        <dt>Oldest activity</dt>
        <dd>{{ $status['oldest_activity_at'] ?? 'None' }}</dd>
        <dt>Newest activity</dt>
        <dd>{{ $status['newest_activity_at'] ?? 'None' }}</dd>
    </dl>

    <p>Retention configuration is read-only and deployment-managed.</p>

    <section aria-labelledby="retention-history-export-heading">
        <h2 id="retention-history-export-heading">
            Retention execution history export
        </h2>

        <p>
            Export operational execution history as CSV or JSON.
            CSV is limited to 100000 rows and JSON is limited to 10000 rows.
        </p>

        @php
            $exportPresets = [
                'all' => [
                    'label' => 'All executions',
                    'filters' => [],
                ],
                'failed' => [
                    'label' => 'Failed executions',
                    'filters' => ['status' => 'failed'],
                ],
                'conflicted' => [
                    'label' => 'Conflicted executions',
                    'filters' => ['status' => 'conflicted'],
                ],
                'manual' => [
                    'label' => 'Manual executions',
                    'filters' => ['type' => 'manual_execution'],
                ],
                'scheduled' => [
                    'label' => 'Scheduled executions',
                    'filters' => ['type' => 'scheduled_execution'],
                ],
                'command' => [
                    'label' => 'Command executions',
                    'filters' => ['type' => 'command_execution'],
                ],
            ];

            $activePreset = request('preset', 'all');
        @endphp

        <nav aria-label="Retention execution history export presets">
            <h3>Presets</h3>

            <ul>
                @foreach ($exportPresets as $presetKey => $preset)
                    <li>
                        <a
                            href="{{ route(
                                'reports.saved-view-share-activity-retention.index',
                                array_merge(
                                    ['preset' => $presetKey],
                                    $preset['filters']
                                )
                            ) }}"
                            @if ($activePreset === $presetKey)
                                aria-current="page"
                            @endif
                        >
                            {{ $preset['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @php
            $utcNow = \Illuminate\Support\Carbon::now('UTC');

            $preservedDateShortcutFilters = array_filter([
                'preset' => request('preset'),
                'type' => request('type'),
                'status' => request('status'),
                'actor_user_id' => request('actor_user_id'),
            ], static fn ($value) => $value !== null && $value !== '');

            $dateRangeShortcuts = [
                'today' => [
                    'label' => 'Today',
                    'started_from' => $utcNow
                        ->copy()
                        ->startOfDay()
                        ->format('Y-m-d\TH:i'),
                    'started_to' => $utcNow
                        ->copy()
                        ->endOfDay()
                        ->format('Y-m-d\TH:i'),
                ],
                'last_7_days' => [
                    'label' => 'Last 7 days',
                    'started_from' => $utcNow
                        ->copy()
                        ->subDays(7)
                        ->format('Y-m-d\TH:i'),
                    'started_to' => $utcNow
                        ->copy()
                        ->format('Y-m-d\TH:i'),
                ],
                'last_30_days' => [
                    'label' => 'Last 30 days',
                    'started_from' => $utcNow
                        ->copy()
                        ->subDays(30)
                        ->format('Y-m-d\TH:i'),
                    'started_to' => $utcNow
                        ->copy()
                        ->format('Y-m-d\TH:i'),
                ],
                'this_month' => [
                    'label' => 'This month',
                    'started_from' => $utcNow
                        ->copy()
                        ->startOfMonth()
                        ->format('Y-m-d\TH:i'),
                    'started_to' => $utcNow
                        ->copy()
                        ->endOfMonth()
                        ->format('Y-m-d\TH:i'),
                ],
                'previous_month' => [
                    'label' => 'Previous month',
                    'started_from' => $utcNow
                        ->copy()
                        ->subMonthNoOverflow()
                        ->startOfMonth()
                        ->format('Y-m-d\TH:i'),
                    'started_to' => $utcNow
                        ->copy()
                        ->subMonthNoOverflow()
                        ->endOfMonth()
                        ->format('Y-m-d\TH:i'),
                ],
                'clear_dates' => [
                    'label' => 'Clear date range',
                    'started_from' => null,
                    'started_to' => null,
                ],
            ];

            $activeDateShortcut = request('date_shortcut');
        @endphp

        <nav aria-label="Retention execution history export date range shortcuts">
            <h3>Date range shortcuts</h3>

            <ul>
                @foreach ($dateRangeShortcuts as $shortcutKey => $shortcut)
                    @php
                        $shortcutParameters = array_merge(
                            $preservedDateShortcutFilters,
                            ['date_shortcut' => $shortcutKey]
                        );

                        if ($shortcut['started_from'] !== null) {
                            $shortcutParameters['started_from'] =
                                $shortcut['started_from'];
                        }

                        if ($shortcut['started_to'] !== null) {
                            $shortcutParameters['started_to'] =
                                $shortcut['started_to'];
                        }
                    @endphp

                    <li>
                        <a
                            href="{{ route(
                                'reports.saved-view-share-activity-retention.index',
                                $shortcutParameters
                            ) }}"
                            @if ($activeDateShortcut === $shortcutKey)
                                aria-current="page"
                            @endif
                        >
                            {{ $shortcut['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <form
            method="GET"
            action="{{ route('reports.saved-view-share-activity-retention.history.export.csv') }}"
        >
            <div>
                <label for="retention-export-type">Type</label>
                <select id="retention-export-type" name="type">
                    <option value="">All types</option>
                    @foreach ([
                        'manual_preview',
                        'manual_execution',
                        'scheduled_execution',
                        'command_execution',
                    ] as $type)
                        <option
                            value="{{ $type }}"
                            @selected(request('type') === $type)
                        >
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="retention-export-status">Status</label>
                <select id="retention-export-status" name="status">
                    <option value="">All statuses</option>
                    @foreach (['succeeded', 'failed', 'conflicted'] as $statusValue)
                        <option
                            value="{{ $statusValue }}"
                            @selected(request('status') === $statusValue)
                        >
                            {{ $statusValue }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="retention-export-actor-user-id">
                    Actor user ID
                </label>
                <input
                    id="retention-export-actor-user-id"
                    name="actor_user_id"
                    type="number"
                    min="1"
                    value="{{ request('actor_user_id') }}"
                >
            </div>

            <div>
                <label for="retention-export-started-from">
                    Started from
                </label>
                <input
                    id="retention-export-started-from"
                    name="started_from"
                    type="datetime-local"
                    value="{{ request('started_from') }}"
                >
            </div>

            <div>
                <label for="retention-export-started-to">
                    Started to
                </label>
                <input
                    id="retention-export-started-to"
                    name="started_to"
                    type="datetime-local"
                    value="{{ request('started_to') }}"
                >
            </div>

            <div>
                <button type="submit">
                    Export CSV
                </button>

                <button
                    type="submit"
                    formaction="{{ route('reports.saved-view-share-activity-retention.history.export.json') }}"
                >
                    Export JSON
                </button>

                <a href="{{ route('reports.saved-view-share-activity-retention.index') }}">
                    Clear filters
                </a>
            </div>
        </form>

        <section aria-labelledby="retention-history-summary-heading">
            <h3 id="retention-history-summary-heading">
                Current export summary
            </h3>

            @if ($exportSummary['total_count'] === 0)
                <p>No execution history matches the current filters.</p>
            @else
                <dl>
                    <dt>Total executions</dt>
                    <dd>{{ $exportSummary['total_count'] }}</dd>
                    <dt>Succeeded</dt>
                    <dd>{{ $exportSummary['succeeded_count'] }}</dd>
                    <dt>Failed</dt>
                    <dd>{{ $exportSummary['failed_count'] }}</dd>
                    <dt>Conflicted</dt>
                    <dd>{{ $exportSummary['conflicted_count'] }}</dd>
                    <dt>Manual previews</dt>
                    <dd>{{ $exportSummary['manual_preview_count'] }}</dd>
                    <dt>Manual executions</dt>
                    <dd>{{ $exportSummary['manual_execution_count'] }}</dd>
                    <dt>Scheduled executions</dt>
                    <dd>{{ $exportSummary['scheduled_execution_count'] }}</dd>
                    <dt>Command executions</dt>
                    <dd>{{ $exportSummary['command_execution_count'] }}</dd>
                    <dt>Candidate count total</dt>
                    <dd>{{ $exportSummary['candidate_count_sum'] }}</dd>
                    <dt>Deleted count total</dt>
                    <dd>{{ $exportSummary['deleted_count_sum'] }}</dd>
                    <dt>Average duration (ms)</dt>
                    <dd>{{ $exportSummary['average_duration_ms'] ?? 'None' }}</dd>
                    <dt>Oldest started at</dt>
                    <dd>{{ $exportSummary['oldest_started_at'] ?? 'None' }}</dd>
                    <dt>Newest started at</dt>
                    <dd>{{ $exportSummary['newest_started_at'] ?? 'None' }}</dd>
                </dl>
            @endif

            <p>
                Current filters:
                type={{ $exportFilters['type'] ?? 'all' }},
                status={{ $exportFilters['status'] ?? 'all' }},
                actor={{ $exportFilters['actor_user_id'] ?? 'all' }},
                started_from={{ $exportFilters['started_from'] ?? 'none' }},
                started_to={{ $exportFilters['started_to'] ?? 'none' }}.
            </p>
        </section>

        <section aria-labelledby="retention-summary-cache-diagnostics-heading">
            <h3 id="retention-summary-cache-diagnostics-heading">
                Summary cache diagnostics
            </h3>

            <button
                id="retention-summary-cache-diagnostics-refresh"
                type="button"
                data-url="{{ route(
                    'reports.saved-view-share-activity-retention.summary-cache-diagnostics'
                ) }}"
            >
                Refresh diagnostics
            </button>

            <p
                id="retention-summary-cache-diagnostics-refresh-status"
                role="status"
                aria-live="polite"
            ></p>

            @php
                $diagnosticsGenerationSource =
                    $exportSummaryCacheDiagnostics['generation_source'];
            @endphp

            @if ($diagnosticsGenerationSource === 'fallback')
                <p role="alert">
                    Cache diagnostics are using fallback values because the
                    cache store could not be read.
                </p>
            @elseif ($diagnosticsGenerationSource === 'default')
                <p>
                    No generated cache version is currently stored. The
                    default generation is active.
                </p>
            @else
                <p>
                    The generated cache version is available.
                </p>
            @endif

            <dl>
                <dt>Cache store</dt>
                <dd id="diagnostics-cache-store">{{ $exportSummaryCacheDiagnostics['cache_store'] }}</dd>

                <dt>Cache read</dt>
                <dd id="diagnostics-cache-read-available">
                    {{ $exportSummaryCacheDiagnostics['cache_read_available']
                        ? 'Available'
                        : 'Unavailable' }}
                </dd>

                <dt>Generation</dt>
                <dd id="diagnostics-generation-present">
                    {{ $exportSummaryCacheDiagnostics['generation_present']
                        ? 'Present'
                        : 'Missing' }}
                </dd>

                <dt>Generation source</dt>
                <dd id="diagnostics-generation-source">
                    {{ $exportSummaryCacheDiagnostics['generation_source'] }}
                </dd>

                <dt>Summary TTL seconds</dt>
                <dd id="diagnostics-summary-ttl-seconds">
                    {{ $exportSummaryCacheDiagnostics['summary_ttl_seconds'] }}
                </dd>

                <dt>Generation TTL seconds</dt>
                <dd id="diagnostics-generation-ttl-seconds">
                    {{ $exportSummaryCacheDiagnostics['generation_ttl_seconds'] }}
                </dd>

                <dt>Observability</dt>
                <dd id="diagnostics-observability-enabled">
                    {{ $exportSummaryCacheDiagnostics['observability_enabled']
                        ? 'Enabled'
                        : 'Disabled' }}
                </dd>

                <dt>Cache key prefix</dt>
                <dd>
                    <code id="diagnostics-cache-key-prefix">
                        {{ $exportSummaryCacheDiagnostics['cache_key_prefix'] }}
                    </code>
                </dd>

                <dt>Generation key prefix</dt>
                <dd>
                    <code id="diagnostics-generation-key-prefix">
                        {{ $exportSummaryCacheDiagnostics['generation_key_prefix'] }}
                    </code>
                </dd>
            </dl>

            <p>
                This section is read-only and never exposes raw cache keys or
                generation tokens.
            </p>
        </section>

        @include(
            'reports.saved-views.partials.'
            . 'share-activity-retention-audit-metrics-health'
        )

        <p>
            Privacy notice: context and updated_at are excluded from exports.
            Export requests do not modify retention history or create sharing activity.
        </p>
    </section>
</div>
@endsection
