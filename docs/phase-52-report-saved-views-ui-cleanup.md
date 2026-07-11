# Phase 52 — Report Saved Views UI Cleanup

## الهدف

تنظيف وتحسين واجهة العروض المحفوظة داخل التقارير بعد اكتمال إدارة العروض المحفوظة وتحسين تجربة الاستخدام في Phase 50 وPhase 51.

تركز هذه المرحلة على توحيد شكل قائمة العروض المحفوظة، شارات الحالة، نصوص المساعدة، وحالة عدم وجود عروض محفوظة عبر التقارير التي تدعم العروض المحفوظة.

## النطاق الوظيفي

### Phase 52A — Report Saved Views UI Cleanup

تم توحيد واجهة قائمة العروض المحفوظة داخل التقارير التي تحتوي على saved views.

تمت إضافة partial مشترك للتنسيقات:

```text
resources/views/reports/partials/saved-view-list-styles.blade.php
```

ويحتوي على تنسيقات موحدة للعناصر التالية:

```text
.saved-views-list
.saved-view-row
.saved-view-row.active-saved-view-row
.saved-view-link
.saved-view-badges
.saved-view-badge
.saved-view-badge-active
.saved-view-badge-default
```

### شارة العرض النشط

تم توحيد شكل شارة:

```text
نشط
```

باستخدام:

```text
saved-view-badge saved-view-badge-active
```

### شارة العرض الافتراضي

تم توحيد شكل شارة:

```text
افتراضي
```

باستخدام:

```text
saved-view-badge saved-view-badge-default
```

### رابط فتح العرض المحفوظ

تم توحيد رابط فتح العرض المحفوظ عبر class:

```text
saved-view-link
```

### صف العرض المحفوظ

تم توحيد شكل صف العرض المحفوظ عبر:

```text
saved-view-row
```

وعند وجود عرض نشط يتم إضافة:

```text
active-saved-view-row
```

## Phase 52B — Saved Views Empty State And Help Text Cleanup

تم توحيد نصوص المساعدة وحالات عدم وجود عروض محفوظة.

### نص المساعدة المشترك

تمت إضافة partial:

```text
resources/views/reports/partials/saved-view-help-text.blade.php
```

ويعرض نصًا موحدًا لقسم العروض المحفوظة:

```text
استخدم العروض المحفوظة للرجوع سريعًا إلى نفس الفلاتر، أو طبّق عرضًا محفوظًا من صفحة الإدارة.
```

### حالة عدم وجود عروض محفوظة

تمت إضافة partial:

```text
resources/views/reports/partials/saved-view-empty-state.blade.php
```

ويدعم:

- `testId` للحفاظ على اختبارات كل تقرير.
- `message` اختياري لتخصيص النص حسب نوع التقرير.

النص الافتراضي للتقارير:

```text
لا توجد عروض محفوظة لهذا التقرير حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.
```

النص المستخدم في تقارير التفاصيل drilldown:

```text
لا توجد عروض محفوظة لهذه التفاصيل حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.
```

## التقارير المتأثرة

تم تطبيق تحسينات Phase 52 على التقارير التالية:

```text
resources/views/reports/sales-invoice-aging.blade.php
resources/views/reports/customer-sales-invoice-aging.blade.php
resources/views/reports/supplier-purchase-invoice-aging.blade.php
resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php
resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php
```

## الملفات الجديدة

```text
resources/views/reports/partials/saved-view-list-styles.blade.php
resources/views/reports/partials/saved-view-help-text.blade.php
resources/views/reports/partials/saved-view-empty-state.blade.php
tests/Feature/ReportSavedViewUiCleanupTest.php
tests/Feature/ReportSavedViewEmptyStateCleanupTest.php
```

## الاختبارات

تمت إضافة اختبارات تغطي:

```text
tests/Feature/ReportSavedViewUiCleanupTest.php
tests/Feature/ReportSavedViewEmptyStateCleanupTest.php
```

كما تم الحفاظ على توافق الاختبارات السابقة، خصوصًا اختبارات selectors لتقارير الدريل داون التي تتوقع نص:

```text
لا توجد عروض محفوظة لهذه التفاصيل حتى الآن.
```

## قيود مقصودة

- تم وضع التنسيقات في partial داخل Blade لتقليل التغيير على بنية الواجهة الحالية.
- لم يتم نقل CSS إلى ملف assets مستقل في هذه المرحلة.
- لم يتم تغيير routes أو controllers.
- لم يتم تغيير منطق حفظ أو تطبيق العروض المحفوظة.
- تم الحفاظ على data-testid الحالية حتى لا تنكسر الاختبارات السابقة.

## نتيجة المرحلة

أصبحت واجهة العروض المحفوظة داخل التقارير أكثر اتساقًا ووضوحًا:

- شارات موحدة.
- صفوف موحدة.
- روابط موحدة.
- نص مساعدة موحد.
- حالة فارغة موحدة مع مراعاة تقارير التفاصيل.
- اختبارات تغطي السلوك البصري والنصي الأساسي.

