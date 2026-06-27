<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تصنيفات الإيرادات</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background:#f7f7f7; color:#222; margin:0; padding:24px; }
        .page { max-width:1100px; margin:0 auto; }
        .card { background:#fff; border:1px solid #ddd; border-radius:12px; padding:20px; margin-bottom:20px; }
        .muted { color:#666; font-size:14px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; }
        label { display:block; margin-bottom:6px; font-weight:bold; }
        input, textarea { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box; }
        textarea { min-height:90px; }
        .btn { display:inline-block; padding:9px 13px; border-radius:8px; background:#111827; color:#fff; text-decoration:none; border:0; cursor:pointer; }
        .btn.secondary { background:#f3f4f6; color:#111827; border:1px solid #d1d5db; }
        .btn.warning { background:#92400e; }
        .btn.success { background:#166534; }
        table { width:100%; border-collapse:collapse; }
        th, td { border-bottom:1px solid #eee; padding:10px; text-align:right; vertical-align:top; }
        th { background:#f9fafb; }
        .badge { display:inline-block; padding:4px 8px; border-radius:999px; font-size:12px; }
        .badge.green { background:#dcfce7; color:#166534; }
        .badge.gray { background:#e5e7eb; color:#374151; }
        .error { color:#b91c1c; font-size:13px; margin-top:5px; }
        .alert-success { padding:12px 14px; border-radius:10px; background:#dcfce7; color:#166534; margin-bottom:16px; }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
            <div>
                <h1 style="margin:0 0 8px;">تصنيفات الإيرادات</h1>
                <div class="muted">إدارة تصنيفات الإيرادات المستخدمة عند تسجيل المقبوضات.</div>
            </div>

            <a href="{{ route('revenues.index') }}" class="btn secondary">رجوع للإيرادات</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="card" data-testid="revenue-category-create-card">
        <h2 style="margin-top:0;">إضافة تصنيف إيراد</h2>

        <form method="POST" action="{{ route('revenue-categories.store') }}">
            @csrf

            <div class="grid">
                <div>
                    <label for="name">اسم التصنيف</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required>
                    @error('name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="slug">الرابط المختصر slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" placeholder="contract-revenues">
                    <div class="muted">اختياري. إذا تُرك فارغًا سيتم توليده تلقائيًا.</div>
                    @error('slug') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="margin-top:14px;">
                <label for="description">الوصف</label>
                <textarea name="description" id="description">{{ old('description') }}</textarea>
                @error('description') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div style="margin-top:16px;">
                <button type="submit" class="btn" data-testid="revenue-category-store-button">حفظ التصنيف</button>
            </div>
        </form>
    </div>

    <div class="card" data-testid="revenue-categories-table">
        <h2 style="margin-top:0;">قائمة التصنيفات</h2>

        @if ($categories->isEmpty())
            <div class="muted">لا توجد تصنيفات إيرادات حتى الآن.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>Slug</th>
                        <th>الوصف</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($categories as $category)
                        <tr data-testid="revenue-category-row-{{ $category->id }}">
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->description }}</td>
                            <td>
                                @if ($category->is_active)
                                    <span class="badge green">مفعل</span>
                                @else
                                    <span class="badge gray">معطل</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <a href="{{ route('revenue-categories.edit', $category) }}" class="btn secondary">تعديل</a>

                                    <form method="POST" action="{{ route('revenue-categories.toggle', $category) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit" class="btn {{ $category->is_active ? 'warning' : 'success' }}">
                                            {{ $category->is_active ? 'تعطيل' : 'تفعيل' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
</body>
</html>
