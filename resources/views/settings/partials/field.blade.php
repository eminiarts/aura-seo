@php
    $seoFieldOwner = method_exists($this, 'mappedFieldBySlug') ? $this : $this->model;
    $seoInputField = $seoFieldOwner->mappedFieldBySlug($slug);

    if ($seoInputField) {
        foreach ($seoInputField as $attribute => $value) {
            if (! $value instanceof \Closure) {
                continue;
            }

            if ($attribute === 'disabled') {
                $seoInputField[$attribute] = (bool) $value($this->model);
            } else {
                unset($seoInputField[$attribute]);
            }
        }
    }
@endphp

@if($seoInputField)
    <x-dynamic-component
        :component="$seoInputField['field']->edit()"
        mode="edit"
        :field="$seoInputField"
        :form="$this->form"
    />
@endif
