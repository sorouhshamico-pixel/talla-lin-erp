@extends('layouts.admin', [
    'title' => 'تفاصيل الفاتورة | طلة لين ERP',
    'header' => 'تفاصيل فاتورة البيع'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">فاتورة بيع</h1>
            <div class="muted">
                رقم الفاتورة:
                <span dir="ltr">{{ $invoice->invoice_number }}</span>
            </div>
        </div>

        <div>
            @if ($invoice->status === 'issued' && (float) $invoice->remaining_amount > 0)
                <a href="{{ route('sales-invoices.payments.create', $invoice) }}"
                   style="display:inline-block;background:#157347;color:#fff;padding:11px 16px;border-radius:12px;font-weight:700;">
                    تسجيل دفعة
                </a>
            @endif

            <a href="{{ route('sales-invoices.index') }}"
               style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:11px 16px;border-radius:12px;font-weight:700;">
                رجوع للفواتير
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="card" style="margin-bottom: 20px; border-color: #cbe7d5; color: #157347;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="card" style="margin-bottom: 20px; border-color: #ffd0c9; color: #b42318;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">العميل</div>
            <div class="metric-value" style="font-size:22px;">
                {{ $invoice->customer?->name }}
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">الفرع</div>
            <div class="metric-value" style="font-size:22px;">
                {{ $invoice->branch?->name }}
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">الإجمالي</div>
            <div class="metric-value" style="font-size:22px;">
                {{ number_format((float) $invoice->grand_total, 2) }} ريال
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">عناصر الفاتورة</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الوصف</th>
                        <th>المتغير</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>الخصم</th>
                        <th>الضريبة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td dir="ltr">{{ $item->variant?->sku }}</td>
                            <td>{{ number_format((float) $item->quantity, 0) }}</td>
                            <td>{{ number_format((float) $item->unit_price, 2) }} ريال</td>
                            <td>{{ number_format((float) $item->discount_amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $item->tax_amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $item->line_total, 2) }} ريال</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h2 style="margin-top:0;">ملخص الفاتورة</h2>

        <p><strong>الإجمالي قبل الضريبة:</strong> {{ number_format((float) $invoice->subtotal, 2) }} ريال</p>
        <p><strong>إجمالي الخصم:</strong> {{ number_format((float) $invoice->discount_total, 2) }} ريال</p>
        <p><strong>إجمالي الضريبة:</strong> {{ number_format((float) $invoice->tax_total, 2) }} ريال</p>
        <p><strong>الإجمالي النهائي:</strong> {{ number_format((float) $invoice->grand_total, 2) }} ريال</p>
        <p><strong>حالة السداد:</strong> {{ $invoice->displayPaymentStatus() }}</p>
        <p><strong>المدفوع:</strong> {{ number_format((float) $invoice->paid_amount, 2) }} ريال</p>
        <p style="margin-bottom:0;"><strong>المتبقي:</strong> {{ number_format((float) $invoice->remaining_amount, 2) }} ريال</p>
    </div>

    <div class="card" style="margin-top:20px;">
        <h2 style="margin-top:0;">سجل الدفعات</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>المبلغ</th>
                        <th>طريقة الدفع</th>
                        <th>المرجع</th>
                        <th>الموظف</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ number_format((float) $payment->amount, 2) }} ريال</td>
                            <td>{{ $payment->displayMethod() }}</td>
                            <td dir="ltr">{{ $payment->reference_number ?? '-' }}</td>
                            <td>{{ $payment->user?->name ?? '-' }}</td>
                            <td>{{ $payment->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">لا توجد دفعات مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card" data-testid="sales-invoice-collection-notes-card" style="margin-top:20px;">
        <h2 style="margin-top:0;">ملاحظات متابعة التحصيل</h2>
        <div class="muted">أضف ملاحظات مرتبطة بمتابعة تحصيل هذه الفاتورة.</div>

        <form method="POST"
              action="{{ route('sales-invoices.collection-notes.store', $invoice) }}"
              data-testid="sales-invoice-collection-note-form"
              style="margin-top:16px;">
            @csrf

            <div class="grid">
                <div class="field">
                    <label for="collection_note" class="label">ملاحظة التحصيل</label>
                    <textarea id="collection_note"
                              name="note"
                              required
                              data-testid="sales-invoice-collection-note-input"
                              style="width:100%;min-height:90px;">{{ old('note') }}</textarea>

                    @error('note')
                        <div class="muted">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="collection_follow_up_at" class="label">تاريخ المتابعة</label>
                    <input id="collection_follow_up_at"
                           type="date"
                           name="follow_up_at"
                           value="{{ old('follow_up_at') }}"
                           data-testid="sales-invoice-collection-follow-up-input">

                    @error('follow_up_at')
                        <div class="muted">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="margin-top:14px;">
                <button type="submit" class="btn" data-testid="sales-invoice-collection-note-submit">
                    إضافة ملاحظة تحصيل
                </button>
            </div>
        </form>

        <div style="margin-top:18px;" data-testid="sales-invoice-collection-notes-list">
            @forelse ($invoice->collectionNotes as $collectionNote)
                <div class="field" data-testid="sales-invoice-collection-note-row" style="margin-bottom:10px;">
                    <div class="label">
                        المتابعة:
                        {{ $collectionNote->follow_up_at?->format('Y-m-d') ?: '-' }}
                        —
                        المستخدم:
                        {{ $collectionNote->user?->name ?: '-' }}
                    </div>
                    <div class="value">{{ $collectionNote->note }}</div>
                </div>
            @empty
                <div class="muted" data-testid="sales-invoice-collection-notes-empty">لا توجد ملاحظات تحصيل بعد.</div>
            @endforelse
        </div>
    </div>

@endsection
