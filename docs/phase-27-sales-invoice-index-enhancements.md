# Phase 27 — Sales Invoice Index Enhancements

## الهدف

تحسين صفحة فواتير المبيعات لتصبح مركزًا عمليًا لمراجعة الفواتير، الفلاتر، التحصيل، الملخصات، والتصدير، بدل أن تكون مجرد جدول عرض.

## النطاق المنفذ

### 27A — Sales Invoice Customer Filter UI

تمت إضافة فلتر العميل في صفحة فواتير المبيعات:

- تحميل قائمة العملاء في `SalesInvoiceController@index`.
- إضافة select لاختيار العميل في `resources/views/sales-invoices/index.blade.php`.
- دعم إبقاء العميل المحدد selected.
- ربط الفلتر مع `customer_id` الموجود سابقًا في `SalesInvoiceController@index`.
- اختبار عرض الفلتر والفلترة حسب العميل.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceCustomerFilterUiTest.php`

### 27B — Sales Invoice Payment Status Filter UI

تمت إضافة فلتر حالة الدفع في صفحة فواتير المبيعات:

- كل الحالات.
- غير مدفوعة.
- مدفوعة جزئيًا.
- مدفوعة بالكامل.
- إبقاء قيمة الفلتر selected عند إعادة تحميل الصفحة.
- دعم الدمج مع فلتر العميل.

الاختبار المرتبط:

- `tests/Feature/SalesInvoicePaymentStatusFilterUiTest.php`

### 27C — Sales Invoice Outstanding Collection Filter UI

تمت إضافة فلتر حالة التحصيل:

- كل الفواتير.
- فواتير ذات مبالغ متبقية.
- استخدام `collection_status=outstanding`.
- إبقاء قيمة الفلتر selected.
- دعم الدمج مع فلتر العميل وحالة الدفع.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceCollectionStatusFilterUiTest.php`

### 27D — Sales Invoice Summary Cards On Index

تمت إضافة كروت ملخص في صفحة فواتير المبيعات، وتتأثر بالفلاتر الحالية:

- عدد الفواتير.
- إجمالي الفواتير.
- إجمالي المدفوع.
- إجمالي المتبقي.
- عدد الفواتير ذات المتبقي.
- عدد الفواتير المدفوعة.

تم احتساب الملخص من نفس Query الفواتير بعد تطبيق الفلاتر، لضمان تطابق الأرقام مع النتائج المعروضة في الجدول.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceSummaryCardsTest.php`

### 27E — Sales Invoice Export Links On Index

تمت إضافة روابط تصدير CSV في صفحة فواتير المبيعات:

- تصدير النتائج الحالية حسب الفلاتر.
- تصدير كل الفواتير.
- الحفاظ على الفلاتر الحالية عند التصدير:
  - `customer_id`
  - `payment_status`
  - `collection_status`
- الاعتماد على route التصدير الموجود `sales-invoices.export`.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceExportLinksIndexTest.php`

## الملفات الرئيسية المتأثرة

- `app/Http/Controllers/SalesInvoiceController.php`
- `resources/views/sales-invoices/index.blade.php`
- `tests/Feature/SalesInvoiceCustomerFilterUiTest.php`
- `tests/Feature/SalesInvoicePaymentStatusFilterUiTest.php`
- `tests/Feature/SalesInvoiceCollectionStatusFilterUiTest.php`
- `tests/Feature/SalesInvoiceSummaryCardsTest.php`
- `tests/Feature/SalesInvoiceExportLinksIndexTest.php`

## النتيجة

أصبحت صفحة فواتير المبيعات تدعم:

1. فلترة الفواتير حسب العميل.
2. فلترة الفواتير حسب حالة الدفع.
3. فلترة الفواتير حسب وجود مبلغ متبقٍ.
4. دمج الفلاتر معًا.
5. عرض ملخص مالي مباشر حسب الفلاتر.
6. تصدير النتائج الحالية CSV.
7. تصدير كل الفواتير CSV.

هذه المرحلة تجعل صفحة فواتير المبيعات مناسبة للمتابعة اليومية والتحصيل والمراجعة المالية.
