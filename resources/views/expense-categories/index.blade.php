@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">تصنيفات المصاريف</h1>
                <p class="text-muted mb-0">إدارة تصنيفات المصاريف التشغيلية وتفعيلها أو تعطيلها.</p>
            </div>

            <a href="{{ route('expense-categories.create') }}" class="btn btn-primary">
                تصنيف جديد
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>اسم التصنيف</th>
                            <th>Slug</th>
                            <th>الوصف</th>
                            <th>الحالة</th>
                            <th>عدد المصاريف</th>
                            <th class="text-end">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($expenseCategories as $expenseCategory)
                            <tr>
                                <td class="fw-semibold">{{ $expenseCategory->name }}</td>

                                <td>
                                    <code>{{ $expenseCategory->slug }}</code>
                                </td>

                                <td>{{ $expenseCategory->description ?: '—' }}</td>

                                <td>
                                    @if ($expenseCategory->is_active)
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-secondary">غير نشط</span>
                                    @endif
                                </td>

                                <td>{{ $expenseCategory->expenses_count }}</td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('expense-categories.edit', $expenseCategory) }}" class="btn btn-sm btn-outline-primary">
                                            تعديل
                                        </a>

                                        <form method="POST" action="{{ route('expense-categories.toggle-status', $expenseCategory) }}">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-sm {{ $expenseCategory->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $expenseCategory->is_active ? 'تعطيل' : 'تفعيل' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('expense-categories.destroy', $expenseCategory) }}" onsubmit="return confirm('هل تريد حذف هذا التصنيف؟');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    لا توجد تصنيفات مصاريف حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($expenseCategories->hasPages())
                <div class="card-footer">
                    {{ $expenseCategories->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
