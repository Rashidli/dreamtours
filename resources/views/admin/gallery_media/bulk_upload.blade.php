{{-- Bulk photo / video upload for one album. Files go up one at a time, in the order chosen,
     so each gets its own progress and error, and the album order follows the selection.
     Existing media, album order and cover image are left as they are. --}}
@can('create-gallery-media')
<div class="bulk-upload mb-4" data-url="{{ route('gallery-media.store', $album) }}" data-token="{{ csrf_token() }}">
    <label class="bulk-upload-drop d-block border border-2 border-dashed rounded-3 p-4 text-center mb-2" style="border-style:dashed!important;cursor:pointer">
        <input type="file" class="d-none" multiple accept="image/*,.heic,.heif,video/mp4,video/webm,video/quicktime">
        <i class="ri-upload-cloud-2-line fs-1 text-primary d-block"></i>
        <span class="fw-semibold">Drop photos here or click to choose</span>
        <span class="d-block text-muted small">Select as many as you like — JPG, PNG, WebP or iPhone HEIC photos and MP4 / WebM / MOV videos, up to 50 MB each.</span>
    </label>
    <div class="bulk-upload-bar d-none d-flex flex-wrap align-items-center gap-2 mb-2">
        <button type="button" class="btn btn-primary btn-sm" data-action="start"><i class="ri-upload-2-line align-middle me-1"></i> Upload <span data-count></span></button>
        <button type="button" class="btn btn-light btn-sm" data-action="clear">Clear finished</button>
        <span class="small text-muted ms-auto" data-summary></span>
    </div>
    <ul class="list-group bulk-upload-list"></ul>
</div>

