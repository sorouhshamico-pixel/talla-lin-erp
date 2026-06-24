@extends('layouts.admin', [
    'title' => 'المخزون | طلة لين ERP',
    'header' => 'إدارة المخزون'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">المخزون</h1>
            <div class="muted">
                عرض أرصدة المنتجات حسب المستودع والفرع.
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">أرصدة المخزون</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>المستودع</th>
                        <th>الفرع</th>
                        <th>المنتج</th>
                        <th>المتغير</th>
                        <th>المتوفر فعليًا</th>
                        <th>المحجوز</th>
                        <th>المتاح للبيع</th>
                        <th>حد التنبيه</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($balances as $balance)
                        <tr>
                            <td>{{ $balance->warehouse?->name }}</td>
                            <td>{{ $balance->branch?->name }}</td>
                            <td>{{ $balance->product?->name }}</td>
                            <td>
                                {{ $balance->variant?->sku }}
                                <br>
                                <span class="muted">{{ $balance->variant?->displayName() }}</span>
                            </td>
                            <td>{{ number_format((float) $balance->quantity_on_hand, 0) }}</td>
                            <td>{{ number_format((float) $balance->quantity_reserved, 0) }}</td>
                            <td>{{ number_format($balance->availableQuantity(), 0) }}</td>
                            <td>{{ number_format((float) $balance->reorder_level, 0) }}</td>
                            <td>
                                @if ($balance->isBelowReorderLevel())
                                    <span class="badge gray">منخفض</span>
                                @else
                                    <span class="badge green">جيد</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">لا توجد أرصدة مخزون مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2 style="margin-top: 0;">آخر حركات المخزون</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>النوع</th>
                        <th>الاتجاه</th>
                        <th>المستودع</th>
                        <th>المنتج</th>
                        <th>المتغير</th>
                        <th>الكمية</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentMovements as $movement)
                        <tr>
                            <td>{{ $movement->occurred_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $movement->displayType() }}</td>
                            <td>{{ $movement->displayDirection() }}</td>
                            <td>{{ $movement->warehouse?->name }}</td>
                            <td>{{ $movement->product?->name }}</td>
                            <td>{{ $movement->variant?->sku }}</td>
                            <td>{{ number_format((float) $movement->quantity, 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">لا توجد حركات مخزون مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
