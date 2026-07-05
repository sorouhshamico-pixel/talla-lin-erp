# Phase 24 — Expense Supplier Linking

## الهدف

ربط المصروفات التشغيلية بالموردين حتى يمكن متابعة مصروفات كل مورد من داخل شاشة المصروفات والتقارير، مع الاستفادة من الربط في كشوف حساب الموردين.

## النطاق المنفذ

### 24A — Expense Supplier Selection

تمت إضافة اختيار المورد في نماذج المصروفات:

- إضافة حقل المورد في صفحة إنشاء المصروف.
- إضافة حقل المورد في صفحة تعديل المصروف.
- حفظ `supplier_id` عند إنشاء المصروف.
- تحديث `supplier_id` عند تعديل المصروف.
- السماح بأن يكون المورد اختياريًا.
- التحقق من أن المورد، عند اختياره، موجود في جدول `suppliers`.

الاختبار المرتبط:

- `tests/Feature/ExpenseSupplierSelectionTest.php`

### 24B — Expense Supplier Index Display

تم إظهار المورد في قائمة المصروفات:

- تحميل علاقة `supplier` مع المصروفات.
- إضافة عمود المورد في جدول المصروفات.
- عرض اسم المورد عند وجوده.
- عرض شرطة `-` عند عدم وجود مورد مرتبط بالمصروف.

الاختبار المرتبط:

- `tests/Feature/ExpenseSupplierIndexDisplayTest.php`

### 24C — Expense Supplier Filter

تمت إضافة فلتر المورد في شاشة المصروفات:

- إضافة `supplier_id` إلى فلاتر المصروفات.
- تحميل قائمة الموردين في صفحة المصروفات.
- إضافة قائمة اختيار المورد في نموذج الفلترة.
- تطبيق الفلتر على نتائج المصروفات.
- الحفاظ على الفلتر داخل روابط الفلاتر السريعة والتصدير.

الاختبار المرتبط:

- `tests/Feature/ExpenseSupplierFilterTest.php`

### 24D — Expense Supplier CSV Export

تم إدخال المورد في تقارير CSV الخاصة بالمصروفات:

- إضافة عمود المورد إلى تصدير المصروفات CSV.
- عرض اسم المورد في صف المصروف المرتبط بمورد.
- ترك قيمة المورد فارغة عند عدم وجود مورد.
- احترام فلتر `supplier_id` في التصدير.
- تنظيف تكرارات محدودة في `ExpenseController` تخص `suppliers` و `supplier_id`.

الاختبار المرتبط:

- `tests/Feature/ExpenseSupplierCsvExportTest.php`

### 24E — Expense Supplier Summary Card

تمت إضافة بطاقة ملخص للمورد المحدد عند استخدام فلتر المورد:

- عرض بطاقة فقط عند وجود فلتر `supplier_id`.
- عرض اسم المورد المحدد.
- عرض عدد المصروفات الخاصة بالمورد ضمن الفلاتر الحالية.
- عرض إجمالي المصروفات الخاصة بالمورد ضمن الفلاتر الحالية.
- إخفاء البطاقة عند عدم اختيار مورد.

الاختبار المرتبط:

- `tests/Feature/ExpenseSupplierSummaryCardTest.php`

## الملفات الرئيسية المتأثرة

- `app/Http/Controllers/ExpenseController.php`
- `resources/views/expenses/create.blade.php`
- `resources/views/expenses/edit.blade.php`
- `resources/views/expenses/index.blade.php`
- `tests/Feature/ExpenseSupplierSelectionTest.php`
- `tests/Feature/ExpenseSupplierIndexDisplayTest.php`
- `tests/Feature/ExpenseSupplierFilterTest.php`
- `tests/Feature/ExpenseSupplierCsvExportTest.php`
- `tests/Feature/ExpenseSupplierSummaryCardTest.php`

## النتيجة

أصبحت المصروفات التشغيلية قابلة للربط بالموردين من دورة العمل الكاملة:

1. اختيار المورد عند إنشاء المصروف.
2. تعديل المورد لاحقًا.
3. عرض المورد في قائمة المصروفات.
4. فلترة المصروفات حسب المورد.
5. تصدير بيانات المورد ضمن CSV.
6. عرض ملخص سريع لمصروفات المورد المحدد.

هذا الربط يدعم دقة كشوف حساب الموردين ويجعل شاشة المصروفات أكثر فائدة في التحليل والمتابعة.
