@extends('layouts.app')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">تعديل مصروف تشغيلي</h1>
            <div class="muted">
                تحديث بيانات المصروف التشغيلي مع إمكانية استبدال أو حذف المرفق الحالي.
            </div>
        </div>

        <a href="{{ route('expenses.index') }}"
           style="background:#eee4dc;color:#5d3b25;padding:12px 18px;border-radius:12px;font-weight:700;">
            رجوع
        </a>
    </div>

    @if (session('success'))
        <div class="card" style="margin-bottom:20px;border-color:#b7e4c7;background:#f0fff4;color:#157347;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="card" style="margin-bottom:20px;border-color:#f1b5b5;background:#fff5f5;color:#b42318;">
            <strong>يرجى مراجعة الأخطاء التالية:</strong>
            <ul style="margin-bottom:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($expense->hasAttachment())
        <div class="card" style="margin-bottom:20px;">
            <h2 style="margin-top:0;">المرفق الحالي</h2>

            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <a href="{{ $expense->attachmentUrl() }}" target="_blank"
                   style="background:#eee4dc;color:#5d3b25;padding:10px 14px;border-radius:10px;font-weight:700;">
                    عرض المرفق الحالي
                </a>

                <span class="muted">{{ $expense->attachment_original_name }}</span>

                <form method="POST" action="{{ route('expenses.attachment.destroy', $expense) }}" onsubmit="return confirm('هل تريد حذف مرفق هذا المصروف؟');">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            style="background:#b42318;color:#fff;border:0;padding:10px 14px;border-radius:10px;font-weight:700;cursor:pointer;">
                        حذف المرفق
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('expenses.update', $expense) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">الفرع</label>
                    <select name="branch_id" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $expense->branch_id) === (string) $branch->id)>
                                {{ $branch->name_ar ?? $branch->name ?? $branch->name_en ?? 'فرع #' . $branch->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">تصنيف المصروف</label>
                    <select name="expense_category_id" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $expense->expense_category_id) === (string) $category->id)>
                                {{ $category->name }}{{ $category->is_active ? '' : ' - غير نشط' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="grid-column:1 / -1;">
                    <label class="muted" style="display:block;margin-bottom:8px;">الوصف</label>
                    <input type="text" name="description" value="{{ old('description', $expense->description) }}" required
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">المبلغ</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $expense->amount) }}" required
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">الضريبة</label>
                    <input type="number" step="0.01" min="0" name="tax_amount" value="{{ old('tax_amount', $expense->tax_amount) }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">طريقة الدفع</label>
                    <select name="payment_method" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', $expense->payment_method) === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">حالة الدفع</label>
                    <select name="is_paid" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="1" @selected((string) old('is_paid', $expense->is_paid ? '1' : '0') === '1')>مدفوع</option>
                        <option value="0" @selected((string) old('is_paid', $expense->is_paid ? '1' : '0') === '0')>غير مدفوع</option>
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">تاريخ المصروف</label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" required
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">رقم المرجع</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number', $expense->reference_number) }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div style="grid-column:1 / -1;">
                    <label class="muted" style="display:block;margin-bottom:8px;">استبدال المرفق</label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;background:#fff;">
                    <div class="muted" style="font-size:12px;margin-top:6px;">
                        اترك الحقل فارغًا للاحتفاظ بالمرفق الحالي. الملفات المسموحة: PDF, JPG, JPEG, PNG, WEBP — الحد الأقصى 4MB.
                    </div>
                </div>

                <div style="grid-column:1 / -1;">
                    <label class="muted" style="display:block;margin-bottom:8px;">ملاحظات</label>
                    <textarea name="notes" rows="4"
                              style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">{{ old('notes', $expense->notes) }}</textarea>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:22px;">
                <a href="{{ route('expenses.index') }}"
                   style="background:#eee4dc;color:#5d3b25;padding:12px 18px;border-radius:12px;font-weight:700;">
                    إلغاء
                </a>

                <button type="submit"
                        style="background:#8b5e3c;color:#fff;border:0;padding:12px 22px;border-radius:12px;font-weight:700;cursor:pointer;">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
@endsection
