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
</div>
@endsection
