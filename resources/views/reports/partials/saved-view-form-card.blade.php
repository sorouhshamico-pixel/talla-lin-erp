<div class="card" data-testid="{{ $cardTestId ?? 'saved-view-form-card' }}">
    <h2>{{ $title ?? 'حفظ عرض التقرير' }}</h2>

    <form method="POST"
          action="{{ route($storeRouteName) }}"
          data-testid="{{ $formTestId ?? 'saved-view-form' }}">
        @csrf
        @include('reports.partials.saved-view-hidden-fields', ['hiddenFields' => $hiddenFields ?? []])
        @include('reports.partials.saved-view-form-fields', [
            'nameInputId' => $nameInputId ?? null,
            'namePlaceholder' => $namePlaceholder ?? null,
            'nameInputTestId' => $nameInputTestId ?? null,
            'defaultCheckboxTestId' => $defaultCheckboxTestId ?? null,
            'saveButtonTestId' => $saveButtonTestId ?? null,
        ])
    </form>
</div>
