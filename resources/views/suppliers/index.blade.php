@extends('layouts.admin', [
    'title' => 'الموردون | طلة لين ERP',
    'header' => 'إدارة الموردين'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">الموردون</h1>
            <div class="muted">
                عرض بيانات الموردين وربطهم بفواتير الشراء.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>اسم المورد</th>
                        <th>الجوال</th>
                        <th>البريد</th>
                        <th>المدينة</th>
                        <th>عدد فواتير الشراء</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->phone ?? '-' }}</td>
                            <td>{{ $supplier->email ?? '-' }}</td>
                            <td>{{ $supplier->city ?? '-' }}</td>
                            <td>{{ $supplier->purchase_invoices_count }}</td>
                            <td>
                                @if ($supplier->is_active)
                                    <span class="badge green">نشط</span>
                                @else
                                    <span class="badge gray">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">لا يوجد موردون مسجلون.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
