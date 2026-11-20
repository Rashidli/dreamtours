
</div>
<!-- END layout-wrapper -->
<script>
    // Keep the admin where it was: a form posted from this page comes back to the same
    // scroll position (unless the redirect names an anchor). This runs right after the
    // page content and before the slower library scripts below, so there is no jump.
    (function () {
        var KEY = 'dt-admin-scroll';
        // Capture phase: some admin scripts cancel the event and submit the form themselves.
        document.addEventListener('submit', function (e) {
            if ((e.target.getAttribute('method') || 'get').toLowerCase() === 'get') return;
            try { sessionStorage.setItem(KEY, JSON.stringify({ path: location.pathname, y: window.scrollY, at: Date.now() })); } catch (err) {}
        }, true);

        var saved = null;
        try { saved = JSON.parse(sessionStorage.getItem(KEY) || 'null'); sessionStorage.removeItem(KEY); } catch (err) {}
        if (!saved || saved.path !== location.pathname || Date.now() - saved.at > 120000 || location.hash) return;
        if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
        var place = function () { window.scrollTo(0, saved.y); };
        place();
        // Editors and images change the page height while loading; settle again afterwards.
        document.addEventListener('DOMContentLoaded', place);
        window.addEventListener('load', function () { place(); setTimeout(place, 150); });
    })();

</script>

{{-- Feedback: success toasts (bottom centre), form-problem banner fallback, confirm dialog. Script at the end. --}}
@php
  $dtSuccess = session('message') ?? session('success');
  $dtFlashShown = app()->bound('dt.flash.rendered');
@endphp
<div id="dt-toasts" class="dt-toasts" aria-live="polite" aria-atomic="false">
  @if($dtSuccess)
    <div class="dt-toast" role="status" data-dt-flash data-delay="5000">
      <i class="ri-checkbox-circle-fill dt-toast-icon" aria-hidden="true"></i>
      <span class="dt-toast-text">{{ $dtSuccess }}</span>
      <button type="button" class="dt-toast-close" data-dt-toast-close aria-label="Dismiss"><i class="ri-close-line" aria-hidden="true"></i></button>
    </div>
  @endif
  @unless($dtFlashShown)
    {{-- Pages without the flash include still show problems; these stay until dismissed. --}}
    @foreach(['error' => 'critical', 'warning' => 'warning'] as $dtKey => $dtTone)
      @if(session($dtKey))
        <div class="dt-toast dt-toast-{{ $dtTone }}" role="alert" data-delay="0">
          <i class="{{ $dtTone === 'critical' ? 'ri-error-warning-fill' : 'ri-alert-fill' }} dt-toast-icon" aria-hidden="true"></i>
          <span class="dt-toast-text">{{ session($dtKey) }}</span>
          <button type="button" class="dt-toast-close" data-dt-toast-close aria-label="Dismiss"><i class="ri-close-line" aria-hidden="true"></i></button>
        </div>
      @endif
    @endforeach
  @endunless
</div>
@if($errors->any() && !app()->bound('dt.errors.rendered'))
  {{-- The page has no validation_errors include: render the banner here; the script moves it to the top. --}}
  @include('admin.includes.validation_errors', ['dtFallback' => true])
@endif

{{-- Confirm dialog used for every onclick/onsubmit="return confirm(…)" and data-confirm="…" (see the script below). --}}
<div class="modal fade dt-confirm" id="dt-confirm" tabindex="-1" role="alertdialog" aria-modal="true" aria-labelledby="dt-confirm-title" aria-describedby="dt-confirm-body" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="dt-confirm-title">Are you sure?</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body" id="dt-confirm-body"></div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" data-dt-confirm-ok>Confirm</button></div>
    </div>
  </div>
</div>


<style>
    .select2-container--bootstrap-5 .select2-selection--multiple { min-height: calc(1.5em + .75rem + 2px); padding: .25rem .375rem; }
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__rendered { display: flex; flex-wrap: wrap; gap: .25rem; }
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice { margin: 0; }
    .select2-container { max-width: 100%; }
</style>

