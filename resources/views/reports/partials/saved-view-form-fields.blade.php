<div class="grid">
    <div class="metric">
        <label class="metric-label" for="{{ $nameInputId ?? 'saved_view_name' }}">اسم العرض المحفوظ</label>
        <input id="{{ $nameInputId ?? 'saved_view_name' }}"
               type="text"
               name="name"
               placeholder="{{ $namePlaceholder ?? 'مثال: متابعة التحصيل الجزئي' }}"
               required
               maxlength="120"
               style="width:100%;padding:10px;border:1px solid #e7dcd2;border-radius:10px;"
               data-testid="{{ $nameInputTestId ?? 'saved-view-name-input' }}">
    </div>

    <div class="metric">
        <div class="metric-label">خيارات العرض</div>
        <label>
            <input type="checkbox"
                   name="is_default"
                   value="1"
                   data-testid="{{ $defaultCheckboxTestId ?? 'saved-view-default-checkbox' }}">
            تعيين كعرض افتراضي لهذا التقرير
        </label>
    </div>

    <div class="metric">
        <div class="metric-label">الإجراء</div>
        <button type="submit"
                class="btn"
                data-testid="{{ $saveButtonTestId ?? 'saved-view-save-button' }}">
            حفظ العرض
        </button>
    </div>
</div>
