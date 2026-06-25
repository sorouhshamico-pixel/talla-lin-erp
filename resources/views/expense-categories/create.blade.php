@extends('layouts.admin', [
    'title' => 'تصنيف مصروف جديد | طلة لين ERP',
    'header' => 'إضافة تصنيف مصروف'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">تصنيف مصروف جديد</h1>
            <div class="muted">
                إضافة تصنيف لاستخدامه عند تسجيل المصاريف التشغيلية.
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-bottom: 20px; border-color: #ffd0c9; color: #b42318;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('expense-categories.store') }}">
            @csrf

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">اسم التصنيف</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="مثال: تسويق وإعلانات"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" required dir="ltr"
                           placeholder="marketing-ads"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                    <div class="muted" style="font-size:13px;margin-top:6px;">
                        استخدم حروف إنجليزية وأرقام وشرطة فقط بدون مسافات.
                    </div>
                </div>

                <div style="grid-column:1 / -1;">
                    <label class="muted" style="display:block;margin-bottom:8px;">الوصف</label>
                    <input type="text" name="description" value="{{ old('description') }}"
                           placeholder="وصف مختصر للتصنيف"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit"
                        style="background:#8b5e3c;color:#fff;border:0;padding:12px 20px;border-radius:12px;font-weight:700;cursor:pointer;">
                    حفظ التصنيف
                </button>

                <a href="{{ route('expense-categories.index') }}"
                   style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:12px 20px;border-radius:12px;font-weight:700;">
                    رجوع
                </a>
            </div>
        </form>
    </div>
@endsection
