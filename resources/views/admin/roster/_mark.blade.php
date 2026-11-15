{{-- Current attendance of one passenger and, for people allowed to, the buttons to change it.
     Steps back (an undo) are only offered to those who may undo; the server checks again. --}}
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $status = $p->attendance_status;
  $size = $size ?? 'btn-sm';
  $mayUndo = $canMark && \App\Support\Attendance::canUndo(auth()->user(), $p);
@endphp
<div>
  @if($status)
    @php([$label, $color, $icon] = \App\Support\Attendance::STATUSES[$status])
    <span class="badge rounded-pill bg-{{ $color }}"><i class="{{ $icon }} me-1"></i>{{ $label }}</span>
    <span class="text-muted ms-1">{{ $p->attendance_at?->timezone($tz)->format('g:i A') }}@if($p->attendanceBy) · {{ $p->attendanceBy->name }}@endif</span>
    @if($p->attendance_note)<span class="sub">“{{ $p->attendance_note }}”</span>@endif
  @else
    <span class="badge rounded-pill bg-light text-body">Expected</span>
  @endif
</div>
@if($canMark)
  <form method="post" action="{{ route('passenger-attendance.update', [$departure, $p]) }}" class="mt-2 d-print-none">@csrf
    <div class="d-flex flex-wrap gap-1">
      @foreach(\App\Support\Attendance::STATUSES as $value => [$label, $color, $icon])
        @continue($value === $status)
        @php($undo = \App\Support\Attendance::isUndo($status, $value))
        @if(!$undo || $mayUndo)
          <button class="btn {{ $size }} {{ $undo ? 'btn-outline-secondary' : 'btn-outline-'.$color }}" name="status" value="{{ $value }}" @if($undo) title="Undo: changes the earlier mark" @endif><i class="{{ $icon }} me-1"></i>{{ $label }}</button>
        @endif
      @endforeach
      @if($status && $mayUndo)<button class="btn {{ $size }} btn-link text-muted" name="status" value="expected" title="Back to expected">Clear</button>@endif
    </div>
    <details class="mt-1"><summary class="text-muted" style="cursor:pointer">Add a note</summary>
      <div class="input-group mt-1"><input class="form-control" name="note" maxlength="255" placeholder="e.g. Called, 5 min late" aria-label="Note"><button class="btn btn-outline-primary" name="status" value="{{ $status ?? 'checked_in' }}">Save{{ $status ? '' : ' & check in' }}</button></div>
    </details>
  </form>
@endif
