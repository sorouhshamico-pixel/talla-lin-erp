@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">تعديل تصنيف مصروف</h1>
                <p class="text-muted mb-0">تحديث الاسم والـ slug والوصف وحالة التصنيف.</p>
            </div>

            <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary">
                رجوع
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('expense-categories.update', $expenseCategory) }}">
                    @csrf
                    @method('PATCH')

                    <input type="hidden" name="is_active" value="0">

                    <div class="mb-3">
                        <label for="name" class="form-label">اسم التصنيف</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $expenseCategory->name) }}"
                            class="form-control @error('name') is-invalid @enderror"
                            required
                        >

                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input
                            type="text"
                            id="slug"
                            name="slug"
                            value="{{ old('slug', $expenseCategory->slug) }}"
                            class="form-control @error('slug') is-invalid @enderror"
                            required
                        >

                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <div class="form-text">
                            يجب أن يكون فريدًا وغير مستخدم في تصنيف آخر.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">الوصف</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            class="form-control @error('description') is-invalid @enderror"
                        >{{ old('description', $expenseCategory->description) }}</textarea>

                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input
                            type="checkbox"
                            id="is_active"
                            name="is_active"
                            value="1"
                            class="form-check-input"
                            @checked(old('is_active', $expenseCategory->is_active))
                        >

                        <label for="is_active" class="form-check-label">
                            التصنيف نشط
                        </label>

                        <div class="form-text">
                            التصنيفات غير النشطة لا تظهر في صفحة إضافة مصروف ولا يمكن استخدامها عند إرسال النموذج يدويًا.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary">
                            إلغاء
                        </a>

                        <button type="submit" class="btn btn-primary">
                            حفظ التعديلات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
