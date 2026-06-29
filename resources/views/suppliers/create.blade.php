<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة مورد</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f6f7fb; color: #111827; margin: 0; padding: 24px; }
        .container { max-width: 860px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin-bottom: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
        h1 { margin: 0 0 8px; font-size: 28px; }
        .muted { color: #6b7280; font-size: 14px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 14px; }
        input, textarea, select { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; box-sizing: border-box; }
        textarea { min-height: 90px; resize: vertical; }
        .error { color: #b91c1c; font-size: 13px; margin-top: 6px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 18px; }
        .btn { border: 0; border-radius: 10px; padding: 11px 16px; background: #111827; color: #fff; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; }
        .btn.secondary { background: #374151; }
        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container" data-testid="suppliers-create">
    <div class="card">
        <h1>إضافة مورد</h1>
        <div class="muted">أدخل بيانات المورد الأساسية.</div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('suppliers.store') }}" data-testid="suppliers-create-form">
            @csrf

            <div class="form-grid">
                <div class="full">
                    <label for="name">اسم المورد</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                    @error('name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="phone">الهاتف</label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone') }}">
                    @error('phone') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="email">البريد الإلكتروني</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}">
                    @error('email') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="city">المدينة</label>
                    <input id="city" type="text" name="city" value="{{ old('city') }}">
                    @error('city') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="tax_number">الرقم الضريبي</label>
                    <input id="tax_number" type="text" name="tax_number" value="{{ old('tax_number') }}">
                    @error('tax_number') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="is_active">الحالة</label>
                    <select id="is_active" name="is_active">
                        <option value="1" @selected(old('is_active', '1') === '1')>نشط</option>
                        <option value="0" @selected(old('is_active') === '0')>غير نشط</option>
                    </select>
                    @error('is_active') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div class="full">
                    <label for="address">العنوان</label>
                    <textarea id="address" name="address">{{ old('address') }}</textarea>
                    @error('address') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn">حفظ المورد</button>
                <a href="{{ route('suppliers.index') }}" class="btn secondary">رجوع للموردين</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
