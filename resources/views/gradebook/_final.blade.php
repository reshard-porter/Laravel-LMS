@if ($letter)
<span class="grade"><span class="letter letter-{{ $letter }}">{{ $letter }}</span><span class="pct">{{ number_format($percent, 1) }}%</span>@if (\App\Services\GradeCalculator::isAtRisk($percent))<span class="flag flag-critical ms-1" title="Below {{ config('lms.at_risk_below') }}%"><i class="bi bi-exclamation-circle"></i></span>@endif</span>
@else
<span class="grade"><span class="letter letter-none">–</span><span class="muted">No scores</span></span>
@endif
