@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>تقرير أعمار ذمم الموردين</h1>
                <p>متابعة فواتير المشتريات المفتوحة حسب المورد وشرائح العمر.</p>
            </div>

            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">مركز التقارير</a>
        </div>

        <div class="card" data-testid="supplier-purchase-invoice-aging-report">
            <div class="card-body">

                <form method="GET" action="{{ route('reports.supplier-purchase-invoice-aging.index') }}" class="filters" data-testid="supplier-aging-filters">
                    <div class="filter-row">
                        <label for="supplier_id">المورد</label>
                        <select name="supplier_id" id="supplier_id" data-testid="supplier-aging-supplier-select">
                            <option value="">كل الموردين</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-row">
                        <label for="aging_bucket">شريحة العمر</label>
                        <select name="aging_bucket" id="aging_bucket" data-testid="supplier-aging-bucket-select">
                            <option value="">كل الشرائح</option>
                            @foreach ($agingBuckets as $value => $label)
                                <option value="{{ $value }}" @selected(request('aging_bucket') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" data-testid="supplier-aging-apply-filters">تطبيق الفلاتر</button>
                        <a href="{{ route('reports.supplier-purchase-invoice-aging.index') }}" class="btn btn-outline-secondary" data-testid="supplier-aging-reset-filters">إعادة تعيين</a>
                    </div>
                </form>

                <div class="report-meta">
                    <p data-testid="supplier-aging-report-date">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</p>
                    <p data-testid="supplier-aging-supplier-filter">فلتر المورد: {{ $supplierFilterLabel }}</p>
                    <p data-testid="supplier-aging-bucket-filter">فلتر شريحة العمر: {{ $agingBucketFilterLabel }}</p>
                </div>

                <div class="summary-grid" data-testid="supplier-aging-summary">
                    <div class="summary-card">
                        <span>عدد الموردين</span>
                        <strong>{{ $summary['suppliers_count'] }}</strong>
                    </div>
                    <div class="summary-card">
                        <span>عدد الفواتير المفتوحة</span>
                        <strong>{{ $summary['invoice_count'] }}</strong>
                    </div>
                    <div class="summary-card">
                        <span>إجمالي الذمم المفتوحة</span>
                        <strong>{{ number_format((float) $summary['remaining_total'], 2) }} ريال</strong>
                    </div>
                    <div class="summary-card">
                        <span>إجمالي المتأخر</span>
                        <strong>{{ number_format((float) $summary['overdue_total'], 2) }} ريال</strong>
                    </div>
                </div>

                @if ($rows->isEmpty())
                    <div class="empty-state" data-testid="supplier-aging-empty">
                        لا توجد ذمم مفتوحة للموردين.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table" data-testid="supplier-aging-table">
                            <thead>
                                <tr>
                                    <th>المورد</th>
                                    <th>عدد الفواتير</th>
                                    <th>إجمالي المتبقي</th>
                                    <th>غير مستحقة بعد</th>
                                    <th>متأخرة 1 إلى 30</th>
                                    <th>متأخرة 31 إلى 60</th>
                                    <th>متأخرة 61 إلى 90</th>
                                    <th>أكثر من 90</th>
                                    <th>بدون تاريخ استحقاق</th>
                                    <th>أقدم استحقاق</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $row['supplier'] ? $row['supplier']->name : '' }}</td>
                                        <td>{{ $row['invoice_count'] }}</td>
                                        <td>{{ number_format((float) $row['remaining_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['not_due_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['overdue_1_30_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['overdue_31_60_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['overdue_61_90_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['overdue_more_than_90_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['without_due_date_total'], 2) }} ريال</td>
                                        <td>{{ $row['oldest_due_at'] ? $row['oldest_due_at']->format('Y-m-d') : '' }}</td>
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
