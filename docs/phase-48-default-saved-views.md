# Phase 48 — Default Saved Views

## الهدف

تفعيل العروض المحفوظة الافتراضية داخل التقارير، بحيث يتم تطبيق فلاتر العرض الافتراضي تلقائيًا عند فتح التقرير بدون فلاتر صريحة.

هذه المرحلة تجعل خيار `is_default` عمليًا داخل التقارير، وليس مجرد حالة إدارية في صفحة العروض المحفوظة.

## السلوك المعتمد

عند فتح تقرير مدعوم:

1. إذا كان الرابط يحتوي على `reset_filters=1`:
   - لا يتم تطبيق العرض الافتراضي.
   - يتم مسح تفضيلات الفلاتر المحفوظة للمستخدم.

2. إذا كان الرابط يحتوي على أي فلتر صريح:
   - يتم تجاهل العرض الافتراضي.
   - الفلاتر الصريحة في الرابط تكون لها الأولوية.

3. إذا تم فتح التقرير بدون فلاتر:
   - يتم البحث عن عرض محفوظ افتراضي للمستخدم ولنفس `report_key`.
   - إذا وجد عرض افتراضي، يتم دمج فلاتره داخل الطلب.
   - بعد ذلك يتم حفظها كتفضيلات فلاتر للمستخدم عبر نظام `user_report_filter_preferences`.

## المراحل المنفذة

### Phase 48A — Sales Invoice Aging

تم تطبيق العرض الافتراضي على:

- `sales-invoice-aging`

الملفات:

- `app/Http/Controllers/SalesInvoiceAgingReportController.php`
- `tests/Feature/SalesInvoiceAgingReportDefaultSavedViewTest.php`

Commit:

- `3ea1dc0 Apply default saved view to sales invoice aging report`

### Phase 48B — Customer And Supplier Aging Reports

تم تطبيق العرض الافتراضي على:

- `customer-sales-invoice-aging`
- `supplier-purchase-invoice-aging`

الملفات:

- `app/Http/Controllers/CustomerSalesInvoiceAgingReportController.php`
- `app/Http/Controllers/SupplierPurchaseInvoiceAgingReportController.php`
- `tests/Feature/CustomerSalesInvoiceAgingReportDefaultSavedViewTest.php`
- `tests/Feature/SupplierPurchaseInvoiceAgingReportDefaultSavedViewTest.php`

Commit:

- `fa47563 Apply default saved views to customer and supplier aging reports`

### Phase 48C — Aging Drilldown Reports

تم تطبيق العرض الافتراضي على:

- `customer-sales-invoice-aging-drilldown`
- `supplier-purchase-invoice-aging-drilldown`

الملفات:

- `app/Http/Controllers/CustomerSalesInvoiceAgingDrilldownController.php`
- `app/Http/Controllers/SupplierPurchaseInvoiceAgingDrilldownController.php`
- `tests/Feature/CustomerSalesInvoiceAgingDrilldownDefaultSavedViewTest.php`
- `tests/Feature/SupplierPurchaseInvoiceAgingDrilldownDefaultSavedViewTest.php`

Commit:

- `5b9e2f4 Apply default saved views to aging drilldown reports`

## التقارير المدعومة

| التقرير | Report Key | حالة التطبيق |
|---|---|---|
| أعمار فواتير المبيعات | `sales-invoice-aging` | مكتمل |
| أعمار ذمم العملاء | `customer-sales-invoice-aging` | مكتمل |
| أعمار ذمم الموردين | `supplier-purchase-invoice-aging` | مكتمل |
| تفاصيل أعمار ذمم العملاء | `customer-sales-invoice-aging-drilldown` | مكتمل |
| تفاصيل أعمار ذمم الموردين | `supplier-purchase-invoice-aging-drilldown` | مكتمل |

## العلاقة مع تفضيلات الفلاتر

يعتمد النظام على طبقتين:

### Saved Views

تخزن العروض المسماة التي ينشئها المستخدم.

الجدول:

- `report_saved_views`

### Filter Preferences

تخزن آخر فلاتر مستخدمة للتقرير.

الجدول:

- `user_report_filter_preferences`

عند تطبيق العرض الافتراضي، يتم تحويل فلاتره إلى طلب فعلي، ثم يحفظ نظام Filter Preferences هذه الفلاتر كآخر حالة مستخدمة.

## أولوية الفلاتر

الأولوية النهائية:

1. `reset_filters`
2. الفلاتر الصريحة في الرابط
3. العرض المحفوظ الافتراضي
4. تفضيلات الفلاتر السابقة
5. القيم الافتراضية داخل التقرير

## الاختبارات

تمت إضافة اختبارات تغطي:

- تطبيق العرض الافتراضي عند فتح التقرير بدون فلاتر.
- تجاهل العرض الافتراضي عند وجود فلاتر صريحة.
- تجاهل العرض الافتراضي عند استخدام `reset_filters`.
- استمرار عمل اختبارات الحفظ والإدارة السابقة.

الاختبارات الرئيسية:

- `SalesInvoiceAgingReportDefaultSavedViewTest`
- `CustomerSalesInvoiceAgingReportDefaultSavedViewTest`
- `SupplierPurchaseInvoiceAgingReportDefaultSavedViewTest`
- `CustomerSalesInvoiceAgingDrilldownDefaultSavedViewTest`
- `SupplierPurchaseInvoiceAgingDrilldownDefaultSavedViewTest`

## حالة الاعتماد

Phase 48 مكتملة بعد نجاح الاختبار الكامل.

آخر حالة مؤكدة قبل التوثيق:

- آخر commit وظيفي: `5b9e2f4 Apply default saved views to aging drilldown reports`
- آخر اختبار كامل: `780 passed / 5498 assertions`
