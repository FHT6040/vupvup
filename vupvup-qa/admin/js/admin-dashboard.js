/* global vupvupAdminData */
(function () {
  'use strict';

  const d = vupvupAdminData;

  let currentEventId   = null;
  let currentStatus    = null;
  let pollInterval     = null;
  let highestKnownId   = 0;
  const renderedIds    = new Set();

  // ─── DOM refs ──────────────────────────────────────────────────────────────
  const eventSelect   = document.getElementById('vupvup-event-select');
  const activateBtn   = document.getElementById('vupvup-activate-btn');
  const closeBtn      = document.getElementById('vupvup-close-btn');
  const statusBadge   = document.getElementById('vupvup-status-badge');
  const filterBar     = document.getElementById('vupvup-filter-bar');
  const filterStatus  = document.getElementById('vupvup-filter-status');
  const filterOrder   = document.getElementById('vupvup-filter-order');
  const container     = document.getElementById('vupvup-questions-container');
  const list          = document.getElementById('vupvup-questions-list');
  const noEventMsg    = document.getElementById('vupvup-no-event-msg');
  const countEl       = document.getElementById('vupvup-question-count');
  const tpl           = document.getElementById('vupvup-question-tpl');
  const regenBtn      = document.getElementById('vupvup-regen-qr');

  // ─── Event selection ───────────────────────────────────────────────────────
  if (eventSelect) {
    eventSelect.addEventListener('change', () => {
      currentEventId = parseInt(eventSelect.value, 10) || null;
      const opt      = eventSelect.selectedOptions[0];
      currentStatus  = opt ? opt.dataset.status : null;
      stopPolling();
      clearList();
      updateUI();
      if (currentEventId) loadQuestions(true);
    });
  }

  function updateUI() {
    const hasEvent = !!currentEventId;
    filterBar.style.display    = hasEvent ? '' : 'none';
    container.style.display    = hasEvent ? '' : 'none';
    noEventMsg.style.display   = hasEvent ? 'none' : '';

    if (activateBtn) activateBtn.style.display = (hasEvent && currentStatus !== 'active') ? '' : 'none';
    if (closeBtn)    closeBtn.style.display    = (hasEvent && currentStatus === 'active')  ? '' : 'none';

    if (statusBadge) {
      const labels = { draft: 'Kladde', active: 'Aktiv', closed: 'Lukket' };
      const cls    = { draft: 'vupvup-status-draft', active: 'vupvup-status-active', closed: 'vupvup-status-closed' };
      statusBadge.textContent  = currentStatus ? (labels[currentStatus] || currentStatus) : '';
      statusBadge.className    = 'vupvup-badge ' + (cls[currentStatus] || '');
    }
  }

  // ─── Activate / Close event ────────────────────────────────────────────────
  if (activateBtn) {
    activateBtn.addEventListener('click', () => setEventStatus('active'));
  }
  if (closeBtn) {
    closeBtn.addEventListener('click', () => setEventStatus('closed'));
  }

  async function setEventStatus(newStatus) {
    if (!currentEventId) return;
    const nonce = activateBtn.dataset.nonce || closeBtn.dataset.nonce;

    const fd = new FormData();
    fd.append('action',   'vupvup_update_event_status');
    fd.append('nonce',    nonce);
    fd.append('event_id', currentEventId);
    fd.append('status',   newStatus);

    try {
      const res  = await fetch(d.ajaxUrl, { method: 'POST', body: fd });
      const json = await res.json();
      if (json.success) {
        currentStatus = newStatus;
        const opt = document.querySelector(`#vupvup-event-select option[value="${currentEventId}"]`);
        if (opt) opt.dataset.status = newStatus;
        updateUI();
        if (newStatus === 'active') startPolling();
        else stopPolling();
      }
    } catch (e) {
      alert(d.i18n.error);
    }
  }

  // ─── Filter changes ────────────────────────────────────────────────────────
  if (filterStatus) filterStatus.addEventListener('change', () => loadQuestions(true));
  if (filterOrder)  filterOrder.addEventListener('change',  () => loadQuestions(true));

  // ─── Load / poll questions ─────────────────────────────────────────────────
  async function loadQuestions(fullRefresh = false) {
    if (!currentEventId) return;

    const status  = filterStatus ? filterStatus.value : 'all';
    const orderby = filterOrder  ? filterOrder.value  : 'newest';
    const sinceId = fullRefresh  ? 0 : highestKnownId;

    const url = new URL(`${d.restUrl}/events/${currentEventId}/questions`);
    url.searchParams.set('status',   status);
    url.searchParams.set('orderby',  orderby);
    url.searchParams.set('since_id', sinceId);

    try {
      const res       = await fetch(url.toString(), {
        headers: { 'X-WP-Nonce': d.nonce },
      });
      const questions = await res.json();

      if (!Array.isArray(questions)) return;

      if (fullRefresh) {
        clearList();
        renderedIds.clear();
        highestKnownId = 0;
      }

      let newCount = 0;
      questions.forEach(q => {
        if (!renderedIds.has(q.id)) {
          renderedIds.add(q.id);
          renderQuestion(q, fullRefresh);
          if (q.id > highestKnownId) highestKnownId = q.id;
          newCount++;
        }
      });

      if (!fullRefresh && newCount > 0) {
        notifyNewQuestions(newCount);
      }

      if (countEl) countEl.textContent = `(${renderedIds.size})`;

    } catch (e) {
      console.error('[VupVup] Poll error:', e);
    }
  }

  function renderQuestion(q, prepend = false) {
    if (!tpl) return;
    const node = tpl.content.cloneNode(true);
    const card = node.querySelector('.vupvup-question-card');

    card.dataset.id            = q.id;
    card.dataset.status        = q.status;
    card.querySelector('.vupvup-q-author').textContent  = q.author;
    card.querySelector('.vupvup-q-time').textContent    = formatTime(q.created_at);
    card.querySelector('.vupvup-q-text').textContent    = q.question;
    card.querySelector('.upvote-count').textContent     = q.upvotes;

    const badge = card.querySelector('.vupvup-q-status-badge');
    badge.textContent  = statusLabel(q.status);
    badge.className    = 'vupvup-q-status-badge vupvup-status-' + q.status;

    // Action buttons
    card.querySelector('.vupvup-btn-approve').addEventListener('click', () => updateStatus(q.id, 'approved', card));
    card.querySelector('.vupvup-btn-reject').addEventListener('click',  () => updateStatus(q.id, 'rejected', card));
    card.querySelector('.vupvup-btn-asked').addEventListener('click',   () => updateStatus(q.id, 'asked',    card));

    const copyBtn = card.querySelector('.vupvup-btn-copy');
    copyBtn.addEventListener('click', () => {
      navigator.clipboard.writeText(q.question).then(() => {
        copyBtn.textContent = d.i18n.copied;
        setTimeout(() => (copyBtn.textContent = d.i18n.copy), 2000);
      });
    });

    if (list) {
      if (prepend) {
        list.insertBefore(node, list.firstChild);
      } else {
        list.appendChild(node);
      }
    }
  }

  async function updateStatus(questionId, newStatus, card) {
    try {
      const res  = await fetch(`${d.restUrl}/questions/${questionId}/status`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': d.nonce },
        body:    JSON.stringify({ status: newStatus }),
      });
      const json = await res.json();
      if (json.success) {
        card.dataset.status = newStatus;
        const badge = card.querySelector('.vupvup-q-status-badge');
        badge.textContent = statusLabel(newStatus);
        badge.className   = 'vupvup-q-status-badge vupvup-status-' + newStatus;
      }
    } catch (e) {
      alert(d.i18n.error);
    }
  }

  // ─── Polling ───────────────────────────────────────────────────────────────
  function startPolling() {
    if (pollInterval) return;
    pollInterval = setInterval(() => loadQuestions(false), 5000);
  }

  function stopPolling() {
    if (pollInterval) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
  }

  function clearList() {
    if (list) list.innerHTML = '';
  }

  // ─── QR Regenerate ─────────────────────────────────────────────────────────
  if (regenBtn) {
    regenBtn.addEventListener('click', async () => {
      regenBtn.disabled = true;
      const fd = new FormData();
      fd.append('action',   'vupvup_regenerate_qr');
      fd.append('nonce',    regenBtn.dataset.nonce);
      fd.append('event_id', regenBtn.dataset.eventId);

      try {
        const res  = await fetch(d.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
          const img = document.querySelector('#vupvup_event_qr img');
          if (img) img.src = json.data.qr_url + '?t=' + Date.now();
        } else {
          alert(d.i18n.error);
        }
      } finally {
        regenBtn.disabled = false;
      }
    });
  }

  // ─── Status toggle on events list ──────────────────────────────────────────
  document.querySelectorAll('.vupvup-toggle-status').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.preventDefault();
      const eventId = btn.dataset.eventId;
      const current = btn.dataset.currentStatus;
      const next    = current === 'active' ? 'closed' : 'active';

      const fd = new FormData();
      fd.append('action',   'vupvup_update_event_status');
      fd.append('nonce',    btn.dataset.nonce);
      fd.append('event_id', eventId);
      fd.append('status',   next);

      try {
        const res  = await fetch(d.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) location.reload();
      } catch {
        alert(d.i18n.error);
      }
    });
  });

  // ─── Helpers ───────────────────────────────────────────────────────────────
  function formatTime(dateStr) {
    if (!dateStr) return '';
    const dt = new Date(dateStr.replace(' ', 'T'));
    return dt.toLocaleTimeString('da-DK', { hour: '2-digit', minute: '2-digit' });
  }

  function statusLabel(status) {
    const map = {
      pending:  'Afventer',
      approved: 'Godkendt',
      rejected: 'Afvist',
      hidden:   'Skjult',
      asked:    'Stillet',
    };
    return map[status] || status;
  }

  function notifyNewQuestions(count) {
    if (document.title.startsWith('(')) {
      document.title = document.title.replace(/^\(\d+\)\s*/, '');
    }
    document.title = `(${count}) ` + document.title;
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(d.i18n.newQ, { body: `${count} nyt/nye spørgsmål`, icon: '' });
    }
  }

  // Auto-start polling if current event is active on page load.
  if (eventSelect && eventSelect.value) {
    const opt = eventSelect.selectedOptions[0];
    if (opt && opt.dataset.status === 'active') {
      currentEventId = parseInt(eventSelect.value, 10);
      currentStatus  = 'active';
      updateUI();
      loadQuestions(true);
      startPolling();
    }
  }

})();
