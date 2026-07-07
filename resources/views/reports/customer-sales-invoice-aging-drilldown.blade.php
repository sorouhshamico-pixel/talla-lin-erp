@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>تفاصيل فواتير العملاء المفتوحة</h1>
                <p>عرض تفصيلي للفواتير المفتوحة حسب العميل وشريحة العمر.</p>
            </div>

            <a href="{{ route('reports.customer-sales-invoice-aging.index') }}" class="btn btn-outline-secondary">تقرير أعمار ذمم العملاء</a>
        </div>

        <div class="card" data-testid="customer-sales-invoice-aging-drilldown">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.customer-sales-invoice-aging.drilldown') }}" class="filters" data-testid="customer-aging-drilldown-filters">
                    <div class="filter-row">
                        <label for="customer_id">العميل</label>
                        <select name="customer_id" id="customer_id" data-testid="customer-aging-drilldown-customer-select">
                            <option value="">كل العملاء</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-row">
                        <label for="aging_bucket">شريحة العمر</label>
                        <select name="aging_bucket" id="aging_bucket" data-testid="customer-aging-drilldown-bucket-select">
                            <option value="">كل الشرائح</option>
                            @foreach ($agingBuckets as $key => $label)
                                <option value="{{ $key }}" @selected($selectedAgingBucket === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" data-testid="customer-aging-drilldown-apply-filters">تطبيق الفلاتر</button>
                        <a href="{{ route('reports.customer-sales-invoice-aging.drilldown.export', request()->only(['customer_id', 'supplier_id', 'aging_bucket'])) }}" class="btn btn-outline-primary" data-testid="customer-aging-drilldown-export-link">تصدير CSV</a>
                        <a href="{{ route('reports.customer-sales-invoice-aging.drilldown') }}" class="btn btn-outline-secondary" data-testid="customer-aging-drilldown-reset-filters">إعادة تعيين</a>
                    </div>
                </form>

                <div class="report-meta">
                    <p data-testid="customer-aging-drilldown-report-date">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</p>
                    <p data-testid="customer-aging-drilldown-customer-filter">فلتر العميل: {{ $selectedCustomerLabel }}</p>
                    <p data-testid="customer-aging-drilldown-bucket-filter">فلتر شريحة العمر: {{ $selectedAgingBucketLabel }}</p>
                </div>

                <div class="summary-grid" data-testid="customer-aging-drilldown-summary">
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
                    <div class="empty-state" data-testid="customer-aging-drilldown-empty">
                        لا توجد فواتير عملاء مفتوحة حسب الفلاتر الحالية.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table" data-testid="customer-aging-drilldown-table">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>العميل</th>
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
                                        <td>{{ $customerNames[$invoice->customer_id] ?? '' }}</td>
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
