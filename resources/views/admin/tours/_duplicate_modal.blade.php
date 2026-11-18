{{-- One “Duplicate tour” dialog per page. Buttons open it with data-duplicate-* attributes:
     url, name, days (itinerary days), items (additional info), dates (future dates). --}}
@can('create-tours')
<div class="modal fade" id="tour-duplicate" tabindex="-1" aria-labelledby="tour-duplicate-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form method="post" action="" id="tour-duplicate-form">@csrf
    <div class="modal-header">
      <h5 class="modal-title" id="tour-duplicate-title"><i class="ri-file-copy-line text-primary me-1"></i> Duplicate tour</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
      <p class="text-muted mb-3">Makes a copy of <b data-duplicate-source></b> to change and save as a new trip. The description, prices settings, policies, categories, pick-up points, inclusions and photos are copied.</p>
      <div class="mb-3"><label class="form-label fw-semibold" for="tour-duplicate-name">Name of the new tour</label><input class="form-control" id="tour-duplicate-name" name="name" maxlength="255" required></div>
      <div class="tf-label mb-1">Also copy</div>
      <div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="tour-duplicate-itinerary" name="itinerary" value="1" checked><label class="form-check-label" for="tour-duplicate-itinerary">Itinerary <span class="text-muted" data-duplicate-count="days"></span></label></div>
      <div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="tour-duplicate-content" name="content" value="1" checked><label class="form-check-label" for="tour-duplicate-content">Additional information (what to bring, documents, FAQ…) <span class="text-muted" data-duplicate-count="items"></span></label></div>
      <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="tour-duplicate-dates" name="dates" value="1"><label class="form-check-label" for="tour-duplicate-dates">Upcoming dates & their prices <span class="text-muted" data-duplicate-count="dates"></span><small class="d-block text-muted">With all seats free — bookings, staff and past dates are never copied.</small></label></div>
      <div class="alert alert-info mb-0 py-2"><i class="ri-information-line"></i> The copy is saved as <b>Draft</b>, so visitors don’t see it until you publish it. It gets its own page address and empty SEO texts.</div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
      <button class="btn btn-primary"><i class="ri-file-copy-line me-1"></i> Create copy</button>
    </div>
  </form>
</div></div></div>
<script>
document.addEventListener('click', function (e) {
  const btn = e.target.closest('[data-duplicate-url]'); if (!btn) return;
  const form = document.getElementById('tour-duplicate-form');
  form.action = btn.dataset.duplicateUrl;
  form.querySelector('[data-duplicate-source]').textContent = btn.dataset.duplicateName;
  const name = document.getElementById('tour-duplicate-name');
  name.value = btn.dataset.duplicateName + ' (copy)';
  const counts = { days: ['day', 'days'], items: ['item', 'items'], dates: ['date', 'dates'] };
  Object.entries(counts).forEach(([key, [one, many]]) => {
    const n = +(btn.dataset['duplicate' + key[0].toUpperCase() + key.slice(1)] || 0);
    form.querySelector('[data-duplicate-count="' + key + '"]').textContent = '(' + n + ' ' + (n === 1 ? one : many) + ')';
    const box = form.querySelector('#tour-duplicate-' + (key === 'items' ? 'content' : key === 'days' ? 'itinerary' : 'dates'));
    box.disabled = n === 0; if (n === 0) box.checked = false; else if (key !== 'dates') box.checked = true;
  });
  document.getElementById('tour-duplicate').addEventListener('shown.bs.modal', () => { name.focus(); name.select(); }, { once: true });
});
</script>
@endcan
