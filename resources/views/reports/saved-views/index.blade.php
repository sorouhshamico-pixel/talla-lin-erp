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

        <div class="card" data-testid="report-saved-views-filter-card" style="margin-bottom: 16px;">
            <form method="GET" action="{{ route('reports.saved-views.index') }}" data-testid="report-saved-views-search-form">
                <div class="form-group">
                    <label for="report_saved_views_search">بحث</label>
                    <input id="report_saved_views_search"
                           type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           maxlength="120"
                           placeholder="ابحث باسم العرض أو التقرير أو الفلاتر"
                           data-testid="report-saved-views-search-input">
                </div>

                <div class="form-group">
                    <label for="report_saved_views_report_key">التقرير</label>
                    <select id="report_saved_views_report_key"
                            name="report_key"
                            data-testid="report-saved-views-report-key-select">
                        <option value="">كل التقارير</option>
                        @foreach ($reportOptions as $reportOption)
                            <option value="{{ $reportOption->key }}" @selected(($filters['report_key'] ?? '') === $reportOption->key)>
                                {{ $reportOption->label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="report_saved_views_per_page">عدد النتائج في الصفحة</label>
                    <select id="report_saved_views_per_page"
                            name="per_page"
                            data-testid="report-saved-views-per-page-select">
                        @foreach ([5, 10, 15, 25, 50, 100] as $perPageOption)
                            <option value="{{ $perPageOption }}" @selected((int) ($filters['per_page'] ?? 15) === $perPageOption)>
                                {{ $perPageOption }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    @php
                        $exportQuery = array_filter([
                            'search' => $filters['search'] ?? '',
                            'report_key' => $filters['report_key'] ?? '',
                        ], fn ($value) => $value !== null && $value !== '');
                    @endphp

                    <button type="submit" class="btn btn-primary" data-testid="report-saved-views-search-submit-button">
                        تطبيق
                    </button>

                    <a href="{{ route('reports.saved-views.index') }}" class="btn btn-outline-secondary" data-testid="report-saved-views-search-clear-link">
                        مسح
                    </a>

                    <a href="{{ route('reports.saved-views.export', $exportQuery) }}" class="btn btn-outline-secondary" data-testid="report-saved-views-export-link">
                        تصدير CSV
                    </a>
                </div>
            </form>

            <form method="POST"
                  action="{{ route('reports.saved-views.import-preview') }}"
                  enctype="multipart/form-data"
                  data-testid="report-saved-views-import-preview-form"
                  style="margin-top: 16px;">
                @csrf

                <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                <input type="hidden" name="report_key" value="{{ $filters['report_key'] ?? '' }}">
                <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? $savedViews->perPage() }}">

                <div class="form-group">
                    <label for="report_saved_views_import_csv">معاينة استيراد CSV</label>
                    <input id="report_saved_views_import_csv"
                           type="file"
                           name="csv_file"
                           accept=".csv,text/csv"
                           data-testid="report-saved-views-import-file-input">
                </div>

                <button type="submit" class="btn btn-outline-secondary" data-testid="report-saved-views-import-preview-button">
                    معاينة الاستيراد
                </button>
            </form>
        </div>

        @if (! empty($importPreview))
            <div class="card" data-testid="report-saved-views-import-preview-card" style="margin-bottom: 16px;">
                <h2>معاينة استيراد CSV</h2>

                @if (! empty($importPreview['header_errors']))
                    <div class="alert alert-danger" data-testid="report-saved-views-import-header-errors">
                        @foreach ($importPreview['header_errors'] as $headerError)
                            <div>{{ $headerError }}</div>
                        @endforeach
                    </div>
                @else
                    <p data-testid="report-saved-views-import-preview-summary">
                        إجمالي الصفوف: {{ $importPreview['total_rows'] }}
                        | صالحة: {{ $importPreview['valid_rows'] }}
                        | غير صالحة: {{ $importPreview['invalid_rows'] }}
                    </p>

                    <div class="table-responsive">
                        <table class="table" data-testid="report-saved-views-import-preview-table">
                            <thead>
                                <tr>
                                    <th>الصف</th>
                                    <th>اسم العرض</th>
                                    <th>التقرير</th>
                                    <th>افتراضي</th>
                                    <th>الفلاتر</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($importPreview['rows'] as $previewRow)
                                    <tr data-testid="report-saved-views-import-preview-row">
                                        <td>{{ $previewRow['row_number'] }}</td>
                                        <td>{{ $previewRow['name'] }}</td>
                                        <td>
                                            <strong>{{ $previewRow['report_label'] }}</strong>
                                            <div class="text-muted" dir="ltr">{{ $previewRow['report_key'] }}</div>
                                        </td>
                                        <td>{{ $previewRow['is_default'] }}</td>
                                        <td>{{ $previewRow['filters_summary'] }}</td>
                                        <td>
                                            @if ($previewRow['status'] === 'valid')
                                                <span data-testid="report-saved-views-import-row-valid">صالح</span>
                                            @else
                                                <span data-testid="report-saved-views-import-row-invalid">غير صالح</span>
                                                <ul>
                                                    @foreach ($previewRow['errors'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if (($importPreview['valid_rows'] ?? 0) > 0 && ($importPreview['invalid_rows'] ?? 0) === 0)
                        <form method="POST"
                              action="{{ route('reports.saved-views.import-apply') }}"
                              data-testid="report-saved-views-import-apply-form"
                              style="margin-top: 16px;">
                            @csrf

                            <textarea name="csv_payload"
                                      style="display:none;"
                                      data-testid="report-saved-views-import-apply-payload">{{ $importPreview['csv_payload'] ?? '' }}</textarea>

                            <p class="text-muted" data-testid="report-saved-views-import-apply-warning">
                                سيتم إنشاء العروض الصالحة فقط. لن يتم استخدام filters_summary لإعادة بناء الفلاتر، وسيتم تخطي العروض المكررة بدل استبدالها.
                            </p>

                            <button type="submit" class="btn btn-primary" data-testid="report-saved-views-import-apply-button">
                                تطبيق الاستيراد
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        @endif

        <div class="card" data-testid="report-saved-views-card">
            <div class="card-body">
                <div class="report-meta">
                    <p data-testid="report-saved-views-count">عدد العروض المحفوظة: {{ $totalSavedViews }}</p>

                    @if ($savedViews->total() > 0)
                        <p class="text-muted" data-testid="report-saved-views-results-summary">
                            عرض {{ $savedViews->firstItem() }} إلى {{ $savedViews->lastItem() }} من {{ $savedViews->total() }} نتيجة
                        </p>

                        <p class="text-muted" data-testid="report-saved-views-per-page-summary">
                            عدد النتائج في الصفحة: {{ $savedViews->perPage() }}
                        </p>
                    @endif
                </div>

                @if (($filters['search'] ?? '') !== '' || ($filters['report_key'] ?? '') !== '')
                    @php
                        $activeReportOption = collect($reportOptions)->firstWhere('key', $filters['report_key'] ?? '');
                    @endphp

                    <div class="alert alert-info" data-testid="report-saved-views-active-filters">
                        <strong>الفلاتر النشطة:</strong>

                        @if (($filters['search'] ?? '') !== '')
                            <span data-testid="report-saved-views-active-search">
                                بحث: {{ $filters['search'] }}
                            </span>
                        @endif

                        @if (($filters['report_key'] ?? '') !== '')
                            <span data-testid="report-saved-views-active-report-key">
                                التقرير: {{ $activeReportOption->label ?? $filters['report_key'] }}
                            </span>
                        @endif

                        <a href="{{ route('reports.saved-views.index') }}"
                           class="btn btn-outline-secondary"
                           data-testid="report-saved-views-active-filters-clear-link">
                            مسح الفلاتر
                        </a>
                    </div>
                @endif

                @if ($savedViews->count() === 0)
                    <div class="empty-state" data-testid="report-saved-views-empty">
                        @if (($filters['search'] ?? '') !== '' || ($filters['report_key'] ?? '') !== '')
                            <span data-testid="report-saved-views-filtered-empty-message">
                                لا توجد نتائج مطابقة للفلاتر الحالية.
                            </span>

                            <div style="margin-top: 12px;">
                                <a href="{{ route('reports.saved-views.index') }}"
                                   class="btn btn-outline-secondary"
                                   data-testid="report-saved-views-filtered-empty-clear-link">
                                    عرض كل العروض
                                </a>
                            </div>
                        @else
                            <span data-testid="report-saved-views-unfiltered-empty-message">
                                لا توجد عروض محفوظة حاليًا.
                            </span>
                        @endif
                    </div>
                @else
                    <div class="filter-actions" style="margin-bottom: 16px;">
                        <form id="report_saved_views_bulk_delete_form"
                              method="POST"
                              action="{{ route('reports.saved-views.bulk-destroy') }}"
                              data-testid="report-saved-views-bulk-action-form">
                            @csrf
                            @method('DELETE')

                            <input type="hidden" name="return_search" value="{{ $filters['search'] ?? '' }}" data-testid="report-saved-views-bulk-return-search">
                            <input type="hidden" name="return_report_key" value="{{ $filters['report_key'] ?? '' }}" data-testid="report-saved-views-bulk-return-report-key">
                            <input type="hidden" name="return_per_page" value="{{ $filters['per_page'] ?? $savedViews->perPage() }}" data-testid="report-saved-views-bulk-return-per-page">
                            <input type="hidden" name="return_page" value="{{ request('page') }}" data-testid="report-saved-views-bulk-return-page">

                            <button type="submit"
                                    class="btn btn-outline-danger"
                                    data-testid="report-saved-views-bulk-delete-button"
                                    disabled>
                                حذف المحدد
                            </button>

                            <span class="text-muted"
                                  data-testid="report-saved-views-selected-count"
                                  aria-live="polite">
                                المحدد: 0
                            </span>
                        </form>

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
                                    <th>
                                        <input type="checkbox"
                                               data-testid="report-saved-views-select-all-checkbox"
                                               aria-label="تحديد كل العروض المحفوظة">
                                    </th>
                                    <th>اسم العرض</th>
                                    <th>التقرير</th>
                                    <th>الفلاتر</th>
                                    <th>افتراضي</th>
                                    <th>آخر تحديث</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($savedViews as $savedView)
                                    <tr data-testid="report-saved-view-row">
                                        <td>
                                            <input type="checkbox"
                                                   name="saved_view_ids[]"
                                                   value="{{ $savedView->id }}"
                                                   form="report_saved_views_bulk_delete_form"
                                                   data-testid="report-saved-view-bulk-select-checkbox"
                                                   aria-label="تحديد {{ $savedView->name }}">
                                        </td>
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
                                            <div class="saved-view-actions" data-testid="report-saved-view-actions" style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                                                <div class="saved-view-action-group saved-view-action-group-primary" data-testid="report-saved-view-primary-actions" style="display:flex; flex-wrap:wrap; gap:8px;">
                                                    @if ($savedView->report_url)
                                                        <a href="{{ $savedView->report_url }}" class="btn btn-outline-primary" data-testid="report-saved-view-open-link">
                                                            فتح التقرير
                                                        </a>
                                                    @endif

                                                    <a href="{{ route('reports.saved-views.apply', $savedView->id) }}" class="btn btn-outline-primary" data-testid="report-saved-view-apply-link">
                                                        تطبيق
                                                    </a>
                                                </div>

                                                <div class="saved-view-action-group saved-view-action-group-secondary" data-testid="report-saved-view-secondary-actions" style="display:flex; flex-wrap:wrap; gap:8px;">
                                                    <a href="{{ route('reports.saved-views.edit', $savedView->id) }}" class="btn btn-outline-secondary" data-testid="report-saved-view-edit-link">
                                                        تعديل
                                                    </a>

                                                    <form method="POST" action="{{ route('reports.saved-views.duplicate', $savedView->id) }}" class="d-inline" data-testid="report-saved-view-duplicate-form">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-secondary" data-testid="report-saved-view-duplicate-button">
                                                            نسخ
                                                        </button>
                                                    </form>

                                                    @unless ($savedView->is_default)
                                                        <form method="POST" action="{{ route('reports.saved-views.make-default', $savedView->id) }}" style="display:inline-block;" data-testid="report-saved-view-make-default-form">
                                                            @csrf
                                                            @method('PATCH')

                                                            <button type="submit" class="btn btn-outline-secondary" data-testid="report-saved-view-make-default-button">
                                                                تعيين افتراضي
                                                            </button>
                                                        </form>
                                                    @endunless
                                                </div>

                                                <div class="saved-view-action-group saved-view-action-group-danger" data-testid="report-saved-view-danger-actions" style="display:flex; flex-wrap:wrap; gap:8px;">
                                                    <form method="POST" action="{{ route('reports.saved-views.destroy', $savedView->id) }}" style="display:inline-block;" onsubmit="return confirm('هل تريد حذف هذا العرض؟');">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit" class="btn btn-outline-danger" data-testid="report-saved-view-delete-button">
                                                            حذف
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const bulkForm = document.querySelector('[data-testid="report-saved-views-bulk-action-form"]');
                            const bulkDeleteButton = document.querySelector('[data-testid="report-saved-views-bulk-delete-button"]');
                            const selectedCountLabel = document.querySelector('[data-testid="report-saved-views-selected-count"]');
                            const selectAll = document.querySelector('[data-testid="report-saved-views-select-all-checkbox"]');
                            const rowCheckboxes = Array.from(document.querySelectorAll('[data-testid="report-saved-view-bulk-select-checkbox"]'));

                            function updateBulkSelectionState() {
                                const selectedCount = rowCheckboxes.filter(function (checkbox) {
                                    return checkbox.checked;
                                }).length;

                                if (selectedCountLabel) {
                                    selectedCountLabel.textContent = 'المحدد: ' + selectedCount;
                                }

                                if (bulkDeleteButton) {
                                    bulkDeleteButton.disabled = selectedCount === 0;
                                }

                                if (selectAll) {
                                    selectAll.checked = selectedCount > 0 && selectedCount === rowCheckboxes.length;
                                    selectAll.indeterminate = selectedCount > 0 && selectedCount < rowCheckboxes.length;
                                }
                            }

                            if (selectAll) {
                                selectAll.addEventListener('change', function () {
                                    rowCheckboxes.forEach(function (checkbox) {
                                        checkbox.checked = selectAll.checked;
                                    });

                                    updateBulkSelectionState();
                                });
                            }

                            rowCheckboxes.forEach(function (checkbox) {
                                checkbox.addEventListener('change', updateBulkSelectionState);
                            });

                            if (bulkForm) {
                                bulkForm.addEventListener('submit', function (event) {
                                    const selectedCount = rowCheckboxes.filter(function (checkbox) {
                                        return checkbox.checked;
                                    }).length;

                                    if (selectedCount === 0) {
                                        event.preventDefault();
                                        alert('اختر عرضًا واحدًا على الأقل.');
                                        updateBulkSelectionState();

                                        return;
                                    }

                                    if (! confirm('هل تريد حذف العروض المحددة؟')) {
                                        event.preventDefault();
                                    }
                                });
                            }

                            updateBulkSelectionState();
                        });
                    </script>

                    @if ($savedViews->hasPages())
                        <div class="pagination-wrap" data-testid="report-saved-views-pagination">
                            {{ $savedViews->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