<script>
(() => {
    const root = document.currentScript.previousElementSibling;
    const input = root.querySelector('input[type=file]');
    const drop = root.querySelector('.bulk-upload-drop');
    const list = root.querySelector('.bulk-upload-list');
    const bar = root.querySelector('.bulk-upload-bar');
    const MAX_BYTES = 50 * 1024 * 1024;
    const VIDEO_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];
    // Some browsers (Chrome on Windows) report HEIC photos with an empty type.
    const isHeic = file => /\.(heic|heif)$/i.test(file.name);
    const items = [];
    let running = false;
    let added = 0;

    const sizeLabel = bytes => bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.ceil(bytes / 1024) + ' KB';

    function addFiles(files) {
        for (const file of files) {
            const item = { file, status: 'queued', error: '' };
            if (!file.type.startsWith('image/') && !VIDEO_TYPES.includes(file.type) && !isHeic(file)) {
                item.status = 'failed'; item.error = 'Not a photo or a supported video.';
            } else if (file.size > MAX_BYTES) {
                item.status = 'failed'; item.error = 'Larger than 50 MB.';
            }
            item.el = document.createElement('li');
            item.el.className = 'list-group-item';
            item.el.innerHTML = `<div class="d-flex align-items-center gap-2">
                <span class="flex-grow-1 text-truncate"><span data-name></span> <span class="text-muted small" data-size></span></span>
                <span class="small" data-status></span>
                <button type="button" class="btn btn-sm btn-outline-primary d-none" data-retry>Retry</button>
                <button type="button" class="btn btn-sm btn-light" data-remove title="Remove from list"><i class="ri-close-line"></i></button>
            </div>
            <div class="progress mt-2" style="height:4px"><div class="progress-bar" style="width:0%"></div></div>`;
            item.el.querySelector('[data-name]').textContent = file.name;
            item.el.querySelector('[data-size]').textContent = sizeLabel(file.size);
            item.el.querySelector('[data-retry]').addEventListener('click', () => { item.status = 'queued'; item.error = ''; render(item); start(); });
            item.el.querySelector('[data-remove]').addEventListener('click', () => { if (item.status === 'uploading') return; items.splice(items.indexOf(item), 1); item.el.remove(); summary(); });
            items.push(item);
            list.append(item.el);
            render(item);
        }
        summary();
    }

    function render(item, percent) {
        const status = item.el.querySelector('[data-status]');
        const progress = item.el.querySelector('.progress-bar');
        const labels = { queued: ['Waiting', 'text-muted'], uploading: ['Uploading…', 'text-primary'], done: ['Uploaded', 'text-success'], failed: [item.error || 'Failed', 'text-danger'] };
        status.textContent = labels[item.status][0];
        status.className = 'small ' + labels[item.status][1];
        progress.style.width = (item.status === 'done' ? 100 : item.status === 'uploading' ? (percent ?? 0) : 0) + '%';
        progress.className = 'progress-bar' + (item.status === 'done' ? ' bg-success' : item.status === 'failed' ? ' bg-danger' : '');
        item.el.querySelector('[data-retry]').classList.toggle('d-none', item.status !== 'failed' || /photo|50 MB/.test(item.error));
        item.el.querySelector('[data-remove]').disabled = item.status === 'uploading';
    }

    function summary() {
        const count = s => items.filter(i => i.status === s).length;
        bar.classList.toggle('d-none', items.length === 0);
        root.querySelector('[data-count]').textContent = count('queued') ? `${count('queued')} file${count('queued') === 1 ? '' : 's'}` : '';
        root.querySelector('[data-action=start]').disabled = running || !count('queued');
        root.querySelector('[data-summary]').textContent = [count('done') && `${count('done')} uploaded`, count('failed') && `${count('failed')} failed`].filter(Boolean).join(' · ');
    }

    function upload(item) {
        return new Promise(resolve => {
            const data = new FormData();
            data.append('_token', root.dataset.token);
            data.append('type', VIDEO_TYPES.includes(item.file.type) ? 'video' : 'image');
            data.append('file', item.file);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', root.dataset.url);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.onprogress = e => e.lengthComputable && render(item, Math.round(e.loaded / e.total * 95));
            xhr.onload = () => {
                if (xhr.status === 201) { item.status = 'done'; added++; }
                else {
                    let message = { 413: 'File is too large for the server.', 419: 'Session expired — reload the page and retry.', 403: 'You are not allowed to add media.' }[xhr.status];
                    try { const body = JSON.parse(xhr.responseText); message = message || Object.values(body.errors || {})[0]?.[0] || body.message; } catch (e) {}
                    item.status = 'failed'; item.error = message || `Upload failed (${xhr.status}).`;
                }
                resolve();
            };
            xhr.onerror = () => { item.status = 'failed'; item.error = 'Network error — check the connection and retry.'; resolve(); };
            item.status = 'uploading';
            render(item, 0);
            xhr.send(data);
        }).then(() => render(item));
    }

    async function start() {
        if (running) return;
        running = true;
        summary();
        let item;
        while ((item = items.find(i => i.status === 'queued'))) {
            await upload(item);
            summary();
        }
        running = false;
        summary();
        if (added && !items.some(i => i.status === 'failed')) {
            root.querySelector('[data-summary]').textContent += ' — refreshing the media list…';
            setTimeout(() => location.reload(), 1200);
        } else if (added) {
            root.querySelector('[data-summary]').innerHTML += ' — <a href="">reload</a> to see the new media';
        }
    }

    input.addEventListener('change', () => { addFiles(input.files); input.value = ''; });
    root.querySelector('[data-action=start]').addEventListener('click', start);
    root.querySelector('[data-action=clear]').addEventListener('click', () => {
        for (const item of items.filter(i => i.status === 'done')) { items.splice(items.indexOf(item), 1); item.el.remove(); }
        summary();
    });
    ['dragenter', 'dragover'].forEach(type => drop.addEventListener(type, e => { e.preventDefault(); drop.classList.add('bg-primary', 'bg-opacity-10'); }));
    ['dragleave', 'drop'].forEach(type => drop.addEventListener(type, e => { e.preventDefault(); drop.classList.remove('bg-primary', 'bg-opacity-10'); }));
    drop.addEventListener('drop', e => addFiles(e.dataTransfer.files));
    window.addEventListener('beforeunload', e => { if (running) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
@endcan
