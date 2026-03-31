/* global vupvupBig */
(function () {
  'use strict';

  const d    = vupvupBig;
  const list = document.getElementById('vv-big-list');
  const cntEl = document.getElementById('vv-big-q-count');

  if (!list || d.eventStatus !== 'active') return;

  const rendered = new Map(); // id → card element

  loadFeed();
  setInterval(loadFeed, 5000);

  async function loadFeed() {
    try {
      const res = await fetch(
        `${d.restUrl}/events/${d.eventId}/feed`,
        { headers: { 'X-WP-Nonce': d.nonce } }
      );
      if (!res.ok) return;
      const questions = await res.json();
      if (!Array.isArray(questions)) return;

      // Sort by upvotes desc
      questions.sort((a, b) => (b.upvotes || 0) - (a.upvotes || 0));

      if (cntEl) cntEl.textContent = questions.length;

      // Remove empty state
      const empty = list.querySelector('.vv-big-empty');

      questions.forEach(q => {
        const existing = rendered.get(q.id);
        if (existing) {
          // Update vote count
          const numEl = existing.querySelector('.vv-big-vote-num');
          if (numEl) numEl.textContent = q.upvotes || 0;
          // Update top status
          if ((q.upvotes || 0) >= 3) existing.classList.add('vv-big-top');
          else existing.classList.remove('vv-big-top');
        } else {
          if (empty) empty.remove();
          const card = buildCard(q);
          rendered.set(q.id, card);
          list.prepend(card);
        }
      });

      // Re-sort DOM by upvotes
      const cards = [...rendered.entries()]
        .sort((a, b) => {
          const qa = questions.find(q => q.id === a[0]);
          const qb = questions.find(q => q.id === b[0]);
          return (qb?.upvotes || 0) - (qa?.upvotes || 0);
        });
      cards.forEach(([, el]) => list.appendChild(el));

    } catch { /* silent */ }
  }

  function buildCard(q) {
    const isTop = (q.upvotes || 0) >= 3;
    const card = document.createElement('div');
    card.className = 'vv-big-card' + (isTop ? ' vv-big-top' : '');
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
