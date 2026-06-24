@extends('layouts.admin', [
    'title' => 'لوحة التحكم | طلة لين ERP',
    'header' => 'لوحة التحكم'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">لوحة التحكم</h1>
            <div class="muted">
                مرحبًا {{ $user->name }}، هذه لوحة التحكم الأولى لنظام طلة لين.
            </div>
        </div>
    </div>

    <div class="grid">
        <div class="metric">
            <div class="metric-label">الشركة</div>
            <div class="metric-value" style="font-size: 22px;">
                {{ $company?->name_ar ?? 'غير محدد' }}
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">عدد الفروع</div>
            <div class="metric-value">
                {{ $company?->branches_count ?? 0 }}
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">عدد المستودعات</div>
            <div class="metric-value">
                {{ $company?->warehouses_count ?? 0 }}
            </div>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2 style="margin-top: 0;">بيانات المستخدم الحالي</h2>

        <p>
            <strong>الدور:</strong>
            {{ $user->role }}
        </p>

        <p>
            <strong>الفرع الحالي:</strong>
            {{ $user->currentBranch?->name ?? 'غير محدد' }}
        </p>

        <p style="margin-bottom: 0;">
            <strong>الفروع المسموحة:</strong>
            {{ $user->branches->pluck('name')->join('، ') }}
        </p>
    </div>
@endsection
