@extends('layouts.app', ['title' => 'لوحة التحكم | طلة لين ERP'])

@section('content')
    <header class="topbar">
        <div class="brand">طلة لين ERP</div>

        <div class="topbar-actions">
            <span>{{ $user->name }}</span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout-button" type="submit">تسجيل الخروج</button>
            </form>
        </div>
    </header>

    <main class="container">
        <div class="card" style="margin-bottom: 20px;">
            <h1 style="margin-top: 0;">لوحة التحكم</h1>

            <p class="muted" style="margin-bottom: 0;">
                مرحبًا {{ $user->name }}، هذه أول لوحة تحكم لنظام طلة لين.
            </p>
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
    </main>
@endsection
