{{-- Current attendance of one passenger and, for people allowed to, the buttons to change it.
     Steps back (an undo) are only offered to those who may undo; the server checks again.
     Layout for a leader at the bus door: the state first, then the next likely marks as big buttons
     (Checked in / No-show, or Boarded once checked in), the rarer ones smaller, and Undo apart. --}}
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $status = $p->attendance_status;
  $size = $size ?? 'btn-sm';
  $mayUndo = $canMark && \App\Support\Attendance::canUndo(auth()->user(), $p);
  $order = ['checked_in', 'no_show', 'boarded', 'not_arrived'];
  $forward = collect($order)->filter(fn ($v) => $v !== $status && !\App\Support\Attendance::isUndo($status, $v))->values();
  $back = $mayUndo ? collect($order)->filter(fn ($v) => $v !== $status && \App\Support\Attendance::isUndo($status, $v))->values() : collect();
@endphp
<div class="rs-mark">
<div class="rs-state">
  @if($status)
    @php
      [$label, $color, $icon] = \App\Support\Attendance::STATUSES[$status];
    @endphp
    <span class="badge rounded-pill bg-{{ $color }}"><i class="{{ $icon }} me-1"></i>{{ $label }}</span>
    <span class="text-muted ms-1 rs-state-when">{{ $p->attendance_at?->timezone($tz)->format('g:i A') }}@if($p->attendanceBy) · {{ $p->attendanceBy->name }}@endif</span>
    @if($p->attendance_note)<span class="sub rs-state-note">“{{ $p->attendance_note }}”</span>@endif
  @else
    <span class="badge rounded-pill bg-light text-body">Expected</span>
  @endif
</div>
@if($canMark)
  <form method="post" action="{{ route('passenger-attendance.update', [$departure, $p]) }}" class="mt-2 d-print-none rs-mark-form">@csrf
    {{-- Enter in the note field saves the note (same as the Save button), never a mark further down. --}}
    <button class="d-none" name="status" value="{{ $status ?? 'checked_in' }}" tabindex="-1" aria-hidden="true"></button>
    @if($forward->isNotEmpty())
    <div class="d-flex flex-wrap gap-1 rs-go-row">
      @foreach($forward as $k => $value)
        @php
          [$label, $color, $icon] = \App\Support\Attendance::STATUSES[$value];
        @endphp
        <button class="btn {{ $size }} btn-outline-{{ $color }} rs-go rs-go-{{ $value }} {{ $k < 2 ? 'rs-go-main' : 'rs-go-alt' }}" name="status" value="{{ $value }}"><i class="{{ $icon }} me-1"></i>{{ $label }}</button>
      @endforeach
    </div>
    @endif
    @if($status && $mayUndo)
    <div class="d-flex flex-wrap align-items-center gap-1 mt-1 rs-back-row">
      <button class="btn {{ $size }} btn-light rs-undo" name="status" value="expected" title="Back to expected (clears this mark)"><i class="ri-arrow-go-back-line me-1"></i>Undo</button>
      @if($back->isNotEmpty())
        <span class="text-muted rs-back-label">or change to</span>
        @foreach($back as $value)
          <button class="btn {{ $size }} btn-link rs-back" name="status" value="{{ $value }}" title="Undo: changes the earlier mark">{{ \App\Support\Attendance::label($value) }}</button>
        @endforeach
      @endif
    </div>
    @endif
    <details class="mt-1 rs-note"><summary class="text-muted" style="cursor:pointer">Add a note</summary>
      <div class="input-group mt-1"><input class="form-control" name="note" maxlength="255" placeholder="e.g. Called, 5 min late" aria-label="Note"><button class="btn btn-outline-primary" name="status" value="{{ $status ?? 'checked_in' }}">Save{{ $status ? '' : ' & check in' }}</button></div>
    </details>
  </form>
@endif
</div>
