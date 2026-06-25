@extends('layouts.admin', [
    'title' => 'مصروف جديد | طلة لين ERP',
    'header' => 'تسجيل مصروف'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">مصروف جديد</h1>
            <div class="muted">
                تسجيل مصروف تشغيلي وربطه بالفرع والتصنيف.
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-bottom: 20px; border-color: #ffd0c9; color: #b42318;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('expenses.store') }}">
            @csrf

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">الفرع</label>
                    <select name="branch_id" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">اختر الفرع</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">تصنيف المصروف</label>
                    <select name="expense_category_id" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">اختر التصنيف</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('expense_category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="grid-column:1 / -1;">
                    <label class="muted" style="display:block;margin-bottom:8px;">الوصف</label>
                    <input type="text" name="description" value="{{ old('description') }}" required
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">المبلغ</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">ضريبة المصروف</label>
                    <input type="number" step="0.01" min="0" name="tax_amount" value="{{ old('tax_amount', 0) }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">طريقة الدفع</label>
                    <select name="payment_method" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="cash" @selected(old('payment_method') === 'cash')>نقدًا</option>
                        <option value="card" @selected(old('payment_method') === 'card')>بطاقة</option>
                        <option value="bank_transfer" @selected(old('payment_method') === 'bank_transfer')>تحويل بنكي</option>
                        <option value="online" @selected(old('payment_method') === 'online')>دفع إلكتروني</option>
                        <option value="other" @selected(old('payment_method') === 'other')>أخرى</option>
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">تاريخ المصروف</label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">رقم المرجع</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">ملاحظات</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit"
                        style="background:#8b5e3c;color:#fff;border:0;padding:12px 20px;border-radius:12px;font-weight:700;cursor:pointer;">
                    حفظ المصروف
                </button>

                <a href="{{ route('expenses.index') }}"
                   style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:12px 20px;border-radius:12px;font-weight:700;">
                    رجوع
                </a>
            </div>
        </form>
    </div>
@endsection
