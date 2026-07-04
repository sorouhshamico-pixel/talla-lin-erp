@extends('layouts.admin', [
    'title' => 'تفاصيل سند التسليم | طلة لين ERP',
    'header' => 'تفاصيل سند التسليم'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">سند تسليم</h1>
            <div class="muted">
                رقم سند التسليم:
                <span dir="ltr">{{ $deliveryNote->delivery_note_number }}</span>
            </div>
        </div>

        <div>
            @if ($deliveryNote->salesInvoice)
                <a href="{{ route('sales-invoices.show', $deliveryNote->salesInvoice) }}"
                   style="display:inline-block;background:#157347;color:#fff;padding:11px 16px;border-radius:12px;font-weight:700;">
                    فاتورة مرتبطة: {{ $deliveryNote->salesInvoice->invoice_number }}
                </a>
            @elseif ($deliveryNote->status === 'delivered')
                <form method="POST"
                      action="{{ route('delivery-notes.convert-to-sales-invoice', $deliveryNote) }}"
                      style="display:inline-block;">
                    @csrf
                    <button type="submit"
                            style="border:0;background:#157347;color:#fff;padding:11px 16px;border-radius:12px;font-weight:700;cursor:pointer;">
                        تحويل إلى فاتورة مبيعات
                    </button>
                </form>
            @endif

            <a href="{{ route('delivery-notes.print', $deliveryNote) }}"
               style="display:inline-block;background:#5d3b25;color:#fff;padding:11px 16px;border-radius:12px;font-weight:700;">
                طباعة
            </a>

            <a href="{{ route('delivery-notes.index') }}"
               style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:11px 16px;border-radius:12px;font-weight:700;">
                رجوع لسندات التسليم
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-bottom: 20px; border-color:#f5c2c7; color:#842029;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if (session('success'))
        <div class="card" style="margin-bottom: 20px; border-color: #cbe7d5; color: #157347;">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">العميل</div>
            <div class="metric-value" style="font-size:22px;">
                {{ $deliveryNote->customer?->name }}
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">أمر البيع</div>
            <div class="metric-value" style="font-size:22px;">
                {{ $deliveryNote->salesOrder?->sales_order_number ?? '-' }}
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">الإجمالي</div>
            <div class="metric-value" style="font-size:22px;">
                {{ number_format((float) $deliveryNote->total_amount, 2) }} ريال
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <h2 style="margin-top:0;">بيانات سند التسليم</h2>

        <p><strong>رقم سند التسليم:</strong> <span dir="ltr">{{ $deliveryNote->delivery_note_number }}</span></p>
        <p><strong>رقم أمر البيع:</strong> <span dir="ltr">{{ $deliveryNote->salesOrder?->sales_order_number ?? '-' }}</span></p>
        <p><strong>العميل:</strong> {{ $deliveryNote->customer?->name }}</p>
        <p><strong>التاريخ:</strong> {{ optional($deliveryNote->delivery_note_date)->format('Y-m-d') }}</p>
        <p><strong>الحالة:</strong> {{ $deliveryNote->status }}</p>

        @if ($deliveryNote->salesInvoice)
            <p>
                <strong>الفاتورة المرتبطة:</strong>
                <a href="{{ route('sales-invoices.show', $deliveryNote->salesInvoice) }}">
                    {{ $deliveryNote->salesInvoice->invoice_number }}
                </a>
            </p>
        @endif
    </div>

    <div class="card">
        <h2 style="margin-top:0;">بنود سند التسليم</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الوصف</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveryNote->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td>{{ number_format((float) $item->quantity, 2) }}</td>
                            <td>{{ number_format((float) $item->unit_price, 2) }} ريال</td>
                            <td>{{ number_format((float) $item->line_total, 2) }} ريال</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">لا توجد بنود</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h2 style="margin-top:0;">ملخص سند التسليم</h2>

        <p><strong>إجمالي سند التسليم:</strong> {{ number_format((float) $deliveryNote->total_amount, 2) }} ريال</p>

        @if ($deliveryNote->notes)
            <p style="margin-bottom:0;"><strong>ملاحظات:</strong> {{ $deliveryNote->notes }}</p>
        @endif
    </div>
@endsection
