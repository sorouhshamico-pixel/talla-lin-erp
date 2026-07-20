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

        <p>
            Privacy notice: context and updated_at are excluded from exports.
            Export requests do not modify retention history or create sharing activity.
        </p>
    </section>
</div>
@endsection
