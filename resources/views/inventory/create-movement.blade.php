@extends('layouts.admin', [
    'title' => 'حركة مخزون جديدة | طلة لين ERP',
    'header' => 'حركة مخزون جديدة'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">حركة مخزون جديدة</h1>
            <div class="muted">
                إضافة كمية أو إخراج كمية من المخزون مع تسجيل الحركة.
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="card" style="margin-bottom: 20px; border-color: #ffd0c9; color: #b42318;">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('inventory.movements.store') }}">
            @csrf

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">المستودع</label>
                    <select name="warehouse_id" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">اختر المستودع</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>
                                {{ $warehouse->name }} - {{ $warehouse->branch?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

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
                    <label class="muted" style="display:block;margin-bottom:8px;">نوع الحركة</label>
                    <select name="type" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="purchase" @selected(old('type') === 'purchase')>شراء / إضافة كمية</option>
                        <option value="adjustment" @selected(old('type') === 'adjustment')>تسوية</option>
                        <option value="damage" @selected(old('type') === 'damage')>تالف</option>
                        <option value="return" @selected(old('type') === 'return')>مرتجع</option>
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">اتجاه الحركة</label>
                    <select name="direction" required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="in" @selected(old('direction') === 'in')>داخل للمخزون</option>
                        <option value="out" @selected(old('direction') === 'out')>خارج من المخزون</option>
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">الكمية</label>
                    <input type="number" step="0.001" min="0.001" name="quantity" value="{{ old('quantity') }}"
                           required style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">تكلفة الوحدة</label>
                    <input type="number" step="0.01" min="0" name="unit_cost" value="{{ old('unit_cost') }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">رقم المرجع</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                           placeholder="مثال: PURCHASE-001"
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
                    حفظ الحركة
                </button>

                <a href="{{ route('inventory.index') }}"
                   style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:12px 20px;border-radius:12px;font-weight:700;">
                    رجوع
                </a>
            </div>
        </form>
    </div>
@endsection
