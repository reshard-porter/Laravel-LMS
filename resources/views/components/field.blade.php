@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])
@php($id = 'f_'.str_replace(['[', ']', '.'], '_', $name))
<div {{ $attributes->only('class')->merge(['class' => 'mb-3']) }}>
    <label for="{{ $id }}" class="form-label">{{ $label }}@unless ($required)<span class="muted fw-normal"> (optional)</span>@endunless</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="3"
                  class="form-control @error($name) is-invalid @enderror"
                  {{ $attributes->except('class') }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
               class="form-control @error($name) is-invalid @enderror" @if ($required) required @endif
               {{ $attributes->except('class') }}>
    @endif
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
