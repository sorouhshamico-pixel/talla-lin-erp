<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $deliveryNote->delivery_note_number }}</title>
</head>
<body>
    <h1>تفاصيل سند التسليم</h1>

    <p>رقم سند التسليم: {{ $deliveryNote->delivery_note_number }}</p>
    <p>رقم أمر البيع: {{ $deliveryNote->salesOrder?->sales_order_number }}</p>
    <p>العميل: {{ $deliveryNote->customer?->name }}</p>
    <p>التاريخ: {{ optional($deliveryNote->delivery_note_date)->format('Y-m-d') }}</p>
    <p>الحالة: {{ $deliveryNote->status }}</p>

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
            @forelse($deliveryNote->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ number_format((float) $item->quantity, 2) }}</td>
                    <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td>{{ number_format((float) $item->line_total, 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">لا توجد بنود</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p>إجمالي سند التسليم: {{ number_format((float) $deliveryNote->total_amount, 0) }}</p>

    @if($deliveryNote->notes)
        <p>ملاحظات: {{ $deliveryNote->notes }}</p>
    @endif
</body>
</html>
