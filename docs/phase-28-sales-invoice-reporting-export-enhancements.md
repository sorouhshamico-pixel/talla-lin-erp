# Phase 28 — Sales Invoice Reporting And Export Enhancements

## الهدف

تطوير قدرات التقارير والتصدير لفواتير المبيعات، بحيث يمكن مراجعة الفواتير حسب التاريخ والفلاتر الحالية، وتصدير ملف CSV واضح يحتوي على بيانات الفواتير، الفلاتر المستخدمة، والإجماليات النهائية.

## النطاق المنفذ

### 28A — Sales Invoice Date Range Filter

تمت إضافة فلتر نطاق تاريخ في صفحة فواتير المبيعات:

- `issued_from`
- `issued_to`
- تطبيق الفلتر على حقل `issued_at`.
- دمج فلتر التاريخ مع الفلاتر الحالية:
  - العميل `customer_id`
  - حالة الدفع `payment_status`
  - حالة التحصيل `collection_status`
- إبقاء قيم التاريخ المختارة داخل الحقول بعد تطبيق الفلتر.
- تحديث رابط تصدير النتائج الحالية ليحمل فلاتر التاريخ.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceDateRangeFilterTest.php`

### 28B — Sales Invoice Export Date Range Filters

تم تحديث تصدير CSV لفواتير المبيعات بحيث يحترم فلاتر التاريخ:

- `issued_from`
- `issued_to`
- دمج فلاتر التاريخ مع:
  - `customer_id`
  - `payment_status`
  - `collection_status`
- ضمان أن ملف CSV لا يحتوي إلا على الفواتير المطابقة للفلاتر.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceExportDateRangeFilterTest.php`

### 28C — Sales Invoice CSV Export Totals Row

تمت إضافة صف إجماليات في نهاية ملف CSV:

- إجمالي الفواتير.
- إجمالي المدفوع.
- إجمالي المتبقي.
- عدد الفواتير.
- احترام كل الفلاتر الحالية عند حساب الإجماليات.

الفلاتر المدعومة في الإجماليات:

- `customer_id`
- `payment_status`
- `collection_status`
- `issued_from`
- `issued_to`

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceExportTotalsRowTest.php`

### 28D — Sales Invoice CSV Export Filter Metadata

تمت إضافة بيانات تعريف أعلى ملف CSV:

- عنوان التقرير: تقرير فواتير المبيعات.
- تاريخ إنشاء التقرير.
- فلتر العميل.
- فلتر حالة الدفع.
- فلتر حالة التحصيل.
- من تاريخ.
- إلى تاريخ.
- استخدام `all` عند عدم وجود فلتر.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceExportMetadataRowsTest.php`

### 28E — Sales Invoice Export Readable Filter Labels

تم تحسين بيانات تعريف الفلاتر في CSV لتكون مقروءة بدل القيم التقنية:

- فلتر العميل يعرض اسم العميل مع رقم السجل.
- حالة الدفع تعرض:
  - غير مدفوعة
  - مدفوعة جزئيًا
  - مدفوعة بالكامل
- حالة التحصيل تعرض:
  - فواتير ذات مبالغ متبقية
- الإبقاء على `all` عند عدم وجود فلتر.
- تحديث اختبار metadata السابق ليتوافق مع التسميات المقروءة.

الاختبار المرتبط:

- `tests/Feature/SalesInvoiceExportReadableFilterLabelsTest.php`

## الملفات الرئيسية المتأثرة

- `app/Http/Controllers/SalesInvoiceController.php`
- `resources/views/sales-invoices/index.blade.php`
- `tests/Feature/SalesInvoiceDateRangeFilterTest.php`
- `tests/Feature/SalesInvoiceExportDateRangeFilterTest.php`
- `tests/Feature/SalesInvoiceExportTotalsRowTest.php`
- `tests/Feature/SalesInvoiceExportMetadataRowsTest.php`
- `tests/Feature/SalesInvoiceExportReadableFilterLabelsTest.php`

## النتيجة

أصبحت صفحة فواتير المبيعات وتصدير CSV تدعم:

1. فلترة الفواتير حسب نطاق التاريخ.
2. دمج التاريخ مع فلاتر العميل وحالة الدفع والتحصيل.
3. تصدير CSV يحترم نفس الفلاتر المعروضة في الصفحة.
4. إضافة بيانات تعريف واضحة أعلى ملف CSV.
5. إضافة صف إجماليات في نهاية ملف CSV.
6. استخدام تسميات عربية مقروءة للفلاتر داخل التصدير.

هذه المرحلة تجعل تصدير فواتير المبيعات مناسبًا للمراجعة المالية، المتابعة الشهرية، والتحصيل.
