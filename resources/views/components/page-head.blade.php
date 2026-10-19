@props(['title', 'sub' => null, 'crumbs' => []])
<div class="page-head">
    <div>
        @if ($crumbs)
            <div class="crumbs">
                @foreach ($crumbs as $label => $url)
                    <a href="{{ $url }}">{{ $label }}</a>@if (! $loop->last) / @endif
                @endforeach
            </div>
        @endif
        <h1>{{ $title }}</h1>
        @if ($sub)<p class="sub">{{ $sub }}</p>@endif
    </div>
    @if (trim($slot))
        <div class="page-actions">{{ $slot }}</div>
    @endif
</div>
