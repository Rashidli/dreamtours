{{-- Sidebar menu search: fuzzy match on menu labels (and their section), best match first.
     "/" focuses it, ↑/↓ choose, Enter opens, Esc clears. --}}
<div class="menu-search px-3 pt-3 pb-1">
    <div class="position-relative">
        <i class="ri-search-line position-absolute text-muted" style="left:10px;top:50%;transform:translateY(-50%)"></i>
        <input type="search" class="form-control form-control-sm" id="menu-search" placeholder="Search menu  ( / )" autocomplete="off" aria-label="Search menu" style="padding-left:30px">
    </div>
    <div class="small text-muted px-1 pt-2" id="menu-search-empty" hidden>No menu item matches.</div>
</div>
<style>
    .vertical-collpsed .menu-search { display: none; }
    #side-menu mark.menu-hit { padding: 0; background: none; color: #f80a6a; font-weight: 700; }
    #side-menu a.menu-pick { background: rgba(15, 156, 243, .08); }
    #side-menu li.menu-hide { display: none !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('menu-search');
    const menu = document.getElementById('side-menu');
    const empty = document.getElementById('menu-search-empty');
    if (!input || !menu) return;

    const plain = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    // Every clickable item (top level or inside a sub-menu), with its section and parent for context.
    let section = '';
    const items = [];
    for (const li of menu.querySelectorAll('li')) {
        if (li.classList.contains('menu-title')) { section = li.textContent.trim(); continue; }
        const link = li.querySelector(':scope > a');
        if (!link || link.classList.contains('has-arrow')) continue;
        const label = (link.querySelector('span:not(.badge)') || link); // skip count badges (Leads)
        const parentLi = li.parentElement.closest('li');
        const parent = parentLi ? parentLi.querySelector(':scope > a').textContent.trim() : '';
        items.push({ li, link, label, text: label.textContent.trim(), context: plain(`${parent} ${section}`), parentLi });
    }

    /**
     * Fuzzy score: every query letter must appear in order. Consecutive letters and
     * letters at word starts score higher; gaps cost a little. -1 = no match.
     */
    function score(query, text) {
        const t = plain(text);
        let qi = 0, last = -2, total = 0;
        const hits = [];
        for (let i = 0; i < t.length && qi < query.length; i++) {
            if (t[i] !== query[qi]) continue;
            total += 1 + (i === last + 1 ? 5 : 0) + (i === 0 || /[\s&/-]/.test(t[i - 1]) ? 4 : 0) - Math.min(3, (i - last - 1) * 0.1);
            hits.push(i); last = i; qi++;
        }
        if (qi < query.length) return null;
        // The whole query as one piece ("seo" in "Pages & SEO") beats letters scattered from the left.
        const at = t.search(new RegExp(`(^|[\\s&/-])${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`));
        if (at >= 0) {
            const start = at + (at > 0 || /[\s&/-]/.test(t[0]) ? 1 : 0);
            return { total: total + 30 - t.length * 0.01, hits: [...query].map((_, i) => start + i) };
        }
        return { total: total - t.length * 0.01, hits };
    }

    function highlight(item, hits) {
        if (!hits) { item.label.textContent = item.text; return; }
        const set = new Set(hits);
        item.label.innerHTML = '';
        [...item.text].forEach((ch, i) => {
            if (set.has(i)) { const m = document.createElement('mark'); m.className = 'menu-hit'; m.textContent = ch; item.label.append(m); }
            else item.label.append(ch);
        });
    }

    let results = [], pick = 0;

    function run() {
        const query = plain(input.value.trim()).replace(/\s+/g, ' ');
        menu.querySelectorAll('.menu-hide').forEach(el => el.classList.remove('menu-hide'));
        menu.querySelectorAll('a.menu-pick').forEach(el => el.classList.remove('menu-pick'));
        menu.querySelectorAll('ul.sub-menu.menu-opened').forEach(ul => { ul.classList.remove('menu-opened', 'mm-show'); ul.closest('li').classList.remove('mm-active'); });
        items.forEach(item => highlight(item, null));
        results = [];
        empty.hidden = true;
        if (!query) return;

        const compact = query.replace(/ /g, '');
        for (const item of items) {
            // The label itself first; the section / parent name ("access", "content") as a weaker fallback.
            const own = score(compact, item.text);
            const ctx = own ? null : score(compact, `${item.context} ${plain(item.text)}`);
            if (own || ctx) results.push({ item, rank: own ? own.total + 100 : ctx.total, hits: own?.hits });
        }
        results.sort((a, b) => b.rank - a.rank);

        const shown = new Set(results.map(r => r.item.li));
        for (const li of menu.querySelectorAll(':scope > li')) {
            const inside = [...shown].some(s => s === li || li.contains(s));
            if (!li.classList.contains('menu-title') && !inside) li.classList.add('menu-hide');
        }
        for (const { item } of results) {
            if (item.parentLi) {
                const sub = item.parentLi.querySelector(':scope > ul.sub-menu');
                // Only sub-menus the search opened are closed again when it is cleared; the current page's stays open.
                if (!sub.classList.contains('mm-show')) {
                    sub.classList.add('menu-opened', 'mm-show');
                    item.parentLi.classList.add('mm-active');
                }
                sub.querySelectorAll(':scope > li').forEach(li => li.classList.toggle('menu-hide', !shown.has(li)));
            }
            highlight(item, results.find(r => r.item === item).hits);
        }
        // Section titles only when something under them is still visible.
        let title = null, visible = false;
        for (const li of menu.querySelectorAll(':scope > li')) {
            if (li.classList.contains('menu-title')) { if (title && !visible) title.classList.add('menu-hide'); title = li; visible = false; }
            else if (!li.classList.contains('menu-hide')) visible = true;
        }
        if (title && !visible) title.classList.add('menu-hide');

        empty.hidden = results.length > 0;
        pick = 0;
        mark();
    }

    function mark() {
        menu.querySelectorAll('a.menu-pick').forEach(el => el.classList.remove('menu-pick'));
        if (results[pick]) { results[pick].item.link.classList.add('menu-pick'); results[pick].item.link.scrollIntoView({ block: 'nearest' }); }
    }

    input.addEventListener('input', run);
    input.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (results.length) { pick = (pick + (e.key === 'ArrowDown' ? 1 : results.length - 1)) % results.length; mark(); }
        } else if (e.key === 'Enter' && results[pick]) {
            e.preventDefault();
            window.location.href = results[pick].item.link.href;
        } else if (e.key === 'Escape') {
            input.value = ''; run(); input.blur();
        }
    });
    document.addEventListener('keydown', e => {
        if (e.key === '/' && !e.ctrlKey && !e.metaKey && !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName) && !document.activeElement.isContentEditable) {
            e.preventDefault();
            input.focus();
        }
    });
});
</script>
