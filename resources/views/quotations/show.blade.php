<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل عرض السعر</title>
</head>
<body>
    <h1>تفاصيل عرض السعر</h1>

    <div data-testid="quotation-number">{{ $quotation->quotation_number }}</div>
    <div data-testid="quotation-customer">{{ $quotation->customer?->name }}</div>
    <div data-testid="quotation-date">{{ $quotation->quotation_date?->format('Y-m-d') }}</div>
    <div data-testid="quotation-status">{{ $quotation->status }}</div>

    @if($quotation->notes)
        <div data-testid="quotation-notes">{{ $quotation->notes }}</div>
    @endif

    <a href="{{ route('quotations.index') }}">العودة لعروض الأسعار</a>
</body>
</html>


    <div style="margin-top: 24px;">
        <h3>بنود عرض السعر</h3>

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
                @forelse($quotation->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format((float) $item->quantity, 2) }}</td>
                        <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td>{{ number_format((float) $item->line_total, 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">لا توجد بنود بعد</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p>إجمالي عرض السعر: {{ number_format((float) $quotation->items->sum('line_total'), 0) }}</p>
    </div>

