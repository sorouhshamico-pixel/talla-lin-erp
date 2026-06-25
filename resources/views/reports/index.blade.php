@extends('layouts.admin', [
    'title' => 'التقارير المالية | طلة لين ERP',
    'header' => 'التقارير المالية'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">التقارير المالية الأساسية</h1>
            <div class="muted">
                ملخص أولي للمبيعات والمشتريات والمدفوعات والمستحقات والمخزون مع إمكانية التصفية حسب الفترة والفرع.
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <form method="GET" action="{{ route('reports.index') }}">
            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;align-items:end;">
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
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit"
                            style="background:#8b5e3c;color:#fff;border:0;padding:12px 20px;border-radius:12px;font-weight:700;cursor:pointer;">
                        تطبيق الفلتر
                    </button>

                    <a href="{{ route('reports.index') }}"
                       style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:12px 20px;border-radius:12px;font-weight:700;">
                        إعادة ضبط
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">إجمالي المبيعات</div>
            <div class="metric-value" style="font-size:28px;">
                {{ number_format((float) $sales['grand_total'], 2) }} ريال
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">إجمالي المشتريات</div>
            <div class="metric-value" style="font-size:28px;">
                {{ number_format((float) $purchases['grand_total'], 2) }} ريال
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">ربح أولي قبل الضريبة</div>
            <div class="metric-value" style="font-size:28px;">
                {{ number_format((float) $profit['gross_profit_before_tax'], 2) }} ريال
            </div>
        </div>
    </div>

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">المدفوع من العملاء</div>
            <div class="metric-value" style="font-size:24px;">
                {{ number_format((float) $sales['paid_amount'], 2) }} ريال
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">المدفوع للموردين</div>
            <div class="metric-value" style="font-size:24px;">
                {{ number_format((float) $purchases['paid_amount'], 2) }} ريال
            </div>
        </div>

        <div class="metric">
            <div class="metric-label">صافي التدفق النقدي الأولي</div>
            <div class="metric-value" style="font-size:24px;">
                {{ number_format((float) $profit['net_cash_flow'], 2) }} ريال
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">تقرير المبيعات</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>عدد الفواتير المعتمدة</th>
                        <th>الإجمالي قبل الضريبة</th>
                        <th>الخصومات</th>
                        <th>الضريبة</th>
                        <th>الإجمالي النهائي</th>
                        <th>المدفوع</th>
                        <th>المستحق</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $sales['count'] }}</td>
                        <td>{{ number_format((float) $sales['subtotal'], 2) }} ريال</td>
                        <td>{{ number_format((float) $sales['discount_total'], 2) }} ريال</td>
                        <td>{{ number_format((float) $sales['tax_total'], 2) }} ريال</td>
                        <td>{{ number_format((float) $sales['grand_total'], 2) }} ريال</td>
                        <td>{{ number_format((float) $sales['paid_amount'], 2) }} ريال</td>
                        <td>{{ number_format((float) $sales['remaining_amount'], 2) }} ريال</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h2 style="margin-top:0;">تقرير المشتريات</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>عدد الفواتير المستلمة</th>
                        <th>الإجمالي قبل الضريبة</th>
                        <th>الخصومات</th>
                        <th>الضريبة</th>
                        <th>الإجمالي النهائي</th>
                        <th>المدفوع</th>
                        <th>المستحق</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $purchases['count'] }}</td>
                        <td>{{ number_format((float) $purchases['subtotal'], 2) }} ريال</td>
                        <td>{{ number_format((float) $purchases['discount_total'], 2) }} ريال</td>
                        <td>{{ number_format((float) $purchases['tax_total'], 2) }} ريال</td>
                        <td>{{ number_format((float) $purchases['grand_total'], 2) }} ريال</td>
                        <td>{{ number_format((float) $purchases['paid_amount'], 2) }} ريال</td>
                        <td>{{ number_format((float) $purchases['remaining_amount'], 2) }} ريال</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h2 style="margin-top:0;">تقرير المصاريف التشغيلية</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>عدد المصاريف</th>
                        <th>إجمالي المصاريف التشغيلية</th>
                        <th>ضريبة المصاريف</th>
                        <th>المصاريف المدفوعة</th>
                        <th>الربح بعد المصاريف</th>
                        <th>صافي التدفق بعد المصاريف</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $expenses['count'] }}</td>
                        <td>{{ number_format((float) $expenses['amount'], 2) }} ريال</td>
                        <td>{{ number_format((float) $expenses['tax_amount'], 2) }} ريال</td>
                        <td>{{ number_format((float) $expenses['paid_amount'], 2) }} ريال</td>
                        <td>{{ number_format((float) $profit['net_profit_after_expenses'], 2) }} ريال</td>
                        <td>{{ number_format((float) $profit['net_cash_flow_after_expenses'], 2) }} ريال</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h2 style="margin-top:0;">تقرير المخزون</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>عدد المنتجات</th>
                        <th>عدد المتغيرات</th>
                        <th>الكمية الفعلية</th>
                        <th>المحجوز</th>
                        <th>المتاح للبيع</th>
                        <th>قيمة التكلفة</th>
                        <th>قيمة البيع</th>
                        <th>هامش محتمل</th>
                        <th>تحت حد التنبيه</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $inventory['products_count'] }}</td>
                        <td>{{ $inventory['variants_count'] }}</td>
                        <td>{{ number_format((float) $inventory['quantity_on_hand'], 0) }}</td>
                        <td>{{ number_format((float) $inventory['quantity_reserved'], 0) }}</td>
                        <td>{{ number_format((float) $inventory['available_quantity'], 0) }}</td>
                        <td>{{ number_format((float) $inventory['cost_value'], 2) }} ريال</td>
                        <td>{{ number_format((float) $inventory['sale_value'], 2) }} ريال</td>
                        <td>{{ number_format((float) $profit['inventory_potential_margin'], 2) }} ريال</td>
                        <td>{{ $inventory['low_stock_count'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h2 style="margin-top:0;">ملاحظات التقرير</h2>

        <p>
            الربح الأولي هنا محسوب كالتالي:
            <strong>إجمالي المبيعات قبل الضريبة - إجمالي المشتريات قبل الضريبة</strong>.
        </p>

        <p>
            تقييم المخزون محسوب على أساس الكمية الفعلية الحالية مضروبة في تكلفة أو سعر بيع كل متغير.
        </p>

        <p style="margin-bottom:0;">
            هذا التقرير لا يشمل المصاريف التشغيلية أو المرتجعات أو تكلفة الشحن أو التسويات المحاسبية المتقدمة.
        </p>
    </div>
@endsection
