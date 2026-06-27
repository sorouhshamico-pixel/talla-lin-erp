<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة إيراد</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background:#f7f7f7; color:#222; margin:0; padding:24px; }
        .page { max-width:900px; margin:0 auto; }
        .card { background:#fff; border:1px solid #ddd; border-radius:12px; padding:20px; margin-bottom:20px; }
        .muted { color:#666; font-size:14px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; }
        label { display:block; margin-bottom:6px; font-weight:bold; }
        input, select, textarea { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box; }
        textarea { min-height:100px; }
        .btn { display:inline-block; padding:10px 14px; border-radius:8px; background:#111827; color:#fff; text-decoration:none; border:0; cursor:pointer; }
        .btn.secondary { background:#f3f4f6; color:#111827; border:1px solid #d1d5db; }
        .error { color:#b91c1c; font-size:13px; margin-top:5px; }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <h1 style="margin-top:0;">إضافة إيراد</h1>
        <div class="muted">سجل إيرادًا جديدًا مع تحديد الفرع والتصنيف وطريقة التحصيل.</div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('revenues.store') }}" data-testid="revenue-create-form">
            @csrf

            <div class="grid">
                <div>
                    <label for="branch_id">الفرع</label>
                    <select name="branch_id" id="branch_id" required>
                        <option value="">اختر الفرع</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                {{ $branch->name_ar ?? $branch->name ?? $branch->name_en ?? 'فرع' }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="revenue_category_id">تصنيف الإيراد</label>
                    <select name="revenue_category_id" id="revenue_category_id" required>
                        <option value="">اختر التصنيف</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('revenue_category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('revenue_category_id') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="revenue_date">تاريخ الإيراد</label>
                    <input type="date" name="revenue_date" id="revenue_date" value="{{ old('revenue_date', now()->format('Y-m-d')) }}" required>
                    @error('revenue_date') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="amount">المبلغ</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="{{ old('amount') }}" required>
                    @error('amount') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="tax_amount">الضريبة</label>
                    <input type="number" step="0.01" min="0" name="tax_amount" id="tax_amount" value="{{ old('tax_amount', 0) }}">
                    @error('tax_amount') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="collection_method">طريقة التحصيل</label>
                    <select name="collection_method" id="collection_method" required>
                        @foreach ($collectionMethods as $key => $label)
                            <option value="{{ $key }}" @selected(old('collection_method', 'cash') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('collection_method') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="collection_status">حالة التحصيل</label>
                    <select name="collection_status" id="collection_status" required>
                        @foreach ($collectionStatuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('collection_status', 'collected') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('collection_status') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="reference_number">رقم المرجع</label>
                    <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number') }}">
                    @error('reference_number') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="margin-top:14px;">
                <label for="description">الوصف</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}" required>
                @error('description') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div style="margin-top:14px;">
                <label for="notes">ملاحظات</label>
                <textarea name="notes" id="notes">{{ old('notes') }}</textarea>
                @error('notes') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="submit" class="btn">حفظ الإيراد</button>
                <a href="{{ route('revenues.index') }}" class="btn secondary">رجوع</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
