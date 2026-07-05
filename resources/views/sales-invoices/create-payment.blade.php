@extends('layouts.admin', [
    'title' => 'تسجيل دفعة | طلة لين ERP',
    'header' => 'تسجيل دفعة'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">تسجيل دفعة</h1>
            <div class="muted">
                الفاتورة:
                <span dir="ltr">{{ $invoice->invoice_number }}</span>
                —
                العميل: {{ $invoice->customer?->name }}
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-bottom: 20px; border-color: #ffd0c9; color: #b42318;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">الإجمالي</div>
            <div class="metric-value" style="font-size:22px;">
                {{ number_format((float) $invoice->grand_total, 2) }} ريال
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">المدفوع</div>
            <div class="metric-value" style="font-size:22px;">
                {{ number_format((float) $invoice->paid_amount, 2) }} ريال
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">المتبقي</div>
            <div class="metric-value" style="font-size:22px;">
                {{ number_format((float) $invoice->remaining_amount, 2) }} ريال
            </div>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('sales-invoices.payments.store', $invoice) }}">
            @csrf

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">مبلغ الدفعة</label>
                    <input type="number" step="0.01" min="0.01" max="{{ $invoice->remaining_amount }}" name="amount" value="{{ old('amount', $invoice->remaining_amount) }}"
                           required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                    <div class="muted" style="margin-top:6px;font-size:13px;">
                        لا يمكن أن تتجاوز الدفعة المبلغ المتبقي: {{ number_format((float) $invoice->remaining_amount, 2) }} ريال
                    </div>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">طريقة الدفع</label>
                    <select name="method" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="cash" @selected(old('method') === 'cash')>نقدًا</option>
                        <option value="card" @selected(old('method') === 'card')>بطاقة</option>
                        <option value="bank_transfer" @selected(old('method') === 'bank_transfer')>تحويل بنكي</option>
                        <option value="online" @selected(old('method') === 'online')>دفع إلكتروني</option>
                        <option value="other" @selected(old('method') === 'other')>أخرى</option>
                    </select>
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
                    حفظ الدفعة
                </button>

                <a href="{{ route('sales-invoices.show', $invoice) }}"
                   style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:12px 20px;border-radius:12px;font-weight:700;">
                    رجوع
                </a>
            </div>
        </form>
    </div>
@endsection
