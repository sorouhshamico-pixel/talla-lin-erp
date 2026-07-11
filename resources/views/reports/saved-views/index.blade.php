@extends('layouts.app')

@section('content')
    <div class="container" data-testid="report-saved-views-page">
        <div class="page-header">
            <div>
                <h1>العروض المحفوظة للتقارير</h1>
                <p>إدارة عروض الفلاتر المسماة للتقارير المالية.</p>
            </div>

            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary" data-testid="report-saved-views-back-link">مركز التقارير</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success" data-testid="report-saved-views-status">
                {{ session('status') }}
            </div>
        @endif

        <div class="card" data-testid="report-saved-views-card">
            <div class="card-body">
                <div class="report-meta">
                    <p data-testid="report-saved-views-count">عدد العروض المحفوظة: {{ $totalSavedViews }}</p>
                </div>

                @if ($savedViews->isEmpty())
                    <div class="empty-state" data-testid="report-saved-views-empty">
                        لا توجد عروض محفوظة حاليًا.
                    </div>
                @else
                    <div class="filter-actions" style="margin-bottom: 16px;">
                        <form method="POST" action="{{ route('reports.saved-views.destroy-all') }}" onsubmit="return confirm('هل تريد حذف جميع العروض المحفوظة؟');">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-outline-danger" data-testid="report-saved-views-clear-all-button">
                                حذف جميع العروض
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table" data-testid="report-saved-views-table">
                            <thead>
                                <tr>
                                    <th>اسم العرض</th>
                                    <th>التقرير</th>
                                    <th>الفلاتر</th>
                                    <th>افتراضي</th>
                                    <th>آخر تحديث</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($savedViews as $savedView)
                                    <tr data-testid="report-saved-view-row">
                                        <td>
                                            <strong>{{ $savedView->name }}</strong>
                                        </td>
                                        <td>
                                            <strong>{{ $savedView->report_label }}</strong>
                                            <div class="text-muted" dir="ltr">{{ $savedView->report_key }}</div>
                                        </td>
                                        <td>
                                            @if ($savedView->filters->isEmpty())
                                                <span class="text-muted">لا توجد فلاتر.</span>
                                            @else
                                                <ul style="margin:0; padding-inline-start:18px;">
                                                    @foreach ($savedView->filters as $filter)
                                                        <li>
                                                            <strong>{{ $filter['label'] }}:</strong>
                                                            <span>{{ $filter['display_value'] }}</span>
                                                            <small class="text-muted" dir="ltr">({{ $filter['value'] }})</small>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($savedView->is_default)
                                                <span data-testid="report-saved-view-default-badge">نعم</span>
                                            @else
                                                <span class="text-muted">لا</span>
                                            @endif
                                        </td>
                                        <td dir="ltr">{{ $savedView->updated_at ?: '-' }}</td>
                                        <td>
                                            @if ($savedView->report_url)
                                                <a href="{{ $savedView->report_url }}" class="btn btn-outline-primary" data-testid="report-saved-view-open-link">
                                                    فتح التقرير
                                                </a>
                                            @endif

                                            <a href="{{ route('reports.saved-views.edit', $savedView->id) }}" class="btn btn-outline-secondary" data-testid="report-saved-view-edit-link">
                                                تعديل
                                            </a>

                                            <a href="{{ route('reports.saved-views.apply', $savedView->id) }}" class="btn btn-outline-primary" data-testid="report-saved-view-apply-link">
                                                تطبيق
                                            </a>

                                            <form method="POST" action="{{ route('reports.saved-views.duplicate', $savedView->id) }}" class="d-inline" data-testid="report-saved-view-duplicate-form">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-secondary" data-testid="report-saved-view-duplicate-button">
                                                    نسخ
                                                </button>
                                            </form>

                                            @unless ($savedView->is_default)
                                                <form method="POST" action="{{ route('reports.saved-views.make-default', $savedView->id) }}" style="display:inline-block;">
                                                    @csrf
                                                    @method('PATCH')

                                                    <button type="submit" class="btn btn-outline-secondary" data-testid="report-saved-view-make-default-button">
                                                        تعيين افتراضي
                                                    </button>
                                                </form>
                                            @endunless

                                            <form method="POST" action="{{ route('reports.saved-views.destroy', $savedView->id) }}" style="display:inline-block;" onsubmit="return confirm('هل تريد حذف هذا العرض؟');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-outline-danger" data-testid="report-saved-view-delete-button">
                                                    حذف
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
