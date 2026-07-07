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
            margin: 32px;
            line-height: 1.7;
        }

        .header {
            border-bottom: 2px solid #111827;
            margin-bottom: 24px;
            padding-bottom: 16px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .meta {
            color: #4b5563;
            font-size: 14px;
        }

        .actions {
            margin-bottom: 24px;
        }

        .button {
            display: inline-block;
            border: 1px solid #111827;
            border-radius: 6px;
            padding: 8px 14px;
            color: #111827;
            text-decoration: none;
            background: #ffffff;
            cursor: pointer;
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
        <button type="button" class="button" onclick="window.print()" data-testid="customer-aging-print-button">طباعة</button>
    </div>

    <div class="header">
        <h1>تقرير أعمار ذمم العملاء</h1>
        <div class="meta">تاريخ التقرير: {{ $reportDate }}</div>
        <div class="meta">فلتر العميل: {{ $customerFilter ?: 'all' }}</div>
        <div class="meta">فلتر شريحة العمر: {{ $agingBucketFilter ?: 'all' }}</div>
    </div>

    <p data-testid="customer-aging-print-skeleton">سيتم عرض بيانات تقرير أعمار ذمم العملاء في مرحلة الربط التالية.</p>
</body>
</html>