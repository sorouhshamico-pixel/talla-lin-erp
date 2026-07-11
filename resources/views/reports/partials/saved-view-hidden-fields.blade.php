@foreach (($hiddenFields ?? []) as $hiddenFieldName => $hiddenFieldValue)
    <input type="hidden" name="{{ $hiddenFieldName }}" value="{{ $hiddenFieldValue }}">
@endforeach
