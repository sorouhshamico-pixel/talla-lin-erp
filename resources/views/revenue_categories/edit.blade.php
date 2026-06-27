<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل تصنيف إيراد</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background:#f7f7f7; color:#222; margin:0; padding:24px; }
        .page { max-width:900px; margin:0 auto; }
        .card { background:#fff; border:1px solid #ddd; border-radius:12px; padding:20px; margin-bottom:20px; }
        .muted { color:#666; font-size:14px; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px; }
        label { display:block; margin-bottom:6px; font-weight:bold; }
        input, textarea { width:100%; padding:10px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box; }
        textarea { min-height:100px; }
        .btn { display:inline-block; padding:10px 14px; border-radius:8px; background:#111827; color:#fff; text-decoration:none; border:0; cursor:pointer; }
        .btn.secondary { background:#f3f4f6; color:#111827; border:1px solid #d1d5db; }
        .error { color:#b91c1c; font-size:13px; margin-top:5px; }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <h1 style="margin-top:0;">تعديل تصنيف إيراد</h1>
        <div class="muted">تعديل اسم التصنيف والرابط المختصر والوصف.</div>
    </div>

    <div class="card" data-testid="revenue-category-edit-card">
        <form method="POST" action="{{ route('revenue-categories.update', $category) }}">
            @csrf
            @method('PUT')

            <div class="grid">
                <div>
                    <label for="name">اسم التصنيف</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required>
                    @error('name') <div class="error">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label for="slug">الرابط المختصر slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $category->slug) }}">
                    @error('slug') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="margin-top:14px;">
                <label for="description">الوصف</label>
                <textarea name="description" id="description">{{ old('description', $category->description) }}</textarea>
                @error('description') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="submit" class="btn">حفظ التعديل</button>
                <a href="{{ route('revenue-categories.index') }}" class="btn secondary">رجوع</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
