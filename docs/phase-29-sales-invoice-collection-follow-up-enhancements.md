# Phase 29 — Sales Invoice Collection Follow-up Enhancements

## الهدف

تطوير أدوات متابعة تحصيل فواتير المبيعات، بحيث يمكن معرفة الفواتير ذات المبالغ المتبقية، الفواتير المتأخرة، تواريخ الاستحقاق، ملاحظات المتابعة، وتقرير التحصيل العام من داخل النظام.

## النطاق المنفذ

### 29A — Sales Invoice Overdue Collection Filter

تمت إضافة فلتر جديد في صفحة فواتير المبيعات وتصدير CSV:

- `collection_status=overdue`
- الفاتورة تعتبر متأخرة إذا:
  - `due_at` قبل تاريخ اليوم.
  - `remaining_amount` أكبر من صفر.
- إضافة خيار "فواتير متأخرة التحصيل" في فلتر حالة التحصيل.
- دعم الفلتر في صفحة فواتير المبيعات.
- دعم الفلتر في تصدير CSV.
- إضافة تسمية عربية مقروءة في بيانات تعريف التصدير.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceOverdueCollectionFilterTest.php`

### 29B — Customer Overdue Sales Invoice Summary

تمت إضافة ملخص فواتير العميل المتأخرة في صفحة العميل.

يعرض الملخص:

- عدد الفواتير المتأخرة.
- إجمالي المبالغ المتأخرة.
- حالة التحصيل:
  - يحتاج متابعة عاجلة.
  - لا توجد فواتير متأخرة.
- رابط مباشر لعرض فواتير العميل المتأخرة في صفحة فواتير المبيعات.

القاعدة المستخدمة:

- `due_at` قبل تاريخ اليوم.
- `remaining_amount` أكبر من صفر.

الاختبار المرتبط:

- `tests/Feature/CustomerOverdueSalesInvoiceSummaryPageTest.php`

### 29C — Sales Invoice Due Date Range Filter

تمت إضافة فلتر نطاق تاريخ الاستحقاق في صفحة فواتير المبيعات:

- `due_from`
- `due_to`
- تطبيق الفلتر على حقل `due_at`.
- دمج الفلتر مع:
  - العميل.
  - حالة الدفع.
  - حالة التحصيل.
  - تاريخ الإصدار.
- إضافة فلاتر تاريخ الاستحقاق إلى رابط تصدير النتائج الحالية.
- دعم فلاتر تاريخ الاستحقاق في تصدير CSV وبيانات التعريف.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceDueDateRangeFilterTest.php`

### 29D — Sales Invoice Collection Follow-up Notes

تمت إضافة ملاحظات متابعة التحصيل على صفحة فاتورة البيع.

المكونات المضافة:

- جدول `sales_invoice_collection_notes`.
- موديل `SalesInvoiceCollectionNote`.
- Controller باسم `SalesInvoiceCollectionNoteController`.
- علاقة `collectionNotes` داخل موديل `SalesInvoice`.
- نموذج إضافة ملاحظة تحصيل من صفحة الفاتورة.
- تاريخ متابعة اختياري.
- ربط الملاحظة بالمستخدم الذي أضافها.
- عرض ملاحظات التحصيل داخل صفحة الفاتورة.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceCollectionNoteTest.php`

### 29E — Sales Invoice Collection Status Report

تمت إضافة تقرير مستقل لمتابعة تحصيل فواتير المبيعات.

يعرض التقرير:

- كل الفواتير ذات المتبقي:
  - العدد.
  - إجمالي المتبقي.
- الفواتير المتأخرة:
  - العدد.
  - إجمالي المتأخر.
- الفواتير غير المدفوعة:
  - العدد.
  - إجمالي المتبقي.
- الفواتير المدفوعة جزئيًا:
  - العدد.
  - إجمالي المتبقي.
- جدول مختصر للفواتير التي تحتاج متابعة.
- رابط مباشر لعرض الفواتير المتأخرة.
- رابط التقرير داخل مركز التقارير.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceCollectionReportTest.php`

## الملفات الرئيسية المتأثرة

- `app/Http/Controllers/SalesInvoiceController.php`
- `app/Http/Controllers/CustomerController.php`
- `app/Http/Controllers/SalesInvoiceCollectionNoteController.php`
- `app/Http/Controllers/SalesInvoiceCollectionReportController.php`
- `app/Models/SalesInvoice.php`
- `app/Models/SalesInvoiceCollectionNote.php`
- `database/migrations/2026_07_06_290000_create_sales_invoice_collection_notes_table.php`
- `resources/views/sales-invoices/index.blade.php`
- `resources/views/sales-invoices/show.blade.php`
- `resources/views/customers/show.blade.php`
- `resources/views/reports/index.blade.php`
- `resources/views/reports/sales-invoice-collections.blade.php`
- `routes/web.php`

## الاختبارات المضافة

- `tests/Feature/SalesInvoiceOverdueCollectionFilterTest.php`
- `tests/Feature/CustomerOverdueSalesInvoiceSummaryPageTest.php`
- `tests/Feature/SalesInvoiceDueDateRangeFilterTest.php`
- `tests/Feature/SalesInvoiceCollectionNoteTest.php`
- `tests/Feature/SalesInvoiceCollectionReportTest.php`

## النتيجة

أصبحت وحدة فواتير المبيعات تدعم متابعة التحصيل بشكل أوضح من خلال:

1. فلترة الفواتير المتأخرة.
2. معرفة المتأخرات من صفحة العميل.
3. فلترة الفواتير حسب تاريخ الاستحقاق.
4. تسجيل ملاحظات متابعة التحصيل داخل الفاتورة.
5. تقرير تحصيل مستقل يوضح الفواتير التي تحتاج متابعة.
6. ربط التقرير بمركز التقارير.

هذه المرحلة تجعل النظام أكثر ملاءمة لإدارة التحصيل، متابعة العملاء، وتقليل الفواتير المتأخرة.
