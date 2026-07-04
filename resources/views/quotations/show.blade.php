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
