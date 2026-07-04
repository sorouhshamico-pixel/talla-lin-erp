<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $salesOrder->sales_order_number }}</title>
</head>
<body>
    <h1>تفاصيل أمر البيع</h1>

    <p>رقم أمر البيع: {{ $salesOrder->sales_order_number }}</p>
    <p>العميل: {{ $salesOrder->customer?->name }}</p>
    <p>رقم عرض السعر: {{ $salesOrder->quotation?->quotation_number }}</p>
    <p>التاريخ: {{ optional($salesOrder->sales_order_date)->format('Y-m-d') }}</p>
    <p>الحالة: {{ $salesOrder->status }}</p>

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
            @forelse($salesOrder->items as $item)
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

    <p>إجمالي أمر البيع: {{ number_format((float) $salesOrder->total_amount, 0) }}</p>

    @if($salesOrder->notes)
        <p>ملاحظات: {{ $salesOrder->notes }}</p>
    @endif
</body>
</html>
