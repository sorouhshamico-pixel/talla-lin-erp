@extends('layouts.admin', [
    'title' => 'التقارير المالية | طلة لين ERP',
    'header' => 'التقارير المالية'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">التقارير المالية الأساسية</h1>
            <div class="muted">
                ملخص أولي للمبيعات والمشتريات والمدفوعات والمستحقات.
            </div>
        </div>
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
        <h2 style="margin-top:0;">ملاحظات التقرير</h2>

        <p>
            الربح الأولي هنا محسوب كالتالي:
            <strong>إجمالي المبيعات قبل الضريبة - إجمالي المشتريات قبل الضريبة</strong>.
        </p>

        <p style="margin-bottom:0;">
            هذا التقرير لا يشمل المصاريف التشغيلية أو المرتجعات أو تكلفة الشحن أو التسويات المحاسبية المتقدمة.
        </p>
    </div>
@endsection
