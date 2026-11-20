{{-- The bell's list: newest open alerts this person may act on (fetched into the header's [data-alerts-list]).
     Styles are scoped here because this shows on every admin page. --}}
@php
  $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
  $iconFix = ['ri-funnel-line' => 'ri-user-received-line'];
  $tones = ['danger' => ['#fedad9', '#8e0b21'], 'warning' => ['#fff1b8', '#5e4200'], 'info' => ['#e0f0ff', '#00527c'], 'primary' => ['#eaf4ff', '#005bd3'], 'success' => ['#cdfee1', '#0c5132'], 'secondary' => ['#ebebeb', '#616161']];
@endphp
<style>
.al-dd-list{max-height:min(420px,calc(100vh - 160px));overflow-y:auto;overscroll-behavior:contain}
.al-dd-item{display:flex;align-items:flex-start;gap:10px;padding:10px 12px 10px 14px;border-bottom:1px solid #f1f1f1}
.al-dd-item:hover{background:#f7f7f7}
.al-dd-icon{width:32px;height:32px;flex:0 0 32px;border-radius:8px;display:grid;place-items:center;font-size:16px}
.al-dd-text{flex:1;min-width:0;color:#303030;text-decoration:none}
.al-dd-text:hover{color:#303030}
.al-dd-title{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-weight:650;font-size:13.5px;line-height:1.35}
.al-dd-body{display:block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;color:#616161;font-size:13px}
.al-dd-meta{display:block;color:#8a8a8a;font-size:12px;margin-top:1px}
.al-dd-done{margin:0}
.al-dd-done .btn{width:32px;height:32px;padding:0;display:grid;place-items:center}
.al-dd-foot{display:block;padding:10px 12px;text-align:center;font-weight:600;background:#f7f7f7;border-top:1px solid #ebebeb;border-radius:0 0 12px 12px;text-decoration:none}
.al-dd-empty{padding:28px 16px;text-align:center;color:#616161}
.al-dd-empty i{display:grid;place-items:center;width:44px;height:44px;margin:0 auto 8px;border-radius:50%;background:#cdfee1;color:#29845a;font-size:22px}
.al-dd-empty strong{display:block;color:#303030;font-size:14px}
</style>
@if($alerts->isEmpty())
  <div class="al-dd-empty"><i class="ri-check-double-line"></i><strong>You're all caught up</strong>New inquiries, payments to verify and tour updates show up here.</div>
@else
  <div class="al-dd-list">
  @foreach($alerts as $alert)
    @php
      [$aLabel, $aIcon, $aColour] = \App\Support\Alerts::TYPES[$alert->type] ?? ['Alert', 'ri-notification-3-line', 'secondary'];
      [$bg, $fg] = $tones[$aColour] ?? $tones['secondary'];
    @endphp
    <div class="al-dd-item">
      <span class="al-dd-icon" style="background:{{ $bg }};color:{{ $fg }}" aria-hidden="true"><i class="{{ $iconFix[$aIcon] ?? $aIcon }}"></i></span>
      <a href="{{ $alert->url ?: route('alerts.index') }}" class="al-dd-text">
        <span class="al-dd-title">{{ str($alert->title)->limit(90) }}</span>
        @if($alert->body)<span class="al-dd-body">{{ $alert->body }}</span>@endif
        <span class="al-dd-meta" title="{{ $alert->created_at->copy()->timezone($tz)->format('D, M j, g:i A') }}"><span style="color:{{ $fg }};font-weight:600">{{ $aLabel }}</span> · {{ $alert->created_at->diffForHumans() }}</span>
      </a>
      <form method="post" action="{{ route('alerts.complete', $alert) }}" class="al-dd-done">@csrf<button class="btn btn-light" title="Mark done" aria-label="Mark “{{ str($alert->title)->limit(40) }}” done"><i class="ri-check-line"></i></button></form>
    </div>
  @endforeach
  </div>
  <a class="al-dd-foot" href="{{ route('alerts.index') }}">See all alerts <i class="ri-arrow-right-s-line"></i></a>
@endif
