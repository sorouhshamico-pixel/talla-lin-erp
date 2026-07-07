<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طباعة أكبر المتأخرات</title>
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
            margin: 22px 0 10px;
            font-size: 16px;
        }

        .meta {
            color: #4b5563;
            margin-top: 3px;
        }

        .empty {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
            color: #4b5563;
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
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="button" onclick="window.print()" data-testid="main-dashboard-top-overdue-print-button">طباعة</button>
    </div>

    <div class="header">
        <h1>أكبر المتأخرات في لوحة التحكم</h1>
        <div class="meta">تاريخ التقرير: {{ $reportDate->format('Y-m-d H:i:s') }}</div>
        <div class="meta" data-testid="main-dashboard-top-overdue-print-branch-label">فلتر الفرع: {{ $branchLabel }}</div>
        <div class="meta" data-testid="main-dashboard-top-overdue-print-as-of-date-label">تاريخ الاحتساب: {{ $asOfDateLabel }}</div>
    </div>

    <h2>أكبر العملاء المتأخرين</h2>

    @if (empty($topOverdueCustomers))
        <div class="empty" data-testid="main-dashboard-top-overdue-customers-print-empty">
            لا توجد فواتير عملاء متأخرة حاليًا.
        </div>
    @else
        <table data-testid="main-dashboard-top-overdue-customers-print-table">
            <thead>
                <tr>
                    <th>العميل</th>
                    <th>عدد الفواتير</th>
                    <th>إجمالي المتأخر</th>
                    <th>أقدم استحقاق</th>
                    <th>أقصى تأخير</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($topOverdueCustomers as $row)
                    <tr>
                        <td>{{ $row['customer_name'] }}</td>
                        <td>{{ $row['invoice_count'] }}</td>
                        <td>{{ number_format((float) $row['overdue_total'], 2) }} ريال</td>
                        <td>{{ $row['oldest_due_at'] ?? '' }}</td>
                        <td>{{ $row['max_days_overdue'] === null ? '' : $row['max_days_overdue'] . ' يوم' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>أكبر الموردين المتأخرين</h2>

    @if (empty($topOverdueSuppliers))
        <div class="empty" data-testid="main-dashboard-top-overdue-suppliers-print-empty">
            لا توجد فواتير موردين متأخرة حاليًا.
        </div>
    @else
        <table data-testid="main-dashboard-top-overdue-suppliers-print-table">
            <thead>
                <tr>
                    <th>المورد</th>
                    <th>عدد الفواتير</th>
                    <th>إجمالي المتأخر</th>
                    <th>أقدم استحقاق</th>
                    <th>أقصى تأخير</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($topOverdueSuppliers as $row)
                    <tr>
                        <td>{{ $row['supplier_name'] }}</td>
                        <td>{{ $row['invoice_count'] }}</td>
                        <td>{{ number_format((float) $row['overdue_total'], 2) }} ريال</td>
                        <td>{{ $row['oldest_due_at'] ?? '' }}</td>
                        <td>{{ $row['max_days_overdue'] === null ? '' : $row['max_days_overdue'] . ' يوم' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
