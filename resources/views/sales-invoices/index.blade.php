@extends('layouts.admin', [
    'title' => 'فواتير البيع | طلة لين ERP',
    'header' => 'فواتير البيع'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">فواتير البيع</h1>
            <div class="muted">
                عرض فواتير البيع وإجمالياتها وحالة السداد.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>العميل</th>
                        <th>الفرع</th>
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
                            <td>{{ $invoice->customer?->name }}</td>
                            <td>{{ $invoice->branch?->name }}</td>
                            <td>
                                <span class="badge">{{ $invoice->displayStatus() }}</span>
                            </td>
                            <td>{{ $invoice->payment_status }}</td>
                            <td>{{ number_format((float) $invoice->grand_total, 2) }} ريال</td>
                            <td>{{ number_format((float) $invoice->remaining_amount, 2) }} ريال</td>
                            <td>{{ $invoice->issued_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('sales-invoices.show', $invoice) }}"
                                   style="color:#8b5e3c;font-weight:700;">
                                    التفاصيل
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">لا توجد فواتير بيع مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
