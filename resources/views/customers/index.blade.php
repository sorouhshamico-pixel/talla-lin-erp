@extends('layouts.admin', [
    'title' => 'العملاء | طلة لين ERP',
    'header' => 'إدارة العملاء'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">العملاء</h1>
            <div class="muted">
                عرض بيانات العملاء وربطهم بفواتير البيع.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>اسم العميل</th>
                        <th>الجوال</th>
                        <th>البريد</th>
                        <th>المدينة</th>
                        <th>عدد الفواتير</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone ?? '-' }}</td>
                            <td>{{ $customer->email ?? '-' }}</td>
                            <td>{{ $customer->city ?? '-' }}</td>
                            <td>{{ $customer->sales_invoices_count }}</td>
                            <td>
                                @if ($customer->is_active)
                                    <span class="badge green">نشط</span>
                                @else
                                    <span class="badge gray">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">لا يوجد عملاء مسجلون.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
