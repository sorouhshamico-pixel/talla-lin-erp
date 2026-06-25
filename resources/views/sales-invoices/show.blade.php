@extends('layouts.admin', [
    'title' => 'تفاصيل الفاتورة | طلة لين ERP',
    'header' => 'تفاصيل فاتورة البيع'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">فاتورة بيع</h1>
            <div class="muted">
                رقم الفاتورة:
                <span dir="ltr">{{ $invoice->invoice_number }}</span>
            </div>
        </div>

        <div>
            <a href="{{ route('sales-invoices.index') }}"
               style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:11px 16px;border-radius:12px;font-weight:700;">
                رجوع للفواتير
            </a>
        </div>
    </div>

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">العميل</div>
            <div class="metric-value" style="font-size:22px;">
                {{ $invoice->customer?->name }}
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">الفرع</div>
            <div class="metric-value" style="font-size:22px;">
                {{ $invoice->branch?->name }}
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">الإجمالي</div>
            <div class="metric-value" style="font-size:22px;">
                {{ number_format((float) $invoice->grand_total, 2) }} ريال
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">عناصر الفاتورة</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الوصف</th>
                        <th>المتغير</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>الخصم</th>
                        <th>الضريبة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td dir="ltr">{{ $item->variant?->sku }}</td>
                            <td>{{ number_format((float) $item->quantity, 0) }}</td>
                            <td>{{ number_format((float) $item->unit_price, 2) }} ريال</td>
                            <td>{{ number_format((float) $item->discount_amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $item->tax_amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $item->line_total, 2) }} ريال</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h2 style="margin-top:0;">ملخص الفاتورة</h2>

        <p><strong>الإجمالي قبل الضريبة:</strong> {{ number_format((float) $invoice->subtotal, 2) }} ريال</p>
        <p><strong>إجمالي الخصم:</strong> {{ number_format((float) $invoice->discount_total, 2) }} ريال</p>
        <p><strong>إجمالي الضريبة:</strong> {{ number_format((float) $invoice->tax_total, 2) }} ريال</p>
        <p><strong>الإجمالي النهائي:</strong> {{ number_format((float) $invoice->grand_total, 2) }} ريال</p>
        <p><strong>المدفوع:</strong> {{ number_format((float) $invoice->paid_amount, 2) }} ريال</p>
        <p style="margin-bottom:0;"><strong>المتبقي:</strong> {{ number_format((float) $invoice->remaining_amount, 2) }} ريال</p>
    </div>
@endsection
