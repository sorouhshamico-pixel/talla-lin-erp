@extends('layouts.app')

@section('content')
    <div class="container" data-testid="report-saved-view-edit-page">
        <div class="page-header">
            <div>
                <h1>تعديل العرض المحفوظ</h1>
                <p>يمكنك تعديل اسم العرض وحالة العرض الافتراضي. الفلاتر المحفوظة تظهر للقراءة فقط في هذه المرحلة.</p>
            </div>

            <a href="{{ route('reports.saved-views.index') }}" class="btn btn-outline-secondary" data-testid="report-saved-view-edit-back-link">
                العودة للعروض المحفوظة
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" data-testid="report-saved-view-edit-errors">
                يرجى مراجعة الحقول المطلوبة.
            </div>
        @endif

        <div class="card" data-testid="report-saved-view-edit-card">
            <form method="POST" action="{{ route('reports.saved-views.update', $savedView->id) }}" data-testid="report-saved-view-edit-form">
                @csrf
                @method('PATCH')

                <div class="form-group">
                    <label for="saved_view_name">اسم العرض</label>
                    <input id="saved_view_name"
                           type="text"
                           name="name"
                           value="{{ old('name', $savedView->name) }}"
                           maxlength="120"
                           required
                           data-testid="report-saved-view-edit-name-input">
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox"
                               name="is_default"
                               value="1"
                               @checked(old('is_default', $savedView->is_default))
                               data-testid="report-saved-view-edit-default-checkbox">
                        تعيين كعرض افتراضي لهذا التقرير
                    </label>
                </div>

                <div class="form-group" data-testid="report-saved-view-edit-report-key">
                    <strong>التقرير:</strong>
                    <span>{{ $reportName }}</span>
                </div>

                <div class="form-group" data-testid="report-saved-view-edit-filters">
                    <strong>الفلاتر المحفوظة:</strong>

                    @if ($filters->isEmpty())
                        <p data-testid="report-saved-view-edit-empty-filters">لا توجد فلاتر محفوظة.</p>
                    @else
                        <ul>
                            @foreach ($filters as $filter)
                                <li data-testid="report-saved-view-edit-filter-item">
                                    <span data-testid="report-saved-view-edit-filter-label">{{ $filter['label'] ?? $filter['key'] }}</span>:
                                    <span data-testid="report-saved-view-edit-filter-value">{{ $filter['value'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary" data-testid="report-saved-view-edit-submit-button">
                    حفظ التعديل
                </button>
            </form>
        </div>
    </div>
@endsection
