@extends('layouts.admin', [
    'title' => 'المستودعات | طلة لين ERP',
    'header' => 'إدارة المستودعات'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">المستودعات</h1>
            <div class="muted">
                عرض المستودعات وربط كل مستودع بالفرع التابع له.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>اسم المستودع</th>
                        <th>النوع</th>
                        <th>الفرع</th>
                        <th>المدينة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($warehouses as $warehouse)
                        <tr>
                            <td>{{ $warehouse->code }}</td>
                            <td>
                                {{ $warehouse->name }}

                                @if ($warehouse->is_main)
                                    <span class="badge">رئيسي</span>
                                @endif
                            </td>
                            <td>{{ $warehouse->type }}</td>
                            <td>{{ $warehouse->branch?->name ?? '-' }}</td>
                            <td>{{ $warehouse->city ?? '-' }}</td>
                            <td>
                                @if ($warehouse->is_active)
                                    <span class="badge green">نشط</span>
                                @else
                                    <span class="badge gray">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">لا توجد مستودعات مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
