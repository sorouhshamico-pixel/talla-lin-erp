@extends('layouts.app')

@section('content')
<div class="container"
     data-testid="saved-view-share-owner-activities-page">
    <h1 class="h4 mb-3">سجل نشاط مشاركة العروض المحفوظة</h1>

    <form method="GET" class="card card-body mb-3">
        <div class="row g-3">
            <div class="col-md-4">
                <select name="action" class="form-select">
                    <option value="">كل الأحداث</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}"
                            @selected(request('action') === $action)>
                            {{ $action }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <select name="recipient_user_id" class="form-select">
                    <option value="">كل المستلمين</option>
                    @foreach ($recipients as $recipient)
                        <option value="{{ $recipient->id }}">
                            {{ $recipient->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <button class="btn btn-primary" type="submit">
                    تطبيق
                </button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <tbody>
                    @forelse ($activities as $activity)
                        <tr>
                            <td>{{ $activity->action }}</td>
                            <td>
                                {{ $activity->source_name_snapshot
                                    ?? 'عرض محذوف' }}
                            </td>
                            <td>
                                {{ $activity->recipient?->name
                                    ?? 'مستخدم محذوف' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td data-testid="owner-activity-empty">
                                لا توجد أنشطة مطابقة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($activities->hasPages())
            <div class="card-footer">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
