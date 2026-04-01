/* global vupvupBig */
(function () {
  'use strict';

  const d       = vupvupBig;
  const list    = document.getElementById('vv-big-list');
  const cntEl   = document.getElementById('vv-big-q-count');
  const banner  = document.getElementById('vv-big-slot-banner');
  const slots   = d.slots || [];

  if (!list || d.eventStatus !== 'active') return;

  const rendered  = new Map(); // id → card element
  let   bigMode   = 'all';    // 'all' | 'highlighted'
  let   activeSlot = -1;

  loadFeed();
  loadState();
  setInterval(loadFeed,  5000);
  setInterval(loadState, 4000);

  async function loadState() {
    try {
      const res  = await fetch(`${d.restUrl}/events/${d.eventId}/bigscreen-state`, { headers: { 'X-WP-Nonce': d.nonce } });
      const json = await res.json();
      if (json.mode && json.mode !== bigMode) {
        bigMode = json.mode;
        renderVisible();
      }
      if (typeof json.active_slot === 'number' && json.active_slot !== activeSlot) {
        activeSlot = json.active_slot;
        updateBanner();
      }
    } catch { /* silent */ }
  }

  function updateBanner() {
    if (!banner) return;
    const label = activeSlot >= 0 && slots[activeSlot] ? slots[activeSlot] : null;
    if (label) {
      banner.textContent = '🎤 ' + label;
      banner.classList.remove('vv-hidden');
    } else {
      banner.classList.add('vv-hidden');
    }
  }

  async function loadFeed() {
    try {
      const res = await fetch(
        `${d.restUrl}/events/${d.eventId}/feed`,
        { headers: { 'X-WP-Nonce': d.nonce } }
      );
      if (!res.ok) return;
      const questions = await res.json();
      if (!Array.isArray(questions)) return;

      if (cntEl) cntEl.textContent = questions.length;

      const empty = list.querySelector('.vv-big-empty');

      questions.forEach(q => {
        const existing = rendered.get(q.id);
        if (existing) {
          existing.querySelector('.vv-big-vote-num').textContent = q.upvotes || 0;
          existing.classList.toggle('vv-big-top', (q.upvotes || 0) >= 3);
          existing.classList.toggle('vv-big-highlighted', !!q.highlighted);
        } else {
          if (empty) empty.remove();
          const card = buildCard(q);
          rendered.set(q.id, card);
          list.prepend(card);
        }
      });

      renderVisible();

    } catch { /* silent */ }
  }

  function renderVisible() {
    rendered.forEach((card, id) => {
      if (bigMode === 'highlighted') {
        card.style.display = card.classList.contains('vv-big-highlighted') ? '' : 'none';
      } else {
        card.style.display = '';
      }
    });

    // Re-sort by upvotes
    const sorted = [...rendered.entries()].sort((a, b) => {
      const va = parseInt(a[1].querySelector('.vv-big-vote-num')?.textContent || 0, 10);
      const vb = parseInt(b[1].querySelector('.vv-big-vote-num')?.textContent || 0, 10);
      return vb - va;
    });
    sorted.forEach(([, el]) => list.appendChild(el));
  }

  function buildCard(q) {
    const isTop  = (q.upvotes || 0) >= 3;
    const isHl   = !!q.highlighted;
    const card   = document.createElement('div');
    card.className = 'vv-big-card' + (isTop ? ' vv-big-top' : '') + (isHl ? ' vv-big-highlighted' : '');
    card.dataset.id = q.id;

    card.innerHTML = `
      <div class="vv-big-vote">
        <svg class="vv-big-vote-icon" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
          <polyline points="18 15 12 9 6 15"/>
        </svg>
        <span class="vv-big-vote-num">${q.upvotes || 0}</span>
      </div>
      <div class="vv-big-body-wrap">
        <p class="vv-big-q-text">${escHtml(q.question)}</p>
        <div class="vv-big-q-meta">
          <span>
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            ${escHtml(q.author || 'Anonym')}
          </span>
          ${isTop ? '<span class="vv-big-top-badge">TOP</span>' : ''}
          ${isHl  ? '<span class="vv-big-top-badge" style="background:#F59E0B">★</span>' : ''}
        </div>
      </div>`;

    return card;
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

})();
