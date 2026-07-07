@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>تفاصيل فواتير الموردين المفتوحة</h1>
                <p>عرض تفصيلي لفواتير المشتريات المفتوحة حسب المورد وشريحة العمر.</p>
            </div>

            <a href="{{ route('reports.supplier-purchase-invoice-aging.index') }}" class="btn btn-outline-secondary">تقرير أعمار ذمم الموردين</a>
        </div>

        <div class="card" data-testid="supplier-purchase-invoice-aging-drilldown">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.supplier-purchase-invoice-aging.drilldown') }}" class="filters" data-testid="supplier-aging-drilldown-filters">
                    <div class="filter-row">
                        <label for="supplier_id">المورد</label>
                        <select name="supplier_id" id="supplier_id" data-testid="supplier-aging-drilldown-supplier-select">
                            <option value="">كل الموردين</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) $selectedSupplierId === (string) $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-row">
                        <label for="aging_bucket">شريحة العمر</label>
                        <select name="aging_bucket" id="aging_bucket" data-testid="supplier-aging-drilldown-bucket-select">
                            <option value="">كل الشرائح</option>
                            @foreach ($agingBuckets as $key => $label)
                                <option value="{{ $key }}" @selected($selectedAgingBucket === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" data-testid="supplier-aging-drilldown-apply-filters">تطبيق الفلاتر</button>
                        <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown') }}" class="btn btn-outline-secondary" data-testid="supplier-aging-drilldown-reset-filters">إعادة تعيين</a>
                    </div>
                </form>

                <div class="report-meta">
                    <p data-testid="supplier-aging-drilldown-report-date">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</p>
                    <p data-testid="supplier-aging-drilldown-supplier-filter">فلتر المورد: {{ $selectedSupplierLabel }}</p>
                    <p data-testid="supplier-aging-drilldown-bucket-filter">فلتر شريحة العمر: {{ $selectedAgingBucketLabel }}</p>
                </div>

                <div class="summary-grid" data-testid="supplier-aging-drilldown-summary">
                    <div class="summary-card">
                        <span>عدد الفواتير المفتوحة</span>
                        <strong>{{ $summary['invoice_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي الفواتير</span>
                        <strong>{{ number_format((float) $summary['grand_total'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي المدفوع</span>
                        <strong>{{ number_format((float) $summary['paid_total'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي المتبقي</span>
                        <strong>{{ number_format((float) $summary['remaining_total'], 2) }} ريال</strong>
                    </div>
                </div>

                @if ($invoices->isEmpty())
                    <div class="empty-state" data-testid="supplier-aging-drilldown-empty">
                        لا توجد فواتير موردين مفتوحة حسب الفلاتر الحالية.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table" data-testid="supplier-aging-drilldown-table">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>المورد</th>
                                    <th>تاريخ الإصدار</th>
                                    <th>تاريخ الاستحقاق</th>
                                    <th>الإجمالي</th>
                                    <th>المدفوع</th>
                                    <th>المتبقي</th>
                                    <th>حالة الدفع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $supplierNames[$invoice->supplier_id] ?? '' }}</td>
                                        <td>{{ $invoice->issued_at ? \Illuminate\Support\Carbon::parse($invoice->issued_at)->format('Y-m-d') : '' }}</td>
                                        <td>{{ $invoice->due_at ? \Illuminate\Support\Carbon::parse($invoice->due_at)->format('Y-m-d') : '' }}</td>
                                        <td>{{ number_format((float) $invoice->grand_total, 2) }} ريال</td>
                                        <td>{{ number_format((float) $invoice->paid_amount, 2) }} ريال</td>
                                        <td>{{ number_format((float) $invoice->remaining_amount, 2) }} ريال</td>
                                        <td>{{ $invoice->payment_status }}</td>
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
