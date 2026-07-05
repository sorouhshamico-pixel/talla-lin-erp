# Phase 25 — Supplier Expense Page Enhancements

## الهدف

تحسين صفحة تفاصيل المورد بعد ربط المصروفات بالموردين، بحيث تصبح صفحة المورد نقطة متابعة مباشرة للمصروفات المرتبطة به، والمدفوع، وغير المدفوع، وروابط التصدير.

## النطاق المنفذ

### 25A — Supplier Expense Summary On Supplier Page

تمت إضافة ملخص عام لمصروفات المورد داخل صفحة المورد:

- إضافة علاقة `expenses()` إلى `App\Models\Supplier`.
- احتساب عدد المصروفات المرتبطة بالمورد.
- احتساب إجمالي مصروفات المورد.
- عرض بطاقة `supplier-expense-summary-card`.
- إضافة رابط مباشر إلى صفحة المصروفات مفلترة بـ `supplier_id`.

الاختبار المرتبط:

- `tests/Feature/SupplierExpenseSummaryPageTest.php`

### 25B — Supplier Recent Expenses On Supplier Page

تمت إضافة قائمة آخر مصروفات المورد:

- عرض آخر 5 مصروفات مرتبطة بالمورد.
- إظهار تاريخ المصروف.
- إظهار وصف المصروف.
- إظهار المبلغ.
- إظهار حالة الدفع.
- إضافة رابط تعديل لكل مصروف.
- عرض رسالة فراغ عند عدم وجود مصروفات.

الاختبار المرتبط:

- `tests/Feature/SupplierRecentExpensesPageTest.php`

### 25C — Supplier Unpaid Expenses Summary

تمت إضافة ملخص مستقل للمصروفات غير المدفوعة:

- احتساب عدد مصروفات المورد غير المدفوعة.
- احتساب إجمالي غير المدفوع.
- عرض حالة متابعة مناسبة.
- إضافة رابط مباشر إلى صفحة المصروفات مفلترة بـ:
  - `supplier_id`
  - `payment_status=unpaid`

الاختبار المرتبط:

- `tests/Feature/SupplierUnpaidExpensesSummaryPageTest.php`

### 25D — Supplier Paid Expenses Summary

تمت إضافة ملخص مستقل للمصروفات المدفوعة:

- احتساب عدد مصروفات المورد المدفوعة.
- احتساب إجمالي المدفوع.
- عرض حالة متابعة مناسبة.
- إضافة رابط مباشر إلى صفحة المصروفات مفلترة بـ:
  - `supplier_id`
  - `payment_status=paid`

الاختبار المرتبط:

- `tests/Feature/SupplierPaidExpensesSummaryPageTest.php`

### 25E — Supplier Expense Export Links

تمت إضافة روابط تصدير مباشرة من صفحة المورد:

- تصدير كل مصروفات المورد CSV.
- تصدير مصروفات المورد غير المدفوعة CSV.
- تصدير مصروفات المورد المدفوعة CSV.
- تمرير `supplier_id` في كل روابط التصدير.
- تمرير `payment_status` المناسب في روابط المدفوع وغير المدفوع.

الاختبار المرتبط:

- `tests/Feature/SupplierExpenseExportLinksTest.php`

## الملفات الرئيسية المتأثرة

- `app/Models/Supplier.php`
- `app/Http/Controllers/SupplierController.php`
- `resources/views/suppliers/show.blade.php`
- `tests/Feature/SupplierExpenseSummaryPageTest.php`
- `tests/Feature/SupplierRecentExpensesPageTest.php`
- `tests/Feature/SupplierUnpaidExpensesSummaryPageTest.php`
- `tests/Feature/SupplierPaidExpensesSummaryPageTest.php`
- `tests/Feature/SupplierExpenseExportLinksTest.php`

## النتيجة

أصبحت صفحة المورد تعرض رؤية مالية وتشغيلية أوضح للمصروفات المرتبطة به:

1. ملخص إجمالي المصروفات.
2. آخر المصروفات المسجلة.
3. ملخص غير المدفوع.
4. ملخص المدفوع.
5. روابط فتح صفحة المصروفات مفلترة حسب المورد.
6. روابط تصدير CSV حسب المورد وحالة الدفع.

هذا يعزز متابعة الموردين ويقلل الحاجة للانتقال اليدوي بين صفحات الموردين والمصروفات.
