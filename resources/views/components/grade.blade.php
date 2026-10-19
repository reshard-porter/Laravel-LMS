@props(['percent' => null, 'letter' => null, 'showPercent' => true])
@php($letter = $letter ?? \App\Services\GradeCalculator::letter($percent))
<span {{ $attributes->merge(['class' => 'grade']) }}>
    @if ($letter)
        <span class="letter letter-{{ $letter }}" aria-label="Grade {{ $letter }}">{{ $letter }}</span>
        @if ($showPercent)<span>{{ number_format($percent, 1) }}%</span>@endif
    @else
        <span class="letter letter-none" aria-label="No grade yet">–</span>
        @if ($showPercent)<span class="muted">No grades yet</span>@endif
    @endif
</span>
