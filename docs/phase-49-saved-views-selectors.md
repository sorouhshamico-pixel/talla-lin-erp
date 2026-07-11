# Phase 49 — Saved Views Selectors Inside Reports

## الهدف

إضافة محددات العروض المحفوظة داخل صفحات التقارير نفسها، بحيث يستطيع المستخدم فتح العروض المحفوظة مباشرة من داخل التقرير، بدل الاعتماد فقط على صفحة إدارة العروض المحفوظة.

## النطاق المنفذ

### Phase 49A — Sales Invoice Aging Report

تمت إضافة محدد العروض المحفوظة داخل تقرير:

- `sales-invoice-aging`

الملفات:

- `app/Services/ReportSavedViewService.php`
- `app/Http/Controllers/SalesInvoiceAgingReportController.php`
- `resources/views/reports/sales-invoice-aging.blade.php`
- `tests/Feature/SalesInvoiceAgingReportSavedViewSelectorTest.php`

Commit:

- `6bd1f08 Add saved views selector to sales invoice aging report`

### Phase 49B — Customer And Supplier Aging Reports

تمت إضافة محددات العروض المحفوظة داخل:

- `customer-sales-invoice-aging`
- `supplier-purchase-invoice-aging`

الملفات:

- `app/Http/Controllers/CustomerSalesInvoiceAgingReportController.php`
- `app/Http/Controllers/SupplierPurchaseInvoiceAgingReportController.php`
- `resources/views/reports/customer-sales-invoice-aging.blade.php`
- `resources/views/reports/supplier-purchase-invoice-aging.blade.php`
- `tests/Feature/CustomerSalesInvoiceAgingReportSavedViewSelectorTest.php`
- `tests/Feature/SupplierPurchaseInvoiceAgingReportSavedViewSelectorTest.php`

Commit:

- `65217ff Add saved views selectors to customer and supplier aging reports`

### Phase 49C — Aging Drilldown Reports

تمت إضافة محددات العروض المحفوظة داخل:

- `customer-sales-invoice-aging-drilldown`
- `supplier-purchase-invoice-aging-drilldown`

الملفات:

- `app/Http/Controllers/CustomerSalesInvoiceAgingDrilldownController.php`
- `app/Http/Controllers/SupplierPurchaseInvoiceAgingDrilldownController.php`
- `resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php`
- `resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php`
- `tests/Feature/CustomerSalesInvoiceAgingDrilldownSavedViewSelectorTest.php`
- `tests/Feature/SupplierPurchaseInvoiceAgingDrilldownSavedViewSelectorTest.php`

Commit:

- `560fff2 Add saved views selectors to aging drilldown reports`

## Service

تمت إضافة الدالة التالية إلى:

- `App\Services\ReportSavedViewService`

```php
listForReport(User $user, string $reportKey)
```

وظيفتها:

- جلب العروض المحفوظة الخاصة بالمستخدم.
- حصر النتائج حسب `report_key`.
- ترتيب العرض الافتراضي أولًا.
- ترتيب باقي العروض بالاسم.

## السلوك المعتمد

داخل كل تقرير مدعوم:

- يظهر قسم العروض المحفوظة.
- إذا لم توجد عروض، تظهر رسالة فارغة.
- إذا وجدت عروض، تظهر كرابط مباشر لفتح التقرير بالفلاتر المحفوظة.
- إذا كان العرض افتراضيًا، تظهر شارة `افتراضي`.
- يوجد رابط لإدارة العروض المحفوظة.

## التقارير المدعومة

| التقرير | Report Key | Selector |
|---|---|---|
| أعمار فواتير المبيعات | `sales-invoice-aging` | مكتمل |
| أعمار ذمم العملاء | `customer-sales-invoice-aging` | مكتمل |
| أعمار ذمم الموردين | `supplier-purchase-invoice-aging` | مكتمل |
| تفاصيل أعمار ذمم العملاء | `customer-sales-invoice-aging-drilldown` | مكتمل |
| تفاصيل أعمار ذمم الموردين | `supplier-purchase-invoice-aging-drilldown` | مكتمل |

## الاختبارات

تمت إضافة اختبارات تغطي:

- ظهور حالة عدم وجود عروض محفوظة.
- عرض العروض الخاصة بالتقرير الحالي فقط.
- عدم عرض عروض تقارير أخرى.
- ظهور رابط فتح العرض المحفوظ.
- ظهور شارة العرض الافتراضي.
- تمرير الفلاتر المحفوظة في رابط فتح التقرير.

الاختبارات الرئيسية:

- `SalesInvoiceAgingReportSavedViewSelectorTest`
- `CustomerSalesInvoiceAgingReportSavedViewSelectorTest`
- `SupplierPurchaseInvoiceAgingReportSavedViewSelectorTest`
- `CustomerSalesInvoiceAgingDrilldownSavedViewSelectorTest`
- `SupplierPurchaseInvoiceAgingDrilldownSavedViewSelectorTest`

## حالة الاعتماد

Phase 49 مكتملة بعد نجاح الاختبار الكامل.

آخر حالة مؤكدة قبل التوثيق:

- آخر commit وظيفي: `560fff2 Add saved views selectors to aging drilldown reports`
- آخر اختبار كامل: `790 passed / 5567 assertions`
