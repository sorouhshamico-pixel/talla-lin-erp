@extends('layouts.admin', [
    'title' => 'فاتورة جديدة | طلة لين ERP',
    'header' => 'إنشاء فاتورة بيع'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">فاتورة بيع جديدة</h1>
            <div class="muted">
                إنشاء فاتورة بيع كمسودة دون التأثير على المخزون في هذه المرحلة.
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-bottom: 20px; border-color: #ffd0c9; color: #b42318;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('sales-invoices.store') }}">
            @csrf

            <h2 style="margin-top:0;">بيانات الفاتورة</h2>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">العميل</label>
                    <select name="customer_id" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">اختر العميل</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                {{ $customer->name }} - {{ $customer->phone ?? 'بدون جوال' }}
                            </option>
                        @endforeach
                    </select>
                </div>

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
                    <label class="muted" style="display:block;margin-bottom:8px;">رقم الفاتورة</label>
                    <input type="text" name="invoice_number" value="{{ old('invoice_number') }}"
                           placeholder="اتركه فارغًا للتوليد التلقائي"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">ملاحظات</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>
            </div>

            <h2 style="margin-top:28px;">عنصر الفاتورة</h2>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">المنتج / المتغير</label>
                    <select name="product_variant_id" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">اختر المتغير</option>
                        @foreach ($variants as $variant)
                            <option value="{{ $variant->id }}" @selected(old('product_variant_id') == $variant->id)>
                                {{ $variant->sku }} - {{ $variant->product?->name }} - {{ $variant->displayName() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">الكمية</label>
                    <input type="number" step="0.001" min="0.001" name="quantity" value="{{ old('quantity', 1) }}"
                           required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">سعر الوحدة</label>
                    <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price', 250) }}"
                           required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">الخصم</label>
                    <input type="number" step="0.01" min="0" name="discount_amount" value="{{ old('discount_amount', 0) }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">نسبة الضريبة %</label>
                    <input type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', 15) }}"
                           required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit"
                        style="background:#8b5e3c;color:#fff;border:0;padding:12px 20px;border-radius:12px;font-weight:700;cursor:pointer;">
                    إنشاء الفاتورة
                </button>

                <a href="{{ route('sales-invoices.index') }}"
                   style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:12px 20px;border-radius:12px;font-weight:700;">
                    رجوع
                </a>
            </div>
        </form>
    </div>
@endsection
