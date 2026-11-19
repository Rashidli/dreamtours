{{-- One shared "Details" modal per list page. Each row renders
       <template id="cms-d-{id}" data-title="…" data-edit="url|empty">…full details…</template>
     and a button: data-bs-toggle="modal" data-bs-target="#cms-details" data-details="#cms-d-{id}". --}}
<div class="modal fade" id="cms-details" tabindex="-1" aria-labelledby="cms-details-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="cms-details-title">Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body cms-details-body"></div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><a class="btn btn-primary" id="cms-details-edit" href="#"><i class="ri-pencil-line"></i> <span>Edit</span></a></div>
    </div>
  </div>
</div>
<script>
document.getElementById('cms-details').addEventListener('show.bs.modal', function (e) {
  const trigger = e.relatedTarget; if (!trigger) return;
  const tpl = document.querySelector(trigger.getAttribute('data-details')); if (!tpl) return;
  this.querySelector('.modal-title').textContent = tpl.dataset.title || 'Details';
  const body = this.querySelector('.cms-details-body'); body.innerHTML = ''; body.appendChild(tpl.content.cloneNode(true));
  const edit = document.getElementById('cms-details-edit');
  edit.hidden = !tpl.dataset.edit; edit.href = tpl.dataset.edit || '#';
  edit.querySelector('span').textContent = tpl.dataset.editLabel || 'Edit';
});
</script>
