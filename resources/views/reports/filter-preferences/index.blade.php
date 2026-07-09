@extends('layouts.app')

@section('content')
    <div class="container" data-testid="report-filter-preferences-page">
        <div class="page-header">
            <div>
                <h1>تفضيلات فلاتر التقارير</h1>
                <p>إدارة الفلاتر المحفوظة تلقائيًا لحسابك داخل التقارير المالية.</p>
            </div>

            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary" data-testid="report-filter-preferences-back-link">مركز التقارير</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success" data-testid="report-filter-preferences-status">
                {{ session('status') }}
            </div>
        @endif

        <div class="card" data-testid="report-filter-preferences-card">
            <div class="card-body">
                <div class="report-meta">
                    <p data-testid="report-filter-preferences-count">عدد التفضيلات المحفوظة: {{ $totalPreferences }}</p>
                </div>

                @if ($preferences->isEmpty())
                    <div class="empty-state" data-testid="report-filter-preferences-empty">
                        لا توجد تفضيلات فلاتر محفوظة حاليًا.
                    </div>
                @else
                    <div class="filter-actions" style="margin-bottom: 16px;">
                        <form method="POST" action="{{ route('reports.filter-preferences.destroy-all') }}" onsubmit="return confirm('هل تريد حذف جميع تفضيلات فلاتر التقارير؟');">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-outline-danger" data-testid="report-filter-preferences-clear-all-button">
                                حذف جميع التفضيلات
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table" data-testid="report-filter-preferences-table">
                            <thead>
                                <tr>
                                    <th>التقرير</th>
                                    <th>الفلاتر المحفوظة</th>
                                    <th>آخر تحديث</th>
                                    <th>إجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($preferences as $preference)
                                    <tr data-testid="report-filter-preference-row">
                                        <td>
                                            <strong>{{ $preference->report_label }}</strong>
                                            <div class="text-muted" dir="ltr">{{ $preference->report_key }}</div>
                                        </td>
                                        <td>
                                            @if ($preference->filters->isEmpty())
                                                <span class="text-muted">لا توجد قيم محفوظة.</span>
                                            @else
                                                <ul style="margin:0; padding-inline-start:18px;">
                                                    @foreach ($preference->filters as $filter)
                                                        <li>
                                                            <strong>{{ $filter['label'] }}:</strong>
                                                            <span>{{ $filter['display_value'] }}</span>
                                                            <small class="text-muted" dir="ltr">({{ $filter['value'] }})</small>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                        <td dir="ltr">{{ $preference->updated_at ?: '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('reports.filter-preferences.destroy', $preference->report_key) }}" onsubmit="return confirm('هل تريد حذف تفضيلات هذا التقرير؟');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-outline-danger" data-testid="report-filter-preference-delete-button">
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
