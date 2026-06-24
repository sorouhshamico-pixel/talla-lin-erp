@extends('layouts.admin', [
    'title' => 'الفروع | طلة لين ERP',
    'header' => 'إدارة الفروع'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">الفروع</h1>
            <div class="muted">
                عرض الفروع وقنوات البيع المرتبطة بمتجر طلة لين.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>اسم الفرع</th>
                        <th>النوع</th>
                        <th>المدينة</th>
                        <th>عدد المستودعات</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($branches as $branch)
                        <tr>
                            <td>{{ $branch->code }}</td>
                            <td>
                                {{ $branch->name }}

                                @if ($branch->is_main)
                                    <span class="badge">رئيسي</span>
                                @endif
                            </td>
                            <td>{{ $branch->type }}</td>
                            <td>{{ $branch->city ?? '-' }}</td>
                            <td>{{ $branch->warehouses_count }}</td>
                            <td>
                                @if ($branch->is_active)
                                    <span class="badge green">نشط</span>
                                @else
                                    <span class="badge gray">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">لا توجد فروع مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
