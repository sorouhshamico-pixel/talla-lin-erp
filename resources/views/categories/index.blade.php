@extends('layouts.admin', [
    'title' => 'التصنيفات | طلة لين ERP',
    'header' => 'إدارة التصنيفات'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">التصنيفات</h1>
            <div class="muted">
                عرض تصنيفات منتجات متجر طلة لين.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الرابط المختصر</th>
                        <th>عدد المنتجات</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>
                                @if ($category->is_active)
                                    <span class="badge green">نشط</span>
                                @else
                                    <span class="badge gray">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">لا توجد تصنيفات مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
