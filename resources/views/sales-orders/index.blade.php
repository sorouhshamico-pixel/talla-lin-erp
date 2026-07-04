<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>أوامر البيع</title>
</head>
<body>
    <h1>أوامر البيع</h1>

    <table>
        <thead>
            <tr>
                <th>رقم أمر البيع</th>
                <th>العميل</th>
                <th>الحالة</th>
                <th>الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesOrders as $salesOrder)
                <tr>
                    <td>
                        <a href="{{ route('sales-orders.show', $salesOrder) }}">
                            {{ $salesOrder->sales_order_number }}
                        </a>
                    </td>
                    <td>{{ $salesOrder->customer?->name }}</td>
                    <td>{{ $salesOrder->status }}</td>
                    <td>{{ number_format((float) $salesOrder->total_amount, 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">لا توجد أوامر بيع</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $salesOrders->links() }}
</body>
</html>
