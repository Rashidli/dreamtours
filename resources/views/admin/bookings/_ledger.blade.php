{{-- Payment ledger (§7): payments and refunds, discounts and complimentary seats, with their actions. --}}
@php
  $cur = $booking->currency ?: 'CAD';
  $fmt = fn ($v) => money($v, $cur);
  $m = $moneySummary;
  $user = auth()->user();
@endphp
<div class="card bk-card bk-ledger"><div class="card-body">
  <div class="tf-head flex-wrap"><span class="tf-icon"><i class="ri-wallet-3-line"></i></span><div><h5>Payment ledger</h5><p>Payments and refunds, with discounts and complimentary seats.</p></div>
    <div class="d-flex flex-wrap gap-2 ms-auto">
      <a class="btn btn-light" href="{{ route('receipts.statement', $booking) }}" target="_blank"><i class="ri-file-list-3-line"></i> Statement</a>
      @can('record-payments')<button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#recordPayment"><i class="ri-add-line"></i> Record payment</button>@endcan
    </div>
  </div>
  <div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>Date</th><th>Receipt</th><th>Type</th><th>Method</th><th class="text-end">Amount</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($booking->payments as $p)
      @php([$statusLabel, $statusColor] = \App\Models\BookingPayment::STATUSES[$p->status])
      <tr class="{{ in_array($p->status, ['voided', 'rejected'], true) ? 'text-muted' : '' }} {{ ($p->note || $p->reason || $p->recorder || $p->confirmer) ? 'bk-has-note' : '' }}">
        <td class="text-nowrap">{{ $p->paid_on->format('M j, Y') }}</td>
        <td class="text-nowrap">{{ $p->receipt_number ?? '—' }}</td>
        <td>{{ $p->isRefund() ? 'Refund' : 'Payment' }}@if($p->isRefund() && $p->refundOf)<div class="bk-sub">of {{ $p->refundOf->receipt_number }}</div>@endif</td>
        <td>{{ \App\Models\BookingPayment::METHODS[$p->method] ?? $p->method }}@if($p->reference)<div class="bk-sub">{{ $p->reference }}</div>@endif</td>
        <td class="text-end text-nowrap fw-semibold {{ in_array($p->status, ['voided', 'rejected'], true) ? 'text-decoration-line-through' : ($p->isRefund() ? 'text-danger' : '') }}">{{ $p->isRefund() ? '−' : '' }}{{ $fmt($p->amount) }}</td>
        <td><span class="badge bg-{{ $statusColor }}">{{ $statusLabel }}</span></td>
        <td class="text-end text-nowrap"><span class="bk-row-actions">
          @if($p->status === 'confirmed' && $p->receipt_number)<a class="btn btn-sm btn-light" href="{{ route('receipts.payment', [$booking, $p]) }}" target="_blank" title="Receipt {{ $p->receipt_number }}"><i class="ri-file-text-line"></i></a>@endif
          @if($p->proof_path)<a class="btn btn-sm btn-light" href="{{ route('booking-payments.proof', [$booking, $p]) }}" target="_blank" title="View proof"><i class="ri-attachment-2"></i></a>@endif
          @if($p->status === 'pending' && $user->can('confirm-payments'))
            <form class="d-inline" method="post" action="{{ route('booking-payments.confirm', [$booking, $p]) }}" onsubmit="return confirm('Confirm that {{ $fmt($p->amount) }} has arrived?')">@csrf<button class="btn btn-sm btn-success" title="Funds arrived"><i class="ri-check-line"></i> Confirm</button></form>
            <button class="btn btn-sm btn-outline-danger" data-reason-action="{{ route('booking-payments.reject', [$booking, $p]) }}" data-reason-title="Reject this payment?" data-reason-button="Reject" title="Funds did not arrive"><i class="ri-close-line"></i></button>
          @endif
          @if($p->status === 'confirmed' && !$p->isRefund() && $user->can('refund-payments') && \App\Support\Money::cmp($p->refundable(), 0) > 0)
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#refundPayment" data-refund-action="{{ route('booking-payments.refund', [$booking, $p]) }}" data-refundable="{{ $p->refundable() }}" data-method="{{ $p->method }}" data-receipt="{{ $p->receipt_number }}" title="Refund"><i class="ri-arrow-go-back-line"></i></button>
          @endif
          @if(in_array($p->status, ['pending', 'confirmed'], true) && $user->can('refund-payments'))
            <button class="btn btn-sm btn-light text-muted" data-reason-action="{{ route('booking-payments.void', [$booking, $p]) }}" data-reason-title="Void this {{ $p->isRefund() ? 'refund' : 'payment' }}? (entered by mistake)" data-reason-button="Void" title="Void — entered by mistake"><i class="ri-delete-back-2-line"></i></button>
          @endif
        </span></td>
      </tr>
      @if($p->note || $p->reason || $p->recorder || $p->confirmer)
        <tr class="bk-note-row"><td></td><td colspan="6">
          {{ collect([$p->note, $p->reason ? ($p->isRefund() ? 'Reason: ' : ucfirst($p->status).': ').$p->reason : null, $p->recorder ? 'Recorded by '.$p->recorder->name : ($p->kind === 'payment' && !$p->recorded_by ? 'From the customer' : null), $p->confirmer && $p->status === 'confirmed' && $p->confirmed_by !== $p->recorded_by ? 'confirmed by '.$p->confirmer->name : null])->filter()->implode(' · ') }}
        </td></tr>
      @endif
    @empty
      <tr><td colspan="7" class="bk-empty"><i class="ri-wallet-3-line" aria-hidden="true"></i><strong>No payments yet</strong>@can('record-payments')Use “Record payment” when money arrives.@endcan</td></tr>
    @endforelse
    </tbody>
  </table></div>

  @if($booking->adjustments->isNotEmpty())
    <h6 class="bk-subhead">Discounts & complimentary seats</h6>
    <div class="table-responsive"><table class="table align-middle mb-0"><tbody>
      @foreach($booking->adjustments as $a)
        <tr class="{{ $a->status === 'voided' ? 'text-muted' : '' }}">
          <td class="text-nowrap">{{ $a->created_at->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, Y') }}</td>
          <td>{{ \App\Models\BookingAdjustment::KINDS[$a->kind] }}@if($a->passenger) · {{ $a->passenger->first_name }} {{ $a->passenger->last_name }}@endif<div class="bk-sub">{{ $a->reason }}{{ $a->creator ? ' · '.$a->creator->name : '' }}{{ $a->status === 'voided' ? ' · removed: '.$a->void_reason : '' }}</div></td>
          <td class="text-end text-nowrap fw-semibold text-success {{ $a->status === 'voided' ? 'text-decoration-line-through text-muted' : '' }}">− {{ $fmt($a->amount) }}</td>
          <td class="text-end">@if($a->status === 'active')@can('apply-discounts')<button class="btn btn-sm btn-light text-muted" data-reason-action="{{ route('booking-adjustments.void', [$booking, $a]) }}" data-reason-title="Remove this {{ strtolower(\App\Models\BookingAdjustment::KINDS[$a->kind]) }}?" data-reason-button="Remove" title="Remove"><i class="ri-delete-back-2-line"></i></button>@endcan @endif</td>
        </tr>
      @endforeach
    </tbody></table></div>
  @endif
</div></div>

{{-- Dialogs --}}
@can('record-payments')
<div class="modal fade" id="recordPayment" tabindex="-1" aria-labelledby="recordPaymentTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('booking-payments.store', $booking) }}" enctype="multipart/form-data">@csrf
  <div class="modal-header"><h5 class="modal-title" id="recordPaymentTitle">Record a payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <div class="row g-2">
      <div class="col-6"><label class="form-label">Amount ({{ $cur }})</label><input class="form-control" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', \App\Support\Money::cmp($m['outstanding'], 0) > 0 ? $m['outstanding'] : '') }}" required>
        <div class="form-text">Outstanding: {{ $fmt($m['outstanding']) }}@if(\App\Support\Money::cmp($m['deposit_outstanding'], 0) > 0) · deposit left: <a href="#" onclick="this.closest('form').amount.value='{{ $m['deposit_outstanding'] }}';return false">{{ $fmt($m['deposit_outstanding']) }}</a>@endif</div></div>
      <div class="col-6"><label class="form-label">Received on</label><input class="form-control" type="date" name="paid_on" value="{{ old('paid_on', now(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->toDateString()) }}" required></div>
      <div class="col-6"><label class="form-label">Method</label><select class="form-select" name="method">@foreach(\App\Models\BookingPayment::METHODS as $value => $label)<option value="{{ $value }}" @selected(old('method', 'etransfer') === $value)>{{ $label }}</option>@endforeach</select></div>
      <div class="col-6"><label class="form-label">Reference <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" name="reference" value="{{ old('reference') }}" maxlength="120" placeholder="e-Transfer ref, cheque no."></div>
      <div class="col-12"><label class="form-label">Proof <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" type="file" name="proof" accept="image/*,.heic,.heif,application/pdf"></div>
      <div class="col-12"><label class="form-label">Note <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" name="note" value="{{ old('note') }}" maxlength="2000"></div>
      <div class="col-12 mt-3">
        @can('confirm-payments')
          <div class="form-check"><input class="form-check-input" type="radio" name="status" id="pay-status-confirmed" value="confirmed" @checked(old('status', 'confirmed') === 'confirmed')><label class="form-check-label" for="pay-status-confirmed"><b>Funds received</b> — confirm now and issue a receipt</label></div>
          <div class="form-check"><input class="form-check-input" type="radio" name="status" id="pay-status-pending" value="pending" @checked(old('status') === 'pending')><label class="form-check-label" for="pay-status-pending">Not checked yet — record as pending verification</label></div>
        @else
          <input type="hidden" name="status" value="pending"><div class="alert alert-info mb-0">It will be recorded as <b>pending verification</b> until someone who can confirm payments checks it.</div>
        @endcan
      </div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save payment</button></div>
</form></div></div>
@endcan

@can('refund-payments')
<div class="modal fade" id="refundPayment" tabindex="-1" aria-labelledby="refundPaymentTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="">@csrf
  <div class="modal-header"><h5 class="modal-title" id="refundPaymentTitle">Refund</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <p class="text-muted mb-3">Refund from payment <b data-receipt></b>. Up to <b data-refundable></b> can be refunded.</p>
    <div class="row g-2">
      <div class="col-6"><label class="form-label">Amount ({{ $cur }})</label><input class="form-control" type="number" step="0.01" min="0.01" name="amount" required></div>
      <div class="col-6"><label class="form-label">Paid back by</label><select class="form-select" name="method">@foreach(\App\Models\BookingPayment::METHODS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
      <div class="col-12"><label class="form-label">Reason</label><input class="form-control" name="reason" maxlength="255" required placeholder="e.g. Cancelled 14 days before departure"></div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Record refund</button></div>
</form></div></div>
@endcan

@can('apply-discounts')
<div class="modal fade" id="addAdjustment" tabindex="-1" aria-labelledby="addAdjustmentTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('booking-adjustments.store', $booking) }}">@csrf
  <div class="modal-header"><h5 class="modal-title" id="addAdjustmentTitle">Discount or complimentary seat</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <p class="text-muted">Lowers what the customer owes (now {{ $fmt($m['due']) }}). It is not money received, so it never counts as a payment.</p>
    <div class="d-flex flex-wrap gap-3 mb-3">@foreach(\App\Models\BookingAdjustment::KINDS as $value => $label)<div class="form-check mb-0"><input class="form-check-input" type="radio" name="kind" id="adj-kind-{{ $value }}" value="{{ $value }}" @checked(old('kind', 'discount') === $value)><label class="form-check-label" for="adj-kind-{{ $value }}">{{ $label }}</label></div>@endforeach</div>
    <div class="mb-2" data-comp-only @if(old('kind', 'discount') !== 'complimentary') hidden @endif><label class="form-label">Passenger</label>
      <select class="form-select" name="booking_passenger_id"><option value="">—</option>@foreach($booking->passengers as $passenger)<option value="{{ $passenger->id }}" data-price="{{ $passenger->price }}">{{ $passenger->first_name }} {{ $passenger->last_name }}{{ $passenger->price !== null ? ' · '.$fmt($passenger->price) : '' }}</option>@endforeach</select></div>
    <div class="row g-2">
      <div class="col-5"><label class="form-label">Amount ({{ $cur }})</label><input class="form-control" type="number" step="0.01" min="0.01" max="{{ $m['due'] }}" name="amount" value="{{ old('amount') }}" required></div>
      <div class="col-7"><label class="form-label">Reason</label><input class="form-control" name="reason" value="{{ old('reason') }}" maxlength="255" required placeholder="e.g. Repeat guest 10%"></div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Apply</button></div>
</form></div></div>
@endcan

@can('edit-bookings')
<div class="modal fade" id="paymentTerms" tabindex="-1" aria-labelledby="paymentTermsTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="{{ route('booking-payments.terms', $booking) }}">@csrf @method('PUT')
  <div class="modal-header"><h5 class="modal-title" id="paymentTermsTitle">Payment terms for this booking</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body">
    <p class="text-muted">Only for exceptions — defaults come from the tour or Settings (balance due {{ (int) ($booking->tour?->balance_due_days ?? setting('balance_due_days', 7)) }} days before departure).</p>
    <div class="row g-2">
      <div class="col-6"><label class="form-label">Deposit ({{ $cur }})</label><input class="form-control" type="number" step="0.01" min="0" max="{{ (float) $booking->total_amount }}" name="deposit_amount" value="{{ old('deposit_amount', $booking->deposit_amount) }}" required></div>
      <div class="col-6"><label class="form-label">Balance due on</label><input class="form-control" type="date" name="balance_due_date" value="{{ old('balance_due_date', $booking->balance_due_date?->toDateString()) }}" required></div>
      <div class="col-12"><label class="form-label">Why <span class="text-muted fw-normal">(optional)</span></label><input class="form-control" name="payment_terms_note" value="{{ old('payment_terms_note', $booking->payment_terms_note) }}" maxlength="255" placeholder="e.g. Group rate, balance on arrival"></div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save terms</button></div>
</form></div></div>
@endcan

{{-- One dialog for every action that needs a reason (reject, void, remove). --}}
<div class="modal fade" id="reasonDialog" tabindex="-1" aria-labelledby="reasonDialogTitle" aria-hidden="true"><div class="modal-dialog"><form class="modal-content" method="post" action="">@csrf
  <div class="modal-header"><h5 class="modal-title" id="reasonDialogTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body"><label class="form-label">Reason</label><input class="form-control" name="reason" maxlength="255" required></div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" data-submit></button></div>
</form></div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const reason = document.getElementById('reasonDialog');
  document.querySelectorAll('[data-reason-action]').forEach(btn => btn.addEventListener('click', () => {
    const form = reason.querySelector('form');
    form.action = btn.dataset.reasonAction;
    form.reason.value = '';
    reason.querySelector('.modal-title').textContent = btn.dataset.reasonTitle;
    reason.querySelector('[data-submit]').textContent = btn.dataset.reasonButton;
    bootstrap.Modal.getOrCreateInstance(reason).show();
  }));
  const refund = document.getElementById('refundPayment');
  refund?.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget, form = refund.querySelector('form');
    form.action = b.dataset.refundAction;
    form.amount.value = b.dataset.refundable;
    form.amount.max = b.dataset.refundable;
    form.method.value = b.dataset.method;
    form.reason.value = '';
    refund.querySelector('[data-refundable]').textContent = '{{ $cur === 'CAD' ? 'CA$' : $cur.' ' }}' + Number(b.dataset.refundable).toFixed(2);
    refund.querySelector('[data-receipt]').textContent = b.dataset.receipt;
  });
  const adjust = document.getElementById('addAdjustment');
  adjust?.querySelectorAll('[name=kind]').forEach(r => r.addEventListener('change', () => { adjust.querySelector('[data-comp-only]').hidden = !adjust.querySelector('[name=kind][value=complimentary]').checked; }));
  adjust?.querySelector('[name=booking_passenger_id]').addEventListener('change', e => { const price = e.target.selectedOptions[0]?.dataset.price; if (price) adjust.querySelector('[name=amount]').value = price; });
  // After a validation error, reopen the dialog that was being filled in.
  const reopen = id => { const el = document.getElementById(id); if (el) bootstrap.Modal.getOrCreateInstance(el).show(); };
  @if($errors->hasAny(['amount', 'method', 'paid_on', 'proof']) && !$errors->has('reason')) reopen('recordPayment'); @endif
  @if($errors->hasAny(['deposit_amount', 'balance_due_date'])) reopen('paymentTerms'); @endif
});
</script>
