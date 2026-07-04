<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إنشاء عرض سعر</title>
</head>
<body>
    <h1>إنشاء عرض سعر</h1>

    <form method="POST" action="{{ route('quotations.store') }}" data-testid="quotations-create-form">
        @csrf

        <label>
            العميل
            <select name="customer_id" data-testid="quotation-customer-select">
                <option value="">اختر العميل</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
        </label>

        @error('customer_id')
            <div>{{ $message }}</div>
        @enderror

        <label>
            تاريخ العرض
            <input type="date" name="quotation_date" value="{{ old('quotation_date', now()->toDateString()) }}" data-testid="quotation-date-input">
        </label>

        @error('quotation_date')
            <div>{{ $message }}</div>
        @enderror

        <label>
            تاريخ الانتهاء
            <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" data-testid="quotation-expiry-date-input">
        </label>

        <label>
            ملاحظات
            <textarea name="notes" data-testid="quotation-notes-input">{{ old('notes') }}</textarea>
        </label>

        <button type="submit" data-testid="quotation-submit-button">حفظ عرض السعر</button>
    </form>
</body>
</html>
