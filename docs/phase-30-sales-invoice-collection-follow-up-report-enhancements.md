# Phase 30 — Sales Invoice Collection Follow-up Report Enhancements

## الهدف

تطوير متابعة تحصيل فواتير المبيعات من مستوى الملاحظات والإجراءات اليومية، بحيث يستطيع المستخدم معرفة المتابعات المستحقة، تصفيتها، تصديرها، إغلاقها عند الانتهاء، أو إعادة جدولتها لتاريخ لاحق.

## النطاق المنفذ

### 30A — Sales Invoice Collection Follow-up Due Report

تمت إضافة تقرير مستقل لمتابعات تحصيل فواتير المبيعات المستحقة.

يعرض التقرير:

- ملاحظات التحصيل التي تاريخ متابعتها اليوم أو قبل اليوم.
- استبعاد الفواتير المسددة بالكامل.
- عدد المتابعات المستحقة.
- عدد المتابعات القادمة.
- عدد الفواتير المرتبطة بالمتابعات المستحقة.
- إجمالي المبالغ المتبقية للفواتير المستحقة للمتابعة.
- جدول تفصيلي للمتابعات المستحقة.
- رابط إلى تقرير تحصيل فواتير المبيعات.
- رابط في مركز التقارير.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceCollectionFollowUpReportTest.php`

### 30B — Sales Invoice Collection Follow-up Report Filters

تمت إضافة فلاتر لتقرير متابعات التحصيل:

- العميل `customer_id`.
- من تاريخ متابعة `follow_up_from`.
- إلى تاريخ متابعة `follow_up_to`.

تم تطبيق الفلاتر على:

- المتابعات المستحقة.
- المتابعات القادمة.
- إجماليات التقرير.
- جدول الملاحظات.

كما تمت إضافة:

- إبقاء القيم المختارة داخل التقرير بعد التطبيق.
- زر إعادة ضبط الفلاتر.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceCollectionFollowUpReportFilterTest.php`

### 30C — Sales Invoice Collection Follow-up Report Export

تمت إضافة تصدير CSV لتقرير متابعات التحصيل المستحقة.

يدعم التصدير:

- احترام فلتر العميل.
- احترام فلتر من تاريخ متابعة.
- احترام فلتر إلى تاريخ متابعة.
- استبعاد الفواتير المسددة بالكامل.
- استبعاد المتابعات غير المستحقة.
- إضافة بيانات تعريف أعلى ملف CSV:
  - اسم التقرير.
  - تاريخ إنشاء التقرير.
  - فلتر العميل.
  - من تاريخ متابعة.
  - إلى تاريخ متابعة.
- إضافة صف إجمالي في نهاية التصدير:
  - إجمالي المتابعات.
  - عدد الفواتير.
  - إجمالي المتبقي.
- إضافة زر تصدير داخل صفحة التقرير.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceCollectionFollowUpReportExportTest.php`

### 30D — Complete Sales Invoice Collection Follow-up Notes

تمت إضافة إمكانية تعليم ملاحظة متابعة التحصيل كمكتملة.

المكونات المضافة:

- حقول جديدة على جدول `sales_invoice_collection_notes`:
  - `completed_at`
  - `completed_by_user_id`
  - `completion_note`
- علاقة `completedByUser` داخل موديل `SalesInvoiceCollectionNote`.
- إجراء إغلاق المتابعة داخل `SalesInvoiceCollectionNoteController`.
- Route لإغلاق المتابعة.
- نموذج إغلاق داخل صفحة فاتورة البيع.
- عرض حالة الإكمال داخل صفحة الفاتورة.
- حفظ المستخدم الذي أغلق المتابعة.
- حفظ ملاحظة إغلاق اختيارية.
- استبعاد المتابعات المكتملة من تقرير المتابعات المستحقة.
- استبعاد المتابعات المكتملة من تصدير التقرير.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceCollectionNoteCompletionTest.php`

### 30E — Reschedule Sales Invoice Collection Follow-up Notes

تمت إضافة إمكانية إعادة جدولة ملاحظة متابعة التحصيل.

المميزات:

- تحديث تاريخ المتابعة `follow_up_at`.
- منع إعادة جدولة الملاحظات المكتملة.
- منع إعادة جدولة ملاحظة لا تنتمي لنفس الفاتورة.
- إضافة Route لإعادة الجدولة.
- إضافة نموذج إعادة جدولة داخل صفحة الفاتورة.
- استبعاد المتابعة من التقرير المستحق إذا تم نقلها لتاريخ قادم.
- استبعادها كذلك من تصدير المتابعات المستحقة إذا أصبح تاريخها في المستقبل.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceCollectionNoteRescheduleTest.php`

## الملفات الرئيسية المتأثرة

- `app/Http/Controllers/SalesInvoiceCollectionFollowUpReportController.php`
- `app/Http/Controllers/SalesInvoiceCollectionNoteController.php`
- `app/Http/Controllers/SalesInvoiceController.php`
- `app/Models/SalesInvoiceCollectionNote.php`
- `database/migrations/2026_07_06_300100_add_completion_fields_to_sales_invoice_collection_notes_table.php`
- `resources/views/reports/sales-invoice-collection-follow-ups.blade.php`
- `resources/views/reports/index.blade.php`
- `resources/views/sales-invoices/show.blade.php`
- `routes/web.php`

## الاختبارات المضافة

- `tests/Feature/SalesInvoiceCollectionFollowUpReportTest.php`
- `tests/Feature/SalesInvoiceCollectionFollowUpReportFilterTest.php`
- `tests/Feature/SalesInvoiceCollectionFollowUpReportExportTest.php`
- `tests/Feature/SalesInvoiceCollectionNoteCompletionTest.php`
- `tests/Feature/SalesInvoiceCollectionNoteRescheduleTest.php`

## النتيجة

أصبحت متابعة تحصيل فواتير المبيعات تدعم دورة عمل أقرب للواقع التشغيلي:

1. إنشاء ملاحظات متابعة تحصيل على الفاتورة.
2. ظهور المتابعات المستحقة في تقرير مستقل.
3. تصفية التقرير حسب العميل ونطاق تاريخ المتابعة.
4. تصدير المتابعات المستحقة إلى CSV.
5. إغلاق المتابعة عند الانتهاء منها.
6. إعادة جدولة المتابعة إلى تاريخ لاحق.
7. استبعاد المتابعات المكتملة أو المؤجلة من تقرير المستحقات.

هذه المرحلة تكمل طبقة تشغيلية مهمة لإدارة التحصيل اليومي داخل النظام.
