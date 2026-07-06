# Phase 31 — Sales Invoice Aging Report Enhancements

## الهدف

تطوير تقرير أعمار ذمم فواتير المبيعات، بحيث يستطيع المستخدم تحليل الفواتير المفتوحة حسب تاريخ الاستحقاق، عدد أيام التأخير، العميل، حالة الدفع، وشريحة العمر، مع إمكانية تصدير التقرير إلى CSV.

## النطاق المنفذ

### 31A — Sales Invoice Aging Report

تمت إضافة تقرير مستقل لأعمار ذمم فواتير المبيعات.

يعرض التقرير الفواتير المفتوحة التي لديها مبلغ متبقٍ أكبر من صفر، ويتم تقسيمها إلى شرائح حسب تاريخ الاستحقاق:

- غير مستحقة بعد.
- متأخرة 1 إلى 30 يوم.
- متأخرة 31 إلى 60 يوم.
- متأخرة 61 إلى 90 يوم.
- أكثر من 90 يوم.
- بدون تاريخ استحقاق.

يعرض التقرير:

- عدد الفواتير المفتوحة.
- إجمالي المبالغ المتبقية.
- ملخص كل شريحة:
  - عدد الفواتير.
  - إجمالي المتبقي.
- جدول الفواتير المفتوحة حسب الأقدمية.
- رابط مباشر للفواتير ذات المتبقي.
- رابط التقرير داخل مركز التقارير.

الملفات الرئيسية:

- `app/Http/Controllers/SalesInvoiceAgingReportController.php`
- `resources/views/reports/sales-invoice-aging.blade.php`
- `resources/views/reports/index.blade.php`
- `routes/web.php`

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceAgingReportTest.php`

### 31B — Sales Invoice Aging Report Filters

تمت إضافة فلاتر للتقرير:

- فلتر العميل `customer_id`.
- فلتر حالة الدفع `payment_status`.

تطبق الفلاتر على:

- إجمالي التقرير.
- شرائح الأعمار.
- جدول الفواتير المفتوحة.

كما تمت إضافة:

- إبقاء القيم المختارة بعد تطبيق الفلتر.
- زر إعادة ضبط الفلاتر.
- دعم حالات الدفع:
  - غير مدفوعة.
  - مدفوعة جزئيًا.
  - مدفوعة بالكامل.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceAgingReportFilterTest.php`

### 31C — Sales Invoice Aging Report Export

تمت إضافة تصدير CSV لتقرير أعمار ذمم فواتير المبيعات.

يدعم التصدير:

- احترام فلتر العميل.
- احترام فلتر حالة الدفع.
- بيانات تعريف أعلى ملف CSV:
  - اسم التقرير.
  - تاريخ إنشاء التقرير.
  - تاريخ التقرير.
  - فلتر العميل.
  - فلتر حالة الدفع.
- ملخص شرائح الأعمار داخل ملف CSV.
- جدول الفواتير المفتوحة.
- صف إجمالي في نهاية الملف:
  - إجمالي عدد الفواتير المفتوحة.
  - إجمالي المتبقي.
- زر تصدير CSV داخل صفحة التقرير.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceAgingReportExportTest.php`

### 31D — Sales Invoice Aging Bucket Filter

تمت إضافة فلتر شريحة العمر `aging_bucket`.

الشرائح المدعومة:

- `not_due`
- `overdue_1_30`
- `overdue_31_60`
- `overdue_61_90`
- `overdue_more_than_90`
- `without_due_date`

تم تطبيق فلتر الشريحة على:

- صفحة تقرير أعمار الذمم.
- جدول الفواتير المفتوحة.
- إجماليات التقرير.
- تصدير CSV.

كما تمت إضافة فلتر الشريحة إلى رابط التصدير حتى يتم تصدير نفس النتائج المعروضة في التقرير.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceAgingReportBucketFilterTest.php`

## الملفات الرئيسية المتأثرة

- `app/Http/Controllers/SalesInvoiceAgingReportController.php`
- `resources/views/reports/sales-invoice-aging.blade.php`
- `resources/views/reports/index.blade.php`
- `routes/web.php`

## الاختبارات المضافة

- `tests/Feature/SalesInvoiceAgingReportTest.php`
- `tests/Feature/SalesInvoiceAgingReportFilterTest.php`
- `tests/Feature/SalesInvoiceAgingReportExportTest.php`
- `tests/Feature/SalesInvoiceAgingReportBucketFilterTest.php`

## النتيجة

أصبح النظام يدعم تقرير أعمار ذمم لفواتير المبيعات يساعد في:

1. معرفة حجم الذمم المفتوحة.
2. توزيع المبالغ المتبقية حسب التأخير.
3. تحديد الفواتير الأقدم والأكثر أولوية للتحصيل.
4. تصفية التقرير حسب العميل وحالة الدفع وشريحة العمر.
5. تصدير التقرير إلى CSV لاستخدامه في المتابعة المالية.
6. ربط التقرير بمركز التقارير وبصفحة فواتير المبيعات.

هذه المرحلة تضيف طبقة تحليل مالي مهمة لإدارة التحصيل والذمم المدينة داخل النظام.
