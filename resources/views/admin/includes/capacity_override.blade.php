{{-- Overbooking (CRM §8, AT-05): only for people with override-capacity, and only with a reason. --}}
@can('override-capacity')
@php($oid = 'cap-'.($id ?? uniqid()))
<div class="mt-2">
  <div class="form-check"><input class="form-check-input" type="checkbox" id="{{ $oid }}" @checked(old('override_reason')) onchange="document.getElementById('{{ $oid }}-r').classList.toggle('d-none', !this.checked); if (!this.checked) document.getElementById('{{ $oid }}-r').querySelector('input').value = ''"><label class="form-check-label" for="{{ $oid }}">Book over capacity</label></div>
  <div class="{{ old('override_reason') ? '' : 'd-none' }} mt-1" id="{{ $oid }}-r"><input class="form-control" name="override_reason" maxlength="255" value="{{ old('override_reason') }}" placeholder="Why? e.g. extra minivan arranged"><div class="form-text">Only used if the date is full. The reason is kept in the activity history.</div></div>
</div>
@endcan
