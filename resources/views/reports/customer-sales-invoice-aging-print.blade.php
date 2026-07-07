<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طباعة تقرير أعمار ذمم العملاء</title>
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

        .empty {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 16px;
            color: #6b7280;
            text-align: center;
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
        <button type="button" class="button" onclick="window.print()" data-testid="customer-aging-print-button">طباعة</button>
    </div>

    <div class="header">
        <h1>تقرير أعمار ذمم العملاء</h1>
        <div class="meta">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</div>
        <div class="meta">فلتر العميل: {{ $customerFilterLabel }}</div>
        <div class="meta">فلتر شريحة العمر: {{ $agingBucketFilterLabel }}</div>
    </div>

    <div class="summary" data-testid="customer-aging-print-summary">
        <div class="card">
            <div class="label">عدد العملاء</div>
            <div class="value">{{ $summary['customers_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">عدد الفواتير المفتوحة</div>
            <div class="value">{{ $summary['invoice_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">إجمالي الذمم المفتوحة</div>
            <div class="value">{{ number_format((float) $summary['remaining_total'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">إجمالي المتأخر</div>
            <div class="value">{{ number_format((float) $summary['overdue_total'], 2) }}</div>
        </div>
    </div>

    @if ($rows->isEmpty())
        <div class="empty" data-testid="customer-aging-print-empty">لا توجد ذمم مفتوحة للعملاء.</div>
    @else
        <table data-testid="customer-aging-print-table">
            <thead>
                <tr>
                    <th>العميل</th>
                    <th>عدد الفواتير</th>
                    <th>إجمالي المتبقي</th>
                    <th>غير مستحقة بعد</th>
                    <th>متأخرة 1 إلى 30</th>
                    <th>متأخرة 31 إلى 60</th>
                    <th>متأخرة 61 إلى 90</th>
                    <th>أكثر من 90</th>
                    <th>بدون تاريخ استحقاق</th>
                    <th>أقدم استحقاق</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['customer'] ? $row['customer']->name : '' }}</td>
                        <td>{{ $row['invoice_count'] }}</td>
                        <td>{{ number_format((float) $row['remaining_total'], 2) }}</td>
                        <td>{{ number_format((float) $row['not_due_total'], 2) }}</td>
                        <td>{{ number_format((float) $row['overdue_1_30_total'], 2) }}</td>
                        <td>{{ number_format((float) $row['overdue_31_60_total'], 2) }}</td>
                        <td>{{ number_format((float) $row['overdue_61_90_total'], 2) }}</td>
                        <td>{{ number_format((float) $row['overdue_more_than_90_total'], 2) }}</td>
                        <td>{{ number_format((float) $row['without_due_date_total'], 2) }}</td>
                        <td>{{ $row['oldest_due_at'] ? $row['oldest_due_at']->format('Y-m-d') : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>