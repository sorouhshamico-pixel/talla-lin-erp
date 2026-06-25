@extends('layouts.admin', [
    'title' => 'فواتير الشراء | طلة لين ERP',
    'header' => 'فواتير الشراء'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">فواتير الشراء</h1>
            <div class="muted">
                عرض فواتير الشراء وإجمالياتها وحالة الاستلام.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>المورد</th>
                        <th>الفرع</th>
                        <th>المستودع</th>
                        <th>الحالة</th>
                        <th>حالة الدفع</th>
                        <th>الإجمالي</th>
                        <th>المتبقي</th>
                        <th>التاريخ</th>
                        <th>عرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td dir="ltr">{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->supplier?->name }}</td>
                            <td>{{ $invoice->branch?->name }}</td>
                            <td>{{ $invoice->warehouse?->name }}</td>
                            <td>
                                <span class="badge">{{ $invoice->displayStatus() }}</span>
                            </td>
                            <td>{{ $invoice->displayPaymentStatus() }}</td>
                            <td>{{ number_format((float) $invoice->grand_total, 2) }} ريال</td>
                            <td>{{ number_format((float) $invoice->remaining_amount, 2) }} ريال</td>
                            <td>{{ $invoice->invoice_date?->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('purchase-invoices.show', $invoice) }}"
                                   style="color:#8b5e3c;font-weight:700;">
                                    التفاصيل
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">لا توجد فواتير شراء مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
