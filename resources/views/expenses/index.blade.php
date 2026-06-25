@extends('layouts.admin', [
    'title' => 'المصاريف التشغيلية | طلة لين ERP',
    'header' => 'إدارة المصاريف'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">المصاريف التشغيلية</h1>
            <div class="muted">
                عرض المصاريف التشغيلية وربطها بالفروع والتصنيفات.
            </div>
        </div>

        <div>
            <a href="{{ route('expenses.create') }}"
               style="display:inline-block;background:#8b5e3c;color:#fff;padding:11px 16px;border-radius:12px;font-weight:700;">
                مصروف جديد
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
                        <th>الكود</th>
                        <th>الوصف</th>
                        <th>التصنيف</th>
                        <th>الفرع</th>
                        <th>المبلغ</th>
                        <th>الضريبة</th>
                        <th>طريقة الدفع</th>
                        <th>التاريخ</th>
                        <th>المسجل</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td dir="ltr">{{ $expense->code }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>{{ $expense->category?->name }}</td>
                            <td>{{ $expense->branch?->name }}</td>
                            <td>{{ number_format((float) $expense->amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $expense->tax_amount, 2) }} ريال</td>
                            <td>{{ $expense->displayPaymentMethod() }}</td>
                            <td>{{ $expense->expense_date?->format('Y-m-d') }}</td>
                            <td>{{ $expense->user?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">لا توجد مصاريف مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
