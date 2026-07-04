<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سندات التسليم</title>
</head>
<body>
    <h1>سندات التسليم</h1>

    <table>
        <thead>
            <tr>
                <th>رقم سند التسليم</th>
                <th>أمر البيع</th>
                <th>العميل</th>
                <th>الحالة</th>
                <th>الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deliveryNotes as $deliveryNote)
                <tr>
                    <td>
                        <a href="{{ route('delivery-notes.show', $deliveryNote) }}">
                            {{ $deliveryNote->delivery_note_number }}
                        </a>
                    </td>
                    <td>{{ $deliveryNote->salesOrder?->sales_order_number }}</td>
                    <td>{{ $deliveryNote->customer?->name }}</td>
                    <td>{{ $deliveryNote->status }}</td>
                    <td>{{ number_format((float) $deliveryNote->total_amount, 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">لا توجد سندات تسليم</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $deliveryNotes->links() }}
</body>
</html>
