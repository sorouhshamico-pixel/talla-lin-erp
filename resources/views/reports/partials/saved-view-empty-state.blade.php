@php
    $emptyMessage = $message ?? 'لا توجد عروض محفوظة لهذا التقرير حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.';
@endphp

<p class="saved-view-empty-state" data-testid="{{ $testId }}">
    {{ $emptyMessage }}
</p>
