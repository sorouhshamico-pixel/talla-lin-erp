@extends('layouts.admin', [
    'title' => 'المنتجات | طلة لين ERP',
    'header' => 'إدارة المنتجات'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">المنتجات</h1>
            <div class="muted">
                عرض المنتجات الأساسية ومتغيراتها مثل اللون والمقاس.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>اسم المنتج</th>
                        <th>التصنيف</th>
                        <th>النوع</th>
                        <th>سعر البيع</th>
                        <th>التكلفة</th>
                        <th>المتغيرات</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?? '-' }}</td>
                            <td>{{ $product->displayType() }}</td>
                            <td>{{ number_format((float) $product->sale_price, 2) }} ريال</td>
                            <td>{{ number_format((float) $product->cost_price, 2) }} ريال</td>
                            <td>{{ $product->variants_count }}</td>
                            <td>
                                @if ($product->is_active)
                                    <span class="badge green">نشط</span>
                                @else
                                    <span class="badge gray">غير نشط</span>
                                @endif
                            </td>
                        </tr>

                        @if ($product->variants->isNotEmpty())
                            <tr>
                                <td colspan="8">
                                    <strong>المتغيرات:</strong>
                                    {{ $product->variants->map(fn ($variant) => $variant->sku . ' / ' . ($variant->displayName() ?: 'بدون وصف'))->join('، ') }}
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8">لا توجد منتجات مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
