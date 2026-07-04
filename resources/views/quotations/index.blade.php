<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>عروض الأسعار</title>
</head>
<body>
    <h1>عروض الأسعار</h1>

    <a href="{{ route('quotations.create') }}" data-testid="quotations-create-link">إنشاء عرض سعر</a>

    <table data-testid="quotations-table">
        <thead>
            <tr>
                <th>رقم العرض</th>
                <th>العميل</th>
                <th>التاريخ</th>
                <th>الحالة</th>
                <th>الرابط</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quotations as $quotation)
                <tr>
                    <td>{{ $quotation->quotation_number }}</td>
                    <td>{{ $quotation->customer?->name }}</td>
                    <td>{{ $quotation->quotation_date?->format('Y-m-d') }}</td>
                    <td>{{ $quotation->status }}</td>
                    <td><a href="{{ route('quotations.show', $quotation) }}">عرض</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">لا توجد عروض أسعار حتى الآن.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $quotations->links() }}
</body>
</html>
