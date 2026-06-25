@extends('layouts.admin', [
    'title' => 'تصنيفات المصاريف | طلة لين ERP',
    'header' => 'تصنيفات المصاريف'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">تصنيفات المصاريف</h1>
            <div class="muted">
                إدارة التصنيفات المستخدمة عند تسجيل المصاريف التشغيلية.
            </div>
        </div>

        <div>
            <a href="{{ route('expense-categories.create') }}"
               style="display:inline-block;background:#8b5e3c;color:#fff;padding:11px 16px;border-radius:12px;font-weight:700;">
                تصنيف جديد
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="card" style="margin-bottom: 20px; border-color: #cbe7d5; color: #157347;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>اسم التصنيف</th>
                        <th>Slug</th>
                        <th>الوصف</th>
                        <th>عدد المصاريف</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td dir="ltr">{{ $category->slug }}</td>
                            <td>{{ $category->description ?? '-' }}</td>
                            <td>{{ $category->expenses_count }}</td>
                            <td>
                                @if ($category->is_active)
                                    <span class="badge">نشط</span>
                                @else
                                    <span class="badge muted">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">لا توجد تصنيفات مصاريف.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