<!-- JAVASCRIPT -->
<script src="{{asset('assets/libs/jquery/jquery.min.js')}}"></script>
{{--<script src="{{asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>--}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
{{--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>--}}
{{--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>--}}
<script src="{{asset('assets/libs/metismenu/metisMenu.min.js')}}"></script>
<script src="{{asset('assets/libs/simplebar/simplebar.min.js')}}"></script>
<script src="{{asset('assets/libs/node-waves/waves.min.js')}}"></script>


<!-- jquery.vectormap map -->
<script src="{{asset('assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js')}}"></script>
<script src="{{asset('assets/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-us-merc-en.js')}}"></script>

<script src="{{asset('/')}}assets/libs/dropzone/min/dropzone.min.js"></script>


<!-- App js -->
<script src="{{asset('assets/js/app.js')}}"></script>
<script src="{{asset('assets/js/select2.js')}}"></script>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Rich text editor: every textarea with class="js-editor" -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('textarea.js-editor').forEach(function (el) {
            ClassicEditor.create(el, {
                toolbar: ['heading','|','bold','italic','link','bulletedList','numberedList','|','blockQuote','insertTable','|','undo','redo'],
            }).catch(function (e) { console.error(e); });
        });
    });
</script>
<script>

    $(document).ready(function (){
        $('#corporate_name').change(function(){
            var voen_number = $(this).find(':selected').attr('data-voen');
            $('#voen').val(voen_number);
        });

        $('.js-example-basic-single').select2();

        $('.js-select2-tags').select2({
            theme: 'bootstrap-5',
            placeholder: 'Tag sec...',
            allowClear: true,
            width: '100%'
        });

        // Multi-selects: show the picks as tags and line up with the other Bootstrap fields.
        $('.js-select2-multi').each(function () {
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $(this).data('placeholder') || 'Select…',
                closeOnSelect: false,
                allowClear: true
            });
        });

        $('.group-checkbox').on('click', function() {

            // $(this).prop('checked', !$(this).prop('checked'));
            var groupBoolean =  $(this).prop('checked');
            var groupValue = $(this).data('group');

            $('input[type="checkbox"][data-group="' + groupValue + '"]').each(function() {

                if(groupBoolean){
                    $(this).prop('checked', true);
                }else{
                    $(this).prop('checked', false);
                }

            });

        });

        $('.radio_input').click(function (){

           var delete_route = $(this).data('delete');
           var edit_route = $(this).data('edit');
           var deleteForm = $('.delete_form');
            $('.edit_form').attr('href', edit_route);
            deleteForm.attr('action', delete_route);
            deleteForm.find('[type="submit"]').prop('disabled', false);

        });

        $('tbody tr').on('click', function () {
            // Find the radio input within this row and trigger its click event
            $(this).find('.radio_input').trigger('click');
        });

        // Attach a click event handler to the radio inputs to prevent propagation
        $('.radio_input').on('click', function (event) {
            event.stopPropagation();
        });
    });

    $(document).ready(function() {
        $('#editor_az, #editor_en, #editor_ru, #editor1_az, #editor1_en, #editor1_ru').summernote({
            height: 100,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['height', ['height']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });

    });

</script>
<script>
/*
 * Admin feedback: toasts, form-problem banner, confirm dialogs. Plain JS (no jQuery), Bootstrap 5.0.2.
 *  - window.dtToast('Saved', {tone: 'critical'|'warning', delay: 5000}) shows a toast from any script.
 *  - Confirm: any element with onclick/onsubmit="return confirm('…')" or data-confirm="…" gets the admin dialog
 *    instead of the browser one. Optional data-confirm-title, data-confirm-button="Delete", data-confirm-tone="critical|primary".
 *    If this script or Bootstrap is missing, the native confirm() in the attribute still works.
 */
(function () {
    'use strict';
    var norm = function (t) { return String(t || '').replace(/\s+/g, ' ').trim(); };
    var getModal = function (el, cfg) { var M = window.bootstrap && window.bootstrap.Modal; if (!M) return null; return (M.getInstance && M.getInstance(el)) || new M(el, cfg || {}); };

    /* ---------- Toasts ---------- */
    var region = document.getElementById('dt-toasts');
    function hideToast(t) { if (!t || t.dtGone) return; t.dtGone = true; t.classList.add('is-out'); setTimeout(function () { t.remove(); }, 220); }
    function armToast(t) {
        var ms = parseInt(t.getAttribute('data-delay') || '5000', 10), timer = null;
        var start = function () { if (ms > 0) { clearTimeout(timer); timer = setTimeout(function () { hideToast(t); }, ms); } };
        var stop = function () { clearTimeout(timer); };
        t.addEventListener('mouseenter', stop); t.addEventListener('mouseleave', start);
        t.addEventListener('focusin', stop); t.addEventListener('focusout', start);
        var close = t.querySelector('[data-dt-toast-close]');
        if (close) close.addEventListener('click', function () { hideToast(t); });
        start();
    }
    window.dtToast = function (message, opts) {
        opts = opts || {};
        if (!region) return;
        var tone = opts.tone === 'critical' || opts.tone === 'warning' ? opts.tone : '';
        var t = document.createElement('div');
        t.className = 'dt-toast' + (tone ? ' dt-toast-' + tone : '');
        t.setAttribute('role', tone ? 'alert' : 'status');
        t.setAttribute('data-delay', String(opts.delay != null ? opts.delay : (tone === 'critical' ? 0 : 5000)));
        t.innerHTML = '<i class="dt-toast-icon ' + (tone === 'critical' ? 'ri-error-warning-fill' : tone === 'warning' ? 'ri-alert-fill' : 'ri-checkbox-circle-fill') + '" aria-hidden="true"></i><span class="dt-toast-text"></span><button type="button" class="dt-toast-close" data-dt-toast-close aria-label="Dismiss"><i class="ri-close-line" aria-hidden="true"></i></button>';
        t.querySelector('.dt-toast-text').textContent = message;
        region.appendChild(t);
        armToast(t);
        return t;
    };
    if (region) {
        region.querySelectorAll('.dt-toast').forEach(armToast);
        // Some pages print the same success message inline: the toast is enough.
        var flash = region.querySelector('[data-dt-flash] .dt-toast-text');
        if (flash) {
            var said = norm(flash.textContent);
            document.querySelectorAll('.main-content .alert-success').forEach(function (a) { if (norm(a.textContent) === said) a.remove(); });
        }
    }

    /* ---------- Form problems ---------- */
    var banner = document.querySelector('[data-dt-errors]');
    var isVisible = function (el) { return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length)); };
    function findField(key) {
        var scope = document.querySelector('.main-content') || document;
        var names = [key, key + '[]'];
        var parts = key.split('.');
        if (parts.length > 1) { var br = parts[0] + parts.slice(1).map(function (p) { return '[' + p + ']'; }).join(''); names.push(br, br + '[]'); }
        for (var i = 0; i < names.length; i++) {
            var sel = '[name="' + (window.CSS && CSS.escape ? CSS.escape(names[i]) : names[i]) + '"]';
            var el = scope.querySelector(sel + '.is-invalid') || scope.querySelector(sel + ':not([type="hidden"])');
            if (el) return el;
        }
        var byId = document.getElementById(key);
        return byId && /^(INPUT|SELECT|TEXTAREA)$/.test(byId.tagName) ? byId : null;
    }
    function focusField(f) {
        var s2 = f.classList.contains('select2-hidden-accessible') && window.jQuery ? window.jQuery(f).next('.select2').find('.select2-selection')[0] : null;
        var target = s2 || f;
        target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        try { target.focus({ preventScroll: true }); } catch (e) { target.focus(); }
    }
    function reveal(f) {
        var B = window.bootstrap;
        var pane = f.closest('.tab-pane');
        if (B && pane && !pane.classList.contains('active') && pane.id) {
            var trig = document.querySelector('[data-bs-target="#' + pane.id + '"], a[href="#' + pane.id + '"][data-bs-toggle]');
            if (trig) new B.Tab(trig).show();
        }
        var col = f.closest('.collapse:not(.show)');
        if (B && col) new B.Collapse(col, { toggle: false }).show();
        var modal = f.closest('.modal');
        if (B && modal && !modal.classList.contains('show')) {
            modal.addEventListener('shown.bs.modal', function () { focusField(f); }, { once: true });
            getModal(modal).show();
            return;
        }
        setTimeout(function () { focusField(f); }, col ? 380 : 30);
    }
    if (banner) {
        if (banner.hasAttribute('data-dt-fallback')) {
            var host = document.querySelector('.page-content > .container-fluid') || document.querySelector('.page-content');
            if (host) {
                var titleRow = host.querySelector(':scope > .row .page-title-box');
                titleRow = titleRow && titleRow.closest('.row');
                host.insertBefore(banner, titleRow && titleRow.parentNode === host ? titleRow.nextSibling : host.firstChild);
            }
            var said2 = Array.prototype.map.call(banner.querySelectorAll('[data-dt-field]'), function (a) { return norm(a.textContent); });
            document.querySelectorAll('.main-content .alert-danger').forEach(function (a) { if (said2.indexOf(norm(a.textContent)) !== -1) a.remove(); });
        }
        banner.querySelectorAll('[data-dt-field]').forEach(function (a) {
            var f = findField(a.getAttribute('data-dt-field'));
            if (!f) { var span = document.createElement('span'); span.textContent = a.textContent; a.replaceWith(span); return; }
            a.addEventListener('click', function (e) { e.preventDefault(); reveal(f); });
        });
        // After the page (and the scroll restore above) settles: go to the first problem.
        window.addEventListener('load', function () {
            setTimeout(function () {
                if (document.querySelector('.modal.show')) return; // a form that reopened in a modal handles itself
                var first = document.querySelector('.main-content .is-invalid');
                if (first && isVisible(first) && !first.closest('.modal')) { focusField(first); return; }
                var top = banner.getBoundingClientRect().top + window.scrollY - 90;
                window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
                try { banner.focus({ preventScroll: true }); } catch (e) {}
            }, 350);
        });
    }

    /* ---------- Confirm dialog ---------- */
    var dlg = document.getElementById('dt-confirm');
    if (!dlg) return;
    var okBtn = dlg.querySelector('[data-dt-confirm-ok]');
    var titleEl = dlg.querySelector('#dt-confirm-title');
    var bodyEl = dlg.querySelector('#dt-confirm-body');
    var bypass = false, pending = null;

    // The message an inline handler would pass to confirm(): run it once with a stand-in confirm that answers "no".
    function askedBy(el, attr, ev) {
        var src = el.getAttribute(attr) || '';
        if (src.indexOf('confirm(') === -1 || typeof el[attr] !== 'function') return null;
        var asked = null, nativeConfirm = window.confirm;
        window.confirm = function (m) { if (asked === null) asked = m == null ? '' : String(m); return false; };
        try { el[attr].call(el, ev); } catch (err) { asked = null; } finally { window.confirm = nativeConfirm; }
        return asked;
    }
    function runConfirmed(fn) {
        var nativeConfirm = window.confirm;
        bypass = true; window.confirm = function () { return true; };
        try { fn(); } finally { window.confirm = nativeConfirm; bypass = false; }
    }
    function resubmit(form, submitter) {
        if (form.requestSubmit) {
            try { if (submitter && submitter.form === form) form.requestSubmit(submitter); else form.requestSubmit(); return; } catch (e) {}
        }
        if (submitter && submitter.name) {
            var h = document.createElement('input'); h.type = 'hidden'; h.name = submitter.name; h.value = submitter.value; form.appendChild(h);
        }
        if (!form.onsubmit || form.onsubmit() !== false) HTMLFormElement.prototype.submit.call(form);
    }
    var CRITICAL = /^(delete|remove|archive|discard|reject|revoke|deactivate|unpublish|clear|reset|cancel|disable)\b|\bspam\b/i;
    var VERBS = { delete: 'Delete', remove: 'Remove', archive: 'Archive', discard: 'Discard', reject: 'Reject', revoke: 'Revoke', deactivate: 'Deactivate', unpublish: 'Unpublish', publish: 'Publish', confirm: 'Confirm', send: 'Send', email: 'Send email', restore: 'Restore', merge: 'Merge', clear: 'Clear', reset: 'Reset', disable: 'Disable', enable: 'Enable' };
    function attr(els, name) { for (var i = 0; i < els.length; i++) { if (els[i] && els[i].hasAttribute && els[i].hasAttribute(name)) return els[i].getAttribute(name); } return null; }
    function ask(sources, message, onOk) {
        var m = norm(message) || 'Are you sure?';
        var title = attr(sources, 'data-confirm-title'), body = '';
        if (!title) {
            var q = m.indexOf('?');
            if (q > 0 && q < 140) { title = m.slice(0, q + 1); body = m.slice(q + 1).trim(); } else { title = 'Are you sure?'; body = m; }
        } else body = m;
        var verb = (m.match(/^[A-Za-z]+/) || [''])[0].toLowerCase();
        var tone = attr(sources, 'data-confirm-tone') || (CRITICAL.test(m) ? 'critical' : 'primary');
        if (/\bspam\b/i.test(m) && verb === 'mark') VERBS.mark = 'Mark as spam'; else delete VERBS.mark;
        titleEl.textContent = title;
        bodyEl.textContent = body;
        bodyEl.hidden = !body;
        okBtn.textContent = attr(sources, 'data-confirm-button') || VERBS[verb] || 'Confirm';
        okBtn.className = 'btn ' + (tone === 'critical' ? 'btn-danger' : 'btn-primary');
        pending = { ok: onOk, back: sources[0], confirmed: false };
        var modal = getModal(dlg, { focus: false });
        if (!modal) { pending = null; if (window.confirm(message)) runConfirmed(onOk); return; }
        modal.show();
    }
    okBtn.addEventListener('click', function () { if (!pending) return; pending.confirmed = true; getModal(dlg).hide(); });
    dlg.addEventListener('shown.bs.modal', function () { okBtn.focus(); });
    dlg.addEventListener('hidden.bs.modal', function () {
        var p = pending; pending = null;
        if (document.querySelector('.modal.show')) document.body.classList.add('modal-open'); // a dialog opened from another modal
        if (!p) return;
        if (p.confirmed) runConfirmed(p.ok); // exactly once
        else if (p.back && document.contains(p.back) && p.back.focus) { try { p.back.focus({ preventScroll: true }); } catch (e) {} }
    });
    // Keep focus inside the dialog (also when it opens over another modal, whose focus trap must not see it).
    dlg.addEventListener('focusin', function (e) { e.stopPropagation(); });
    dlg.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !(e.target instanceof HTMLButtonElement)) { e.preventDefault(); okBtn.click(); }
        if (e.key !== 'Tab') return;
        var items = Array.prototype.filter.call(dlg.querySelectorAll('button:not([disabled]), [href], input, select, textarea'), isVisible);
        if (!items.length) return;
        var i = items.indexOf(document.activeElement);
        var next = e.shiftKey ? (i <= 0 ? items.length - 1 : i - 1) : (i === items.length - 1 ? 0 : i + 1);
        e.preventDefault(); items[next].focus();
    });

    // Buttons and links: capture the click before the inline handler (and its native confirm) runs.
    document.addEventListener('click', function (e) {
        if (bypass || !window.bootstrap || e.button !== 0 || !e.target.closest) return;
        var el = e.target.closest('[data-confirm], [onclick*="confirm("]');
        if (!el || el.tagName === 'FORM' || el.disabled || dlg.contains(el)) return;
        var msg = el.hasAttribute('data-confirm') ? (el.getAttribute('data-confirm') || 'Are you sure?') : askedBy(el, 'onclick', e);
        if (msg === null) return;
        e.preventDefault(); e.stopImmediatePropagation();
        ask([el, el.form || el.closest('form')], msg, function () { el.click(); });
    }, true);
    // Forms: onsubmit="return confirm(…)" or <form data-confirm>; continue with the same submit button.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (bypass || !window.bootstrap || !form.matches || !form.matches('form[data-confirm], form[onsubmit*="confirm("]')) return;
        var msg = form.hasAttribute('data-confirm') ? (form.getAttribute('data-confirm') || 'Are you sure?') : askedBy(form, 'onsubmit', e);
        if (msg === null) return;
        e.preventDefault(); e.stopImmediatePropagation();
        var submitter = e.submitter || null;
        ask([submitter, form], msg, function () { resubmit(form, submitter); });
    }, true);
})();
</script>
</body>

</html>
