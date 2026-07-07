<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طباعة لوحة أعمار الذمم</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            color: #111827;
            background: #ffffff;
            margin: 24px;
            line-height: 1.6;
            font-size: 13px;
        }

        .actions {
            margin-bottom: 20px;
        }

        .button {
            border: 1px solid #111827;
            border-radius: 6px;
            padding: 8px 14px;
            background: #ffffff;
            color: #111827;
            cursor: pointer;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        h2 {
            margin: 18px 0 10px;
            font-size: 16px;
        }

        .meta {
            color: #4b5563;
            margin-top: 3px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
        }

        .label {
            color: #6b7280;
            font-size: 12px;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 7px;
            text-align: right;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                margin: 0;
            }

            .summary {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="button" onclick="window.print()" data-testid="aging-dashboard-print-button">طباعة</button>
    </div>

    <div class="header">
        <h1>لوحة أعمار الذمم</h1>
        <div class="meta">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</div>
    </div>

    <h2>ملخص ذمم العملاء</h2>
    <div class="summary" data-testid="aging-dashboard-print-customer-summary">
        <div class="card">
            <div class="label">عدد العملاء</div>
            <div class="value">{{ $customerSummary['customers_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">فواتير العملاء المفتوحة</div>
            <div class="value">{{ $customerSummary['invoice_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">إجمالي ذمم العملاء المفتوحة</div>
            <div class="value">{{ number_format((float) $customerSummary['remaining_total'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">إجمالي المتأخر على العملاء</div>
            <div class="value">{{ number_format((float) $customerSummary['overdue_total'], 2) }}</div>
        </div>
    </div>

    <h2>ملخص ذمم الموردين</h2>
    <div class="summary" data-testid="aging-dashboard-print-supplier-summary">
        <div class="card">
            <div class="label">عدد الموردين</div>
            <div class="value">{{ $supplierSummary['suppliers_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">فواتير الموردين المفتوحة</div>
            <div class="value">{{ $supplierSummary['invoice_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">إجمالي ذمم الموردين المفتوحة</div>
            <div class="value">{{ number_format((float) $supplierSummary['remaining_total'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">إجمالي المتأخر للموردين</div>
            <div class="value">{{ number_format((float) $supplierSummary['overdue_total'], 2) }}</div>
        </div>
    </div>

    <h2>صافي الذمم</h2>
    <div class="summary" data-testid="aging-dashboard-print-net-summary">
        <div class="card">
            <div class="label">صافي الذمم المفتوحة</div>
            <div class="value">{{ number_format((float) $netSummary['net_open_total'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">حالة صافي الذمم</div>
            <div class="value">{{ $netSummary['position_label'] }}</div>
        </div>
        <div class="card">
            <div class="label">صافي المتأخرات</div>
            <div class="value">{{ number_format((float) $netSummary['net_overdue_total'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">حالة صافي المتأخرات</div>
            <div class="value">{{ $netSummary['overdue_position_label'] }}</div>
        </div>
    </div>

    <h2>مقارنة شرائح الأعمار</h2>
    <table data-testid="aging-dashboard-print-bucket-comparison">
        <thead>
            <tr>
                <th>شريحة العمر</th>
                <th>ذمم العملاء</th>
                <th>ذمم الموردين</th>
                <th>صافي الفرق</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bucketComparison as $bucket)
                <tr>
                    <td>{{ $bucket['label'] }}</td>
                    <td>{{ number_format((float) $bucket['customer_total'], 2) }}</td>
                    <td>{{ number_format((float) $bucket['supplier_total'], 2) }}</td>
                    <td>{{ number_format((float) $bucket['net_total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
