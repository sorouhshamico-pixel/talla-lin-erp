# Phase 26 — Customer Sales Invoice Page Enhancements

## الهدف

تحسين صفحة تفاصيل العميل بعد اكتمال دورة فواتير المبيعات والدفعات، بحيث تصبح صفحة العميل نقطة متابعة مباشرة لفواتير المبيعات، والمدفوع، والمتبقي، وروابط العرض والتصدير.

## النطاق المنفذ

### 26A — Customer Sales Invoice Summary On Customer Page

تمت إضافة ملخص عام لفواتير مبيعات العميل داخل صفحة العميل:

- احتساب عدد فواتير المبيعات المرتبطة بالعميل.
- احتساب إجمالي قيمة فواتير العميل.
- احتساب إجمالي المدفوع.
- احتساب إجمالي المتبقي.
- إضافة بطاقة `customer-sales-invoice-summary-card`.
- إضافة رابط مباشر إلى صفحة فواتير المبيعات مفلترة بـ `customer_id`.
- دعم فلتر `customer_id` في `SalesInvoiceController@index`.
- إضافة fallback داخل `customers/show.blade.php` لتجنب خطأ المتغير غير المعرف عند فتح الصفحة من مسارات أو اختبارات لا تمرر الملخص.

الاختبار المرتبط:

- `tests/Feature/CustomerSalesInvoiceSummaryPageTest.php`

### 26B — Customer Recent Sales Invoices On Customer Page

تمت إضافة قائمة آخر فواتير مبيعات العميل:

- عرض آخر 5 فواتير مبيعات مرتبطة بالعميل.
- إظهار رقم الفاتورة.
- إظهار تاريخ الفاتورة.
- إظهار إجمالي الفاتورة.
- إظهار المدفوع.
- إظهار المتبقي.
- إظهار حالة الدفع.
- إضافة رابط فتح لكل فاتورة.
- عرض رسالة فراغ عند عدم وجود فواتير.

الاختبار المرتبط:

- `tests/Feature/CustomerRecentSalesInvoicesPageTest.php`

### 26C — Customer Outstanding Sales Invoice Summary

تمت إضافة ملخص مستقل للفواتير ذات المبالغ المتبقية:

- احتساب عدد فواتير العميل التي لها مبلغ متبقٍ.
- احتساب إجمالي المبالغ المتبقية.
- عرض حالة متابعة مناسبة.
- إضافة رابط مباشر إلى صفحة فواتير المبيعات مفلترة بـ:
  - `customer_id`
  - `collection_status=outstanding`
- دعم فلتر `collection_status=outstanding` في `SalesInvoiceController@index`.

الاختبار المرتبط:

- `tests/Feature/CustomerOutstandingSalesInvoiceSummaryPageTest.php`

### 26D — Customer Paid Sales Invoice Summary

تمت إضافة ملخص مستقل لفواتير العميل المدفوعة بالكامل:

- احتساب عدد فواتير العميل المدفوعة.
- احتساب إجمالي قيمة الفواتير المدفوعة.
- عرض حالة تحصيل مناسبة.
- إضافة رابط مباشر إلى صفحة فواتير المبيعات مفلترة بـ:
  - `customer_id`
  - `payment_status=paid`
- دعم فلتر `payment_status` في `SalesInvoiceController@index`.

الاختبار المرتبط:

- `tests/Feature/CustomerPaidSalesInvoiceSummaryPageTest.php`

### 26E — Customer Sales Invoice Export Links

تمت إضافة روابط تصدير فواتير مبيعات العميل:

- إضافة route باسم `sales-invoices.export`.
- إضافة `SalesInvoiceController@export`.
- تصدير كل فواتير العميل CSV.
- تصدير فواتير العميل ذات المتبقي CSV.
- تصدير فواتير العميل المدفوعة CSV.
- دعم فلاتر التصدير:
  - `customer_id`
  - `collection_status=outstanding`
  - `payment_status=paid`
- إضافة روابط التصدير داخل صفحة العميل.

الاختبار المرتبط:

- `tests/Feature/CustomerSalesInvoiceExportLinksTest.php`

## الملفات الرئيسية المتأثرة

- `app/Http/Controllers/CustomerController.php`
- `app/Http/Controllers/SalesInvoiceController.php`
- `resources/views/customers/show.blade.php`
- `routes/web.php`
- `tests/Feature/CustomerSalesInvoiceSummaryPageTest.php`
- `tests/Feature/CustomerRecentSalesInvoicesPageTest.php`
- `tests/Feature/CustomerOutstandingSalesInvoiceSummaryPageTest.php`
- `tests/Feature/CustomerPaidSalesInvoiceSummaryPageTest.php`
- `tests/Feature/CustomerSalesInvoiceExportLinksTest.php`

## النتيجة

أصبحت صفحة العميل تعرض رؤية مالية وتشغيلية أوضح لفواتير المبيعات المرتبطة به:

1. ملخص إجمالي فواتير المبيعات.
2. آخر فواتير المبيعات.
3. ملخص الفواتير ذات المبالغ المتبقية.
4. ملخص الفواتير المدفوعة بالكامل.
5. روابط فتح صفحة الفواتير مفلترة حسب العميل وحالة التحصيل.
6. روابط تصدير CSV حسب العميل وحالة التحصيل.

هذا يعزز متابعة العملاء والتحصيل ويجعل صفحة العميل مركزًا مباشرًا لمراجعة موقفه المالي.
