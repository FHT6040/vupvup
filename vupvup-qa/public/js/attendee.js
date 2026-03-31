/* global vupvupData */
(function () {
  'use strict';

  const d = vupvupData;

  // ─── DOM refs ────────────────────────────────────────────────────────────────
  const qList       = document.getElementById('vv-questions-list');
  const qCount      = document.getElementById('vv-q-count');
  const voteCount   = document.getElementById('vv-vote-count');
  const textarea    = document.getElementById('vupvup-question-input');
  const submitBtn   = document.getElementById('vupvup-submit-btn');
  const charDisplay = document.getElementById('vupvup-chars');
  const feedback    = document.getElementById('vv-form-feedback');
  const speakerSel  = document.getElementById('vupvup-speaker-select');

  // ─── State ───────────────────────────────────────────────────────────────────
  let currentGuestName = d.guestName || '';
  let pollTimer        = null;
  const renderedIds    = new Set();
  const votedIds       = new Set();

  // ─── Guest name flow ─────────────────────────────────────────────────────────
  const guestSubmit = document.getElementById('vupvup-guest-submit');
  const guestInput  = document.getElementById('vupvup-guest-name-input');

  if (guestSubmit) {
    const confirm = async () => {
      const name = (guestInput.value || '').trim();
      if (name.length < 2) { guestInput.focus(); return; }
      guestSubmit.disabled = true;

      const fd = new FormData();
      fd.append('action',     'vupvup_guest_login');
      fd.append('nonce',      guestSubmit.dataset.nonce);
      fd.append('guest_name', name);

      try {
        const res  = await fetch(d.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
          currentGuestName = json.data.guest_name;
          d.nonce = json.data.rest_nonce;
          // Swap guest bar for question input bar (reload page section)
          const guestBar = document.getElementById('vv-guest-bar');
          if (guestBar) {
            guestBar.innerHTML =
              '<div class="vv-input-wrap">' + document.getElementById('vv-input-bar').innerHTML + '</div>';
            location.reload();
          }
        }
      } catch { /* silent */ } finally {
        guestSubmit.disabled = false;
      }
    };
    guestSubmit.addEventListener('click', confirm);
    if (guestInput) guestInput.addEventListener('keydown', e => { if (e.key === 'Enter') confirm(); });
  }

  // ─── Auto-grow textarea ──────────────────────────────────────────────────────
  if (textarea) {
    textarea.addEventListener('input', () => {
      if (charDisplay) charDisplay.textContent = textarea.value.length;
      textarea.style.height = 'auto';
      textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    });
  }

  // ─── Question submission ─────────────────────────────────────────────────────
  if (submitBtn && textarea) {
    submitBtn.addEventListener('click', async () => {
      const question = textarea.value.trim();
      if (!question) { textarea.focus(); return; }

      hideFeedback();
      submitBtn.disabled = true;

      const body = {
        question,
        guest_name: currentGuestName || undefined,
        speaker_id: speakerSel ? (parseInt(speakerSel.value, 10) || undefined) : undefined,
      };

      try {
        const res  = await fetch(`${d.restUrl}/events/${d.eventId}/questions`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': d.nonce },
          body:    JSON.stringify(body),
        });
        const json = await res.json();

        if (res.ok && json.success) {
          textarea.value = '';
          textarea.style.height = 'auto';
          if (charDisplay) charDisplay.textContent = '0';
          showFeedback('ok', '✓ ' + (json.message || d.i18n.success));
        } else {
          showFeedback('err', json.message || d.i18n.error);
          submitBtn.disabled = false;
        }
      } catch {
        showFeedback('err', d.i18n.error);
        submitBtn.disabled = false;
      }
    });
  }

  // ─── Live feed ───────────────────────────────────────────────────────────────
  if (qList && d.eventStatus === 'active') {
    loadFeed();
    pollTimer = setInterval(loadFeed, 5000);
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

      // Update stats
      const totalVotes = questions.reduce((s, q) => s + (q.upvotes || 0), 0);
      if (qCount)    qCount.textContent    = questions.length;
      if (voteCount) voteCount.textContent = totalVotes;

      // Sort by upvotes desc
      questions.sort((a, b) => (b.upvotes || 0) - (a.upvotes || 0));

      // Render new ones at top, update vote counts on existing
      questions.forEach(q => {
        const existing = qList.querySelector(`[data-id="${q.id}"]`);
        if (existing) {
          const numEl = existing.querySelector('.vv-vote-num');
          if (numEl) numEl.textContent = q.upvotes || 0;
        } else {
          renderedIds.add(q.id);
          const card = buildCard(q);
          const empty = qList.querySelector('.vv-empty-feed');
          if (empty) empty.remove();
          qList.prepend(card);
        }
      });
    } catch { /* silent */ }
  }

  function buildCard(q) {
    const wrap = document.createElement('div');
    wrap.className = 'vv-q-card vv-q-new';
    wrap.dataset.id = q.id;

    const isTop = (q.upvotes || 0) >= 3;
    const topBadge = isTop
      ? '<span class="vv-q-top-badge">TOP</span>'
      : '';

    wrap.innerHTML = `
      <button class="vv-q-vote" aria-label="Stem" data-id="${q.id}">
        <svg class="vv-vote-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"/></svg>
        <span class="vv-vote-num">${q.upvotes || 0}</span>
      </button>
      <div class="vv-q-body">
        <p class="vv-q-text">${escHtml(q.question)}</p>
        <div class="vv-q-meta">
          <span>
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            ${escHtml(q.author || 'Anonym')}
          </span>
          <span>
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            ${timeAgo(q.created_at)}
          </span>
          ${topBadge}
        </div>
      </div>`;

    // Upvote handler
    const voteBtn = wrap.querySelector('.vv-q-vote');
    voteBtn.addEventListener('click', () => upvote(q.id, voteBtn));

    return wrap;
  }

  async function upvote(qId, btn) {
    if (votedIds.has(qId)) return;
    votedIds.add(qId);
    btn.classList.add('vv-voted');

    try {
      const res  = await fetch(`${d.restUrl}/questions/${qId}/upvote`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': d.nonce },
      });
      const json = await res.json();
      if (json.success) {
        const numEl = btn.querySelector('.vv-vote-num');
        if (numEl) numEl.textContent = json.data?.upvotes ?? (parseInt(numEl.textContent, 10) + 1);
      } else {
        votedIds.delete(qId);
        btn.classList.remove('vv-voted');
      }
    } catch {
      votedIds.delete(qId);
      btn.classList.remove('vv-voted');
    }
  }

  // ─── Helpers ─────────────────────────────────────────────────────────────────
  function showFeedback(type, msg) {
    if (!feedback) return;
    feedback.textContent = msg;
    feedback.className = `vv-feedback vv-${type}`;
    feedback.classList.remove('vupvup-hidden');
    setTimeout(() => feedback.classList.add('vupvup-hidden'), 5000);
  }

  function hideFeedback() {
    if (feedback) feedback.classList.add('vupvup-hidden');
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function timeAgo(dateStr) {
    if (!dateStr) return '';
    const diff = Math.floor((Date.now() - new Date(dateStr.replace(' ', 'T'))) / 1000);
    if (diff < 60)  return 'lige nu';
    if (diff < 3600) return Math.floor(diff / 60) + 'm siden';
    return Math.floor(diff / 3600) + 't siden';
  }

})();
