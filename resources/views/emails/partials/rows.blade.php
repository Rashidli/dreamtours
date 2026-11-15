{{-- Label / value card. $rows: [label => value]; empty values are skipped, values are escaped. $title optional. --}}
@php($rows = collect($rows)->filter(fn ($value) => filled($value)))
@if($rows->isNotEmpty())
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0 0;border:1px solid #ebe7e0;border-radius:12px;border-collapse:separate;">
  @isset($title)
    <tr><td colspan="2" style="padding:14px 18px 4px;font-size:12px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#9aa0ad;">{{ $title }}</td></tr>
  @endisset
  @foreach($rows as $label => $value)
    <tr class="dt-stack">
      <td class="dt-label" valign="top" style="padding:10px 18px;width:38%;font-size:14px;color:#6b7280;{{ $loop->first && !isset($title) ? '' : 'border-top:1px solid #f1eee8;' }}">{{ $label }}</td>
      <td valign="top" style="padding:10px 18px;font-size:14px;color:#141824;font-weight:600;{{ $loop->first && !isset($title) ? '' : 'border-top:1px solid #f1eee8;' }}">{!! nl2br(e($value)) !!}</td>
    </tr>
  @endforeach
</table>
@endif
