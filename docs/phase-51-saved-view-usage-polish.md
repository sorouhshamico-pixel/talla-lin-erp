# Phase 51 — Saved View Usage Polish

## الهدف

تحسين تجربة استخدام العروض المحفوظة داخل التقارير نفسها بعد اكتمال إدارة العروض المحفوظة في Phase 50.

تركز هذه المرحلة على جعل المستخدم يعرف بوضوح متى يكون التقرير مفتوحًا من عرض محفوظ، وأي عرض محفوظ هو النشط حاليًا، وكيف يمكنه إلغاء هذا الربط مع الحفاظ على الفلاتر الحالية.

## النطاق الوظيفي

### Phase 51A — Highlight Active Report Saved View

تمت إضافة إبراز للعرض المحفوظ النشط داخل التقرير عند فتح التقرير من خلال إجراء تطبيق العرض المحفوظ.

يعتمد ذلك على تمرير:

```text
saved_view_id
```

ضمن رابط التقرير عند تطبيق العرض المحفوظ.

تمت إضافة partial مشترك:

```text
resources/views/reports/partials/active-saved-view-banner.blade.php
```

ويظهر داخل التقرير تنبيه يوضح:

```text
التقرير مفتوح من العرض المحفوظ: [اسم العرض]
```

تم تضمين التنبيه في التقارير التالية:

```text
resources/views/reports/sales-invoice-aging.blade.php
resources/views/reports/customer-sales-invoice-aging.blade.php
resources/views/reports/supplier-purchase-invoice-aging.blade.php
resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php
resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php
```

### Phase 51B — Keep Active Report Saved View Selected

تم تحسين قوائم العروض المحفوظة داخل التقارير بحيث يتم تمييز العرض النشط حاليًا.

عند فتح تقرير مع:

```text
saved_view_id
```

تقوم قائمة العروض المحفوظة بما يلي:

- تمييز صف العرض النشط بإضافة class باسم `active-saved-view-row`.
- إظهار شارة `نشط`.
- تمرير `saved_view_id` داخل روابط فتح العروض المحفوظة حتى يبقى العرض المحدد واضحًا بعد التنقل.

تم تطبيق ذلك على قوائم العروض المحفوظة في التقارير الخمسة نفسها.

### Phase 51C — Add Clear Active Saved View Link

تمت إضافة رابط داخل تنبيه العرض النشط لإلغاء ربط التقرير بالعرض المحفوظ الحالي.

نص الرابط:

```text
عرض التقرير بدون العرض المحفوظ
```

وظيفة الرابط:

- إزالة `saved_view_id` من query string.
- الحفاظ على باقي الفلاتر الحالية كما هي.
- عدم إعادة ضبط التقرير بالكامل.
- إخفاء تنبيه العرض النشط بعد فتح الرابط الناتج.

## الملفات الرئيسية

### Controller

```text
app/Http/Controllers/ReportSavedViewController.php
```

تم تحسين `apply` بحيث يضيف `saved_view_id` إلى رابط التقرير الناتج عند تطبيق العرض المحفوظ.

### Partial

```text
resources/views/reports/partials/active-saved-view-banner.blade.php
```

مسؤول عن:

- تحديد العرض النشط من `saved_view_id`.
- عرض اسم العرض النشط.
- بناء رابط إلغاء العرض النشط بإزالة `saved_view_id` فقط.
- الحفاظ على باقي query parameters.

### Report Views

تم تعديل التقارير التالية لإظهار التنبيه وتمييز العرض النشط داخل قائمة العروض المحفوظة:

```text
resources/views/reports/sales-invoice-aging.blade.php
resources/views/reports/customer-sales-invoice-aging.blade.php
resources/views/reports/supplier-purchase-invoice-aging.blade.php
resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php
resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php
```

## الاختبارات

تمت إضافة اختبارات تغطي تحسينات الاستخدام:

```text
tests/Feature/ReportSavedViewActiveUsageTest.php
tests/Feature/ReportSavedViewActiveSelectorTest.php
tests/Feature/ReportSavedViewClearActiveLinkTest.php
```

وتغطي الاختبارات:

- تطبيق العرض المحفوظ يضيف `saved_view_id` إلى رابط التقرير.
- ظهور تنبيه العرض النشط داخل التقرير.
- ظهور اسم العرض النشط.
- تمييز العرض النشط داخل قائمة العروض المحفوظة.
- تمرير `saved_view_id` داخل روابط العروض المحفوظة.
- ظهور رابط إزالة العرض النشط.
- إزالة `saved_view_id` من رابط الإزالة.
- الحفاظ على باقي الفلاتر عند إزالة العرض النشط.
- عدم ظهور رابط الإزالة عند عدم وجود عرض نشط.

## سلوك المستخدم النهائي

بعد هذه المرحلة، عند استخدام العروض المحفوظة:

1. يضغط المستخدم على تطبيق من صفحة إدارة العروض.
2. يفتح التقرير بالفلاتر المحفوظة.
3. يظهر تنبيه بأن التقرير مفتوح من عرض محفوظ.
4. يظهر نفس العرض داخل قائمة العروض المحفوظة كعرض نشط.
5. يستطيع المستخدم إلغاء العرض النشط بدون فقدان الفلاتر الحالية.

## قيود مقصودة

- `saved_view_id` لا يغير منطق الفلاتر نفسه، بل يستخدم فقط لتجربة الاستخدام والتمييز البصري.
- إلغاء العرض النشط لا يمس الفلاتر الحالية.
- إذا كان `saved_view_id` غير مطابق لأي عرض محفوظ ضمن قائمة التقرير، لا يظهر التنبيه.
- لا يتم تغيير `is_default` عند تطبيق العرض أو إلغاء العرض النشط.

## نتيجة المرحلة

أصبحت تجربة العروض المحفوظة أوضح داخل التقارير، حيث يمكن للمستخدم معرفة العرض النشط، والتنقل بين العروض مع بقاء الحالة واضحة، وإلغاء الربط بالعرض المحفوظ عند الحاجة دون فقدان الفلاتر.

