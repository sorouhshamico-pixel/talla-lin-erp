@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">المصاريف التشغيلية</h1>
            <div class="muted">
                متابعة المصاريف التشغيلية مع الفلترة حسب الفترة والفرع والتصنيف وطريقة الدفع وحالة الدفع.
            </div>
        </div>

        <a href="{{ route('expenses.create') }}"
           style="background:#8b5e3c;color:#fff;padding:12px 18px;border-radius:12px;font-weight:700;">
            مصروف جديد
        </a>
    </div>

    @if (session('success'))
        <div class="card" style="margin-bottom:20px;border-color:#b7e4c7;background:#f0fff4;color:#157347;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card" style="margin-bottom:20px;">
        <form method="GET" action="{{ route('expenses.index') }}">
            <div style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:16px;align-items:end;">
                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">من تاريخ</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">إلى تاريخ</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">الفرع</label>
                    <select name="branch_id" style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">كل الفروع</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $filters['branch_id'] === (string) $branch->id)>
                                {{ $branch->name_ar ?? $branch->name ?? $branch->name_en ?? 'فرع #' . $branch->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">تصنيف المصروف</label>
                    <select name="expense_category_id" style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">كل التصنيفات</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $filters['expense_category_id'] === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">طريقة الدفع</label>
                    <select name="payment_method" style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">كل طرق الدفع</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_method'] === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">حالة الدفع</label>
                    <select name="payment_status" style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">كل الحالات</option>
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit"
                            style="background:#8b5e3c;color:#fff;border:0;padding:12px 20px;border-radius:12px;font-weight:700;cursor:pointer;">
                        تطبيق الفلتر
                    </button>

                    <a href="{{ route('expenses.index') }}"
                       style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:12px 20px;border-radius:12px;font-weight:700;">
                        إعادة ضبط
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">إجمالي نتائج الفلتر</div>
            <div class="metric-value">{{ $expenseTotals['count'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-label">إجمالي المصاريف</div>
            <div class="metric-value">{{ number_format($expenseTotals['amount'], 2) }} ريال</div>
        </div>

        <div class="metric">
            <div class="metric-label">إجمالي ضريبة المصاريف</div>
            <div class="metric-value">{{ number_format($expenseTotals['tax_amount'], 2) }} ريال</div>
        </div>
    </div>

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">إجمالي المصاريف المدفوعة</div>
            <div class="metric-value">{{ number_format($expenseTotals['paid_amount'], 2) }} ريال</div>
        </div>

        <div class="metric">
            <div class="metric-label">إجمالي المصاريف غير المدفوعة</div>
            <div class="metric-value">{{ number_format($expenseTotals['unpaid_amount'], 2) }} ريال</div>
        </div>

        <div class="metric">
            <div class="metric-label">حالة الدفع المحددة</div>
            <div class="metric-value" style="font-size:18px;">
                {{ $filters['payment_status'] ? ($paymentStatuses[$filters['payment_status']] ?? $filters['payment_status']) : 'كل الحالات' }}
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">قائمة المصاريف</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>التاريخ</th>
                        <th>الوصف</th>
                        <th>الفرع</th>
                        <th>التصنيف</th>
                        <th>طريقة الدفع</th>
                        <th>المبلغ</th>
                        <th>الضريبة</th>
                        <th>حالة الدفع</th>
                        <th>المرفق</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->code }}</td>
                            <td>{{ $expense->expense_date?->format('Y-m-d') }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>{{ $expense->branch?->name_ar ?? $expense->branch?->name ?? $expense->branch?->name_en ?? '—' }}</td>
                            <td>{{ $expense->category?->name ?? '—' }}</td>
                            <td>{{ $expense->displayPaymentMethod() }}</td>
                            <td>{{ number_format((float) $expense->amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $expense->tax_amount, 2) }} ريال</td>
                            <td>
                                @if ($expense->is_paid)
                                    <span class="badge green">مدفوع</span>
                                @else
                                    <span class="badge gray">غير مدفوع</span>
                                @endif
                            </td>
                            <td>
                                @if ($expense->hasAttachment())
                                    <a href="{{ $expense->attachmentUrl() }}" target="_blank"
                                       style="color:#5d3b25;font-weight:700;">
                                        عرض المرفق
                                    </a>
                                    <div class="muted" style="font-size:12px;margin-top:4px;">
                                        {{ $expense->attachment_original_name }}
                                    </div>
                                @else
                                    <span class="muted">لا يوجد</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <a href="{{ route('expenses.edit', $expense) }}"
                                       style="background:#eee4dc;color:#5d3b25;padding:8px 12px;border-radius:10px;font-weight:700;">
                                        تعديل
                                    </a>

                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('هل تريد حذف هذا المصروف؟');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                style="background:#b42318;color:#fff;border:0;padding:8px 12px;border-radius:10px;font-weight:700;cursor:pointer;">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">لا توجد مصاريف ضمن الفلاتر الحالية.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
