# Phase 47 — Report Saved Views

## الهدف

إضافة أساس موحد لحفظ عروض التقارير باسم مخصص لكل مستخدم، بحيث يستطيع المستخدم حفظ مجموعة فلاتر تقرير محددة والرجوع إليها لاحقًا من صفحة إدارة العروض المحفوظة.

الميزة تستهدف تقليل تكرار إدخال الفلاتر في التقارير التشغيلية والمالية، خصوصًا تقارير أعمار الذمم وتقارير التفاصيل المرتبطة بها.

## النطاق المنفذ

### 47A — Foundation

تمت إضافة البنية الأساسية للعروض المحفوظة:

- جدول `report_saved_views`
- موديل `App\Models\ReportSavedView`
- خدمة `App\Services\ReportSavedViewService`
- علاقة المستخدم بالعروض المحفوظة
- دعم تعيين عرض افتراضي واحد لكل مستخدم ولكل تقرير
- اختبارات خدمة الحفظ والتحديث والتعيين الافتراضي

### 47B — Management Page

تمت إضافة صفحة إدارة العروض المحفوظة:

- عرض كل العروض المحفوظة للمستخدم
- حذف عرض محفوظ
- تعيين عرض محفوظ كافتراضي
- روابط فتح التقرير بالفلاتر المحفوظة
- معالجة أسماء التقارير بشكل مقروء
- اختبارات إدارة العروض المحفوظة

### 47C — Sales Invoice Aging Saved Views

تمت إضافة حفظ العروض داخل تقرير أعمار فواتير المبيعات:

- report key: `sales-invoice-aging`
- حفظ فلاتر:
  - `customer_id`
  - `payment_status`
  - `aging_bucket`
- route:
  - `reports.sales-invoice-aging.saved-views.store`
- واجهة حفظ عرض داخل صفحة التقرير
- اختبارات feature مستقلة

### 47D — Customer And Supplier Aging Saved Views

تمت إضافة حفظ العروض داخل تقارير أعمار الذمم المختصرة:

- report key: `customer-sales-invoice-aging`
- report key: `supplier-purchase-invoice-aging`

فلاتر تقرير العملاء:

- `customer_id`
- `aging_bucket`

فلاتر تقرير الموردين:

- `supplier_id`
- `aging_bucket`

Routes:

- `reports.customer-sales-invoice-aging.saved-views.store`
- `reports.supplier-purchase-invoice-aging.saved-views.store`

### 47E — Aging Drilldown Saved Views

تمت إضافة حفظ العروض داخل صفحات تفاصيل أعمار الذمم:

- report key: `customer-sales-invoice-aging-drilldown`
- report key: `supplier-purchase-invoice-aging-drilldown`

فلاتر تفاصيل العملاء:

- `customer_id`
- `branch_id`
- `as_of_date`
- `aging_bucket`

فلاتر تفاصيل الموردين:

- `supplier_id`
- `branch_id`
- `as_of_date`
- `aging_bucket`

Routes:

- `reports.customer-sales-invoice-aging.drilldown.saved-views.store`
- `reports.supplier-purchase-invoice-aging.drilldown.saved-views.store`

## التقارير المدعومة

| التقرير | Report Key | حفظ من صفحة التقرير | فتح من صفحة الإدارة |
|---|---|---:|---:|
| أعمار فواتير المبيعات | `sales-invoice-aging` | نعم | نعم |
| أعمار ذمم العملاء | `customer-sales-invoice-aging` | نعم | نعم |
| أعمار ذمم الموردين | `supplier-purchase-invoice-aging` | نعم | نعم |
| تفاصيل أعمار ذمم العملاء | `customer-sales-invoice-aging-drilldown` | نعم | نعم |
| تفاصيل أعمار ذمم الموردين | `supplier-purchase-invoice-aging-drilldown` | نعم | نعم |

## سلوك العرض الافتراضي

عند حفظ عرض مع خيار `is_default`:

- يتم تعيين العرض الحالي كافتراضي للتقرير نفسه.
- يتم إلغاء الافتراضي من باقي عروض نفس المستخدم لنفس `report_key`.
- لا يتأثر مستخدم آخر بنفس التقرير.
- لا تتأثر عروض التقارير الأخرى.

## قواعد الحفظ

كل عملية حفظ تتحقق من:

- وجود اسم العرض
- طول الاسم لا يتجاوز 120 حرفًا
- صحة الفلاتر الرقمية
- صحة التاريخ عند وجود `as_of_date`
- صحة شريحة العمر عند وجود `aging_bucket`

القيم الفارغة لا تحفظ داخل JSON filters.

## الملفات الرئيسية

### Models

- `app/Models/ReportSavedView.php`
- `app/Models/User.php`

### Services

- `app/Services/ReportSavedViewService.php`

### Controllers

- `app/Http/Controllers/ReportSavedViewController.php`
- `app/Http/Controllers/SalesInvoiceAgingReportController.php`
- `app/Http/Controllers/CustomerSalesInvoiceAgingReportController.php`
- `app/Http/Controllers/SupplierPurchaseInvoiceAgingReportController.php`
- `app/Http/Controllers/CustomerSalesInvoiceAgingDrilldownController.php`
- `app/Http/Controllers/SupplierPurchaseInvoiceAgingDrilldownController.php`

### Views

- `resources/views/reports/saved-views/index.blade.php`
- `resources/views/reports/sales-invoice-aging.blade.php`
- `resources/views/reports/customer-sales-invoice-aging.blade.php`
- `resources/views/reports/supplier-purchase-invoice-aging.blade.php`
- `resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php`
- `resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php`

### Tests

- `tests/Feature/ReportSavedViewServiceTest.php`
- `tests/Feature/ReportSavedViewManagementTest.php`
- `tests/Feature/SalesInvoiceAgingReportSavedViewTest.php`
- `tests/Feature/CustomerSalesInvoiceAgingReportSavedViewTest.php`
- `tests/Feature/SupplierPurchaseInvoiceAgingReportSavedViewTest.php`
- `tests/Feature/CustomerSalesInvoiceAgingDrilldownSavedViewTest.php`
- `tests/Feature/SupplierPurchaseInvoiceAgingDrilldownSavedViewTest.php`

## حالة الاعتماد

Phase 47 مكتملة ومعتمدة بعد نجاح الاختبارات الكاملة.

آخر حالة مؤكدة قبل التوثيق:

- آخر commit وظيفي: `8bc3580 Allow saving aging drilldown report views`
- آخر اختبار كامل: `765 passed / 5440 assertions`
