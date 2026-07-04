<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة أمر البيع</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            direction: rtl;
            margin: 32px;
            color: #111827;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: right;
        }

        th {
            background: #f3f4f6;
        }

        .total {
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
            text-align: left;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button onclick="window.print()">طباعة</button>

    <div class="header">
        <h1>طباعة أمر البيع</h1>
        <p>رقم أمر البيع: {{ $salesOrder->sales_order_number }}</p>
        <p>رقم عرض السعر: {{ $salesOrder->quotation?->quotation_number }}</p>
        <p>العميل: {{ $salesOrder->customer?->name }}</p>
        <p>التاريخ: {{ optional($salesOrder->sales_order_date)->format('Y-m-d') }}</p>
        <p>الحالة: {{ $salesOrder->status }}</p>
    </div>

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

    <div class="total">
        إجمالي أمر البيع: {{ number_format((float) $salesOrder->total_amount, 0) }}
    </div>

    @if($salesOrder->notes)
        <p>ملاحظات: {{ $salesOrder->notes }}</p>
    @endif
</body>
</html>
