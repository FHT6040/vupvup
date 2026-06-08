/* global vupvupDash */
(function () {
  'use strict';
  const d = window.vupvupDash || {};

  // ─── Register form ───────────────────────────────────────────────────────────
  const regForm   = document.getElementById('vupvup-register-form');
  const regError  = document.getElementById('vupvup-reg-error');
  const regSubmit = document.getElementById('vupvup-reg-submit');

  if (regForm) {
    regForm.addEventListener('submit', async e => {
      e.preventDefault();
      hideEl(regError);
      regSubmit.disabled = true;
      regSubmit.textContent = 'Opretter konto…';

      const body = {
        first_name: regForm.first_name.value.trim(),
        last_name:  regForm.last_name.value.trim(),
        company:    regForm.company.value.trim(),
        email:      regForm.email.value.trim(),
        password:   regForm.password.value,
      };

      try {
        const res  = await post(`${d.restUrl}/register`, body);
        const json = await res.json();
        if (res.ok && json.success) {
          window.location.href = json.redirect_url;
        } else {
          showError(regError, json.message || 'Noget gik galt.');
          regSubmit.disabled = false;
          regSubmit.textContent = 'Opret konto gratis';
        }
      } catch {
        showError(regError, 'Netværksfejl. Prøv igen.');
        regSubmit.disabled = false;
        regSubmit.textContent = 'Opret konto gratis';
      }
    });
  }

  // ─── Register organizer form ─────────────────────────────────────────────────
  const regOrgForm   = document.getElementById('vupvup-register-organizer-form');
  const regOrgError  = document.getElementById('vupvup-reg-organizer-error');
  const regOrgSubmit = document.getElementById('vupvup-reg-organizer-submit');

  if (regOrgForm) {
    regOrgForm.addEventListener('submit', async e => {
      e.preventDefault();
      hideEl(regOrgError);
      regOrgSubmit.disabled = true;
      regOrgSubmit.textContent = 'Opretter konto…';

      const body = {
        first_name: regOrgForm.first_name.value.trim(),
        last_name:  regOrgForm.last_name.value.trim(),
        company:    regOrgForm.company.value.trim(),
        email:      regOrgForm.email.value.trim(),
        password:   regOrgForm.password.value,
      };

      try {
        const res  = await post(`${d.restUrl}/register/organizer`, body);
        const json = await res.json();
        if (res.ok && json.success) {
          window.location.href = json.redirect_url;
        } else {
          showError(regOrgError, json.message || 'Noget gik galt.');
          regOrgSubmit.disabled = false;
          regOrgSubmit.textContent = 'Opret arrangørkonto';
        }
      } catch {
        showError(regOrgError, 'Netværksfejl. Prøv igen.');
        regOrgSubmit.disabled = false;
        regOrgSubmit.textContent = 'Opret arrangørkonto';
      }
    });
  }

  // ─── Login form ──────────────────────────────────────────────────────────────
  const loginForm   = document.getElementById('vupvup-login-form');
  const loginError  = document.getElementById('vupvup-login-error');
  const loginSubmit = document.getElementById('vupvup-login-submit');

  if (loginForm) {
    loginForm.addEventListener('submit', async e => {
      e.preventDefault();
      hideEl(loginError);
      loginSubmit.disabled = true;
      loginSubmit.textContent = 'Logger ind…';

      const body = {
        email:    loginForm.email.value.trim(),
        password: loginForm.password.value,
      };

      try {
        const res  = await post(`${d.restUrl}/login`, body);
        const json = await res.json();
        if (res.ok && json.success) {
          window.location.href = json.redirect_url;
        } else {
          showError(loginError, json.message || 'Forkert e-mail eller adgangskode.');
          loginSubmit.disabled = false;
          loginSubmit.textContent = 'Log ind';
        }
      } catch {
        showError(loginError, 'Netværksfejl. Prøv igen.');
        loginSubmit.disabled = false;
        loginSubmit.textContent = 'Log ind';
      }
    });
  }

  // ─── New event form ───────────────────────────────────────────────────────────
  const newForm    = document.getElementById('vupvup-new-event-form');
  const newError   = document.getElementById('vupvup-new-error');
  const newSuccess = document.getElementById('vupvup-new-success');
  const newSubmit  = document.getElementById('vupvup-new-submit');

  if (newForm) {
    newForm.addEventListener('submit', async e => {
      e.preventDefault();
      hideEl(newError); hideEl(newSuccess);
      newSubmit.disabled = true;
      newSubmit.textContent = 'Opretter…';

      const body = {
        title:         newForm.title.value.trim(),
        start_time:    newForm.start_time.value,
        end_time:      newForm.end_time.value,
        location:      newForm.location.value.trim(),
        speakers:      newForm.speakers.value.trim(),
        guest_allowed: newForm.guest_allowed.checked ? 1 : 0,
        activate_now:  newForm.activate_now.checked  ? 1 : 0,
        nonce:         newForm.vupvup_event_nonce.value,
      };

      if (!body.title) {
        showError(newError, 'Eventtitel er påkrævet.');
        newSubmit.disabled = false;
        newSubmit.textContent = 'Opret event';
        return;
      }

      try {
        const res  = await fetch(`${d.ajaxUrl}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'vupvup_create_event', ...body }),
        });
        const json = await res.json();
        if (json.success) {
          window.location.href = json.data.redirect_url;
        } else {
          showError(newError, json.data || 'Noget gik galt.');
          newSubmit.disabled = false;
          newSubmit.textContent = 'Opret event';
        }
      } catch {
        showError(newError, 'Netværksfejl. Prøv igen.');
        newSubmit.disabled = false;
        newSubmit.textContent = 'Opret event';
      }
    });
  }

  // ─── Edit event form ─────────────────────────────────────────────────────────
  const editForm    = document.getElementById('vupvup-edit-event-form');
  const editError   = document.getElementById('vupvup-edit-error');
  const editSuccess = document.getElementById('vupvup-edit-success');
  const editSubmit  = document.getElementById('vupvup-edit-submit');

  if (editForm) {
    editForm.addEventListener('submit', async e => {
      e.preventDefault();
      hideEl(editError); hideEl(editSuccess);
      editSubmit.disabled = true;
      editSubmit.textContent = 'Gemmer…';

      const body = {
        event_id:      editForm.event_id.value,
        title:         editForm.title.value.trim(),
        start_time:    editForm.start_time.value,
        end_time:      editForm.end_time.value,
        location:      editForm.location.value.trim(),
        speakers:      editForm.speakers.value.trim(),
        guest_allowed: editForm.guest_allowed.checked ? 1 : 0,
        nonce:         editForm.vupvup_edit_nonce.value,
      };

      if (!body.title) {
        showError(editError, 'Eventtitel er påkrævet.');
        editSubmit.disabled = false;
        editSubmit.textContent = 'Gem ændringer';
        return;
      }

      try {
        const res  = await fetch(d.ajaxUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'vupvup_update_event', ...body }),
        });
        const json = await res.json();
        if (json.success) {
          window.location.href = json.data.redirect_url;
        } else {
          showError(editError, json.data || 'Noget gik galt.');
          editSubmit.disabled = false;
          editSubmit.textContent = 'Gem ændringer';
        }
      } catch {
        showError(editError, 'Netværksfejl. Prøv igen.');
        editSubmit.disabled = false;
        editSubmit.textContent = 'Gem ændringer';
      }
    });
  }

  // ─── Slot builder (new + edit forms) ─────────────────────────────────────────
  const slotList   = document.getElementById('vv-slot-list');
  const addSlotBtn = document.getElementById('vv-add-slot');
  const speakersIn = document.getElementById('ev-speakers');

  if (slotList && addSlotBtn && speakersIn) {
    // Parse existing value (format: "Name|HH:MM-HH:MM") into rows.
    const existing = speakersIn.value ? speakersIn.value.split('\n').filter(Boolean) : [];
    existing.forEach(line => addSlotRow(line));

    addSlotBtn.addEventListener('click', () => addSlotRow(''));

    function addSlotRow(line) {
      const parts = line.split('|');
      const name  = parts[0]?.trim() || '';
      const times = (parts[1] || '').split('-');
      const start = times[0]?.trim() || '';
      const end   = times[1]?.trim() || '';

      const row = document.createElement('div');
      row.className = 'vv-slot-row';
      row.innerHTML = `
        <input type="text"  placeholder="Navn på taler" value="${escAttr(name)}" class="vv-slot-name">
        <input type="time"  placeholder="Start" value="${escAttr(start)}" class="vv-slot-start">
        <input type="time"  placeholder="Slut"  value="${escAttr(end)}"   class="vv-slot-end">
        <button type="button" class="vv-slot-remove" title="Fjern">✕</button>`;
      row.querySelector('.vv-slot-remove').addEventListener('click', () => {
        row.remove(); serializeSlots();
      });
      row.querySelectorAll('input').forEach(i => i.addEventListener('input', serializeSlots));
      slotList.appendChild(row);
      serializeSlots();
    }

    function serializeSlots() {
      const lines = [...slotList.querySelectorAll('.vv-slot-row')].map(row => {
        const name  = row.querySelector('.vv-slot-name').value.trim();
        const start = row.querySelector('.vv-slot-start').value;
        const end   = row.querySelector('.vv-slot-end').value;
        if (!name) return null;
        return start || end ? `${name}|${start}-${end}` : name;
      }).filter(Boolean);
      speakersIn.value = lines.join('\n');
    }

    function escAttr(s) {
      return String(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
  }

  // ─── Default times for datetime fields ───────────────────────────────────────
  const evStart = document.getElementById('ev-start');
  const evEnd   = document.getElementById('ev-end');
  if (evStart) evStart.addEventListener('change', function () {
    if (this.value && this.value.endsWith('T00:00')) this.value = this.value.replace('T00:00', 'T09:00');
  });
  if (evEnd) evEnd.addEventListener('change', function () {
    if (this.value && this.value.endsWith('T00:00')) this.value = this.value.replace('T00:00', 'T19:00');
  });

  // ─── Copy link buttons ────────────────────────────────────────────────────────
  document.querySelectorAll('.vupvup-copy-link').forEach(btn => {
    btn.addEventListener('click', () => {
      navigator.clipboard.writeText(btn.dataset.url).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Kopieret!';
        setTimeout(() => (btn.textContent = orig), 2000);
      });
    });
  });

  // ─── Live event dashboard ─────────────────────────────────────────────────────
  const qList = document.getElementById('vupvup-questions-list');
  const qTpl  = document.getElementById('vupvup-q-tpl');

  if ( qList && d.eventId ) {
    let currentFilter  = 'pending';
    let currentSort    = 'newest';
    let currentSpeaker = 0;
    let highestId      = 0;
    let pollTimer      = null;
    const rendered     = new Set();

    // Build speaker name lookup: { 1: "Jens Nielsen", 2: "Peter Magnussen", ... }
    const speakerNames = {};
    if (d.speakers && d.speakers.length) {
      d.speakers.forEach((s, i) => { speakerNames[i + 1] = s.split('|')[0].trim(); });
    }

    // Populate speaker filter dropdown
    const speakerFilter = document.getElementById('vupvup-speaker-filter');
    if (speakerFilter && d.speakers && d.speakers.length > 0) {
      const allOpt = document.createElement('option');
      allOpt.value = '0';
      allOpt.textContent = 'Alle talere';
      speakerFilter.appendChild(allOpt);
      d.speakers.forEach((s, i) => {
        const opt = document.createElement('option');
        opt.value = String(i + 1);
        opt.textContent = s.split('|')[0].trim();
        speakerFilter.appendChild(opt);
      });
      speakerFilter.style.display = '';
      speakerFilter.addEventListener('change', () => {
        currentSpeaker = parseInt(speakerFilter.value, 10) || 0;
        loadQuestions(true);
      });
    }

    // Filter buttons
    document.querySelectorAll('.vupvup-filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.vupvup-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.dataset.status;
        loadQuestions(true);
      });
    });

    // Sort
    const sortSel = document.getElementById('vupvup-sort');
    if (sortSel) sortSel.addEventListener('change', () => {
      currentSort = sortSel.value;
      loadQuestions(true);
    });

    // Activate / Close event
    const activateBtn = document.getElementById('vupvup-activate-btn');
    const closeBtn    = document.getElementById('vupvup-close-btn');
    if (activateBtn) activateBtn.addEventListener('click', () => setStatus('active'));
    if (closeBtn)    closeBtn.addEventListener('click',    () => setStatus('closed'));

    async function setStatus(newStatus) {
      const btn   = newStatus === 'active' ? activateBtn : closeBtn;
      const nonce = btn.dataset.nonce;
      const fd    = new FormData();
      fd.append('action',   'vupvup_update_event_status');
      fd.append('nonce',    nonce);
      fd.append('event_id', d.eventId);
      fd.append('status',   newStatus);
      try {
        const res  = await fetch(d.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) location.reload();
      } catch { alert('Fejl. Prøv igen.'); }
    }

    async function loadQuestions(full = false) {
      const base = d.sceneId
        ? `${d.restUrl}/scenes/${d.sceneId}/questions`
        : `${d.restUrl}/events/${d.eventId}/questions`;
      const url = new URL(base);
      url.searchParams.set('status',   currentFilter);
      url.searchParams.set('orderby',  currentSort);
      url.searchParams.set('since_id', full ? 0 : highestId);

      try {
        const res = await fetch(url, { headers: { 'X-WP-Nonce': d.nonce } });
        const qs  = await res.json();
        if (!Array.isArray(qs)) return;

        if (full) { qList.innerHTML = ''; rendered.clear(); highestId = 0; }

        let added = 0;
        const visible = currentSpeaker > 0 ? qs.filter(q => q.speaker_id === currentSpeaker) : qs;
        visible.forEach(q => {
          if (!rendered.has(q.id)) {
            rendered.add(q.id);
            renderQ(q, full);
            if (q.id > highestId) highestId = q.id;
            added++;
          }
        });

        if (qList.children.length === 0) {
          qList.innerHTML = '<div class="vupvup-questions-empty">Ingen spørgsmål i denne kategori endnu.</div>';
        }

        updateStats();
        if (!full && added > 0) flashTitle(added);

      } catch (err) { console.error('[VupVup]', err); }
    }

    function renderQ(q, prepend = false) {
      if (!qTpl) return;
      const node = qTpl.content.cloneNode(true);
      const card = node.querySelector('.vupvup-q-card');
      card.dataset.id     = q.id;
      card.dataset.status = q.status;
      card.querySelector('.vupvup-q-author').textContent = q.author;
      card.querySelector('.vupvup-q-time').textContent   = fmtTime(q.created_at);
      const speakerEl = card.querySelector('.vupvup-q-speaker');
      if (speakerEl) {
        const sName = q.speaker_id ? (speakerNames[q.speaker_id] || '') : '';
        speakerEl.textContent    = sName;
        speakerEl.style.display  = sName ? '' : 'none';
      }
      card.querySelector('.up-num').textContent          = q.upvotes;
      card.querySelector('.vupvup-q-text').textContent   = q.question;

      const badge = card.querySelector('.vupvup-q-badge');
      badge.textContent = statusLabel(q.status);
      badge.className   = `vupvup-badge vupvup-status-${q.status} vupvup-q-badge`;

      card.querySelector('.btn-approve').addEventListener('click',    () => updateStatus(q.id, 'approved', card));
      card.querySelector('.btn-reject').addEventListener('click',    () => updateStatus(q.id, 'rejected', card));
      card.querySelector('.btn-asked').addEventListener('click',    () => updateStatus(q.id, 'asked',    card));
      const hlBtn = card.querySelector('.btn-highlight');
      if (hlBtn) hlBtn.addEventListener('click', () => {
        const isNowHighlighted = !card.classList.contains('is-highlighted');
        toggleHighlight(q.id, isNowHighlighted, card);
      });
      card.querySelector('.btn-copy').addEventListener('click',    () => {
        const btn = card.querySelector('.btn-copy');
        navigator.clipboard.writeText(q.question).then(() => {
          btn.textContent = 'Kopieret!';
          setTimeout(() => (btn.textContent = 'Kopiér'), 2000);
        });
      });

      // Remove empty-state placeholder if present
      const empty = qList.querySelector('.vupvup-questions-empty');
      if (empty) empty.remove();

      prepend ? qList.insertBefore(node, qList.firstChild) : qList.appendChild(node);
    }

    async function updateStatus(qId, newStatus, card) {
      try {
        const res  = await fetch(`${d.restUrl}/questions/${qId}/status`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': d.nonce },
          body:    JSON.stringify({ status: newStatus }),
        });
        const json = await res.json();
        if (json.success) {
          card.dataset.status = newStatus;
          const badge = card.querySelector('.vupvup-q-badge');
          badge.textContent = statusLabel(newStatus);
          badge.className   = `vupvup-badge vupvup-status-${newStatus} vupvup-q-badge`;
        }
      } catch { alert('Fejl. Prøv igen.'); }
    }

    async function updateStats() {
      try {
        const statsBase = d.sceneId
          ? `${d.restUrl}/scenes/${d.sceneId}/questions`
          : `${d.restUrl}/events/${d.eventId}/questions`;
        const res = await fetch(
          `${statsBase}?status=all&orderby=newest`,
          { headers: { 'X-WP-Nonce': d.nonce } }
        );
        const qs = await res.json();
        if (!Array.isArray(qs)) return;
        document.getElementById('stat-total').textContent   = qs.length;
        document.getElementById('stat-pending').textContent = qs.filter(q => q.status === 'pending').length;
        document.getElementById('stat-asked').textContent   = qs.filter(q => q.status === 'asked').length;
        const pc = document.getElementById('count-pending');
        if (pc) pc.textContent = qs.filter(q => q.status === 'pending').length;
      } catch {}
    }

    // ── Highlight button ──────────────────────────────────────────────
    async function toggleHighlight(qId, highlighted, card) {
      try {
        const res  = await fetch(`${d.restUrl}/questions/${qId}/highlight`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': d.nonce },
          body:    JSON.stringify({ highlighted }),
        });
        const json = await res.json();
        if (json.success) {
          // Update all cards: clear others, set this one.
          document.querySelectorAll('.vupvup-q-card').forEach(c => {
            c.classList.remove('is-highlighted');
            c.querySelector('.btn-highlight')?.classList.remove('is-highlighted');
          });
          if (json.highlighted) {
            card.classList.add('is-highlighted');
            card.querySelector('.btn-highlight')?.classList.add('is-highlighted');
          }
        }
      } catch { alert('Fejl. Prøv igen.'); }
    }

    // ── Active slot buttons ───────────────────────────────────────────
    document.querySelectorAll('.vv-slot-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const slotIndex = parseInt(btn.dataset.slot, 10);
        try {
          await fetch(`${d.restUrl}/events/${d.eventId}/active-slot`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': d.nonce },
            body:    JSON.stringify({ slot_index: slotIndex }),
          });
          document.querySelectorAll('.vv-slot-btn').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
        } catch { alert('Fejl. Prøv igen.'); }
      });
    });

    // ── Mode buttons ─────────────────────────────────────────────────
    document.querySelectorAll('.vv-mode-btn').forEach(btn => {
      btn.addEventListener('click', async () => {
        const mode = btn.dataset.mode;
        try {
          await fetch(`${d.restUrl}/events/${d.eventId}/bigscreen-state`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': d.nonce },
            body:    JSON.stringify({ mode }),
          });
          document.querySelectorAll('.vv-mode-btn').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
        } catch { alert('Fejl. Prøv igen.'); }
      });
    });

    // Load initial bigscreen state to sync buttons.
    (async () => {
      try {
        const res  = await fetch(`${d.restUrl}/events/${d.eventId}/bigscreen-state`, { headers: { 'X-WP-Nonce': d.nonce } });
        const json = await res.json();
        if (json.mode) {
          document.querySelectorAll('.vv-mode-btn').forEach(b => b.classList.toggle('active', b.dataset.mode === json.mode));
        }
        if (typeof json.active_slot === 'number' && json.active_slot >= 0) {
          document.querySelectorAll('.vv-slot-btn').forEach(b => b.classList.toggle('active', parseInt(b.dataset.slot, 10) === json.active_slot));
        }
      } catch {}
    })();

    // Start
    loadQuestions(true);
    pollTimer = setInterval(() => loadQuestions(false), 5000);
  }

  // ─── Verify banner (resend email) ────────────────────────────────────────────
  const resendBtn = document.querySelector('.vupvup-resend-btn');
  if (resendBtn) {
    resendBtn.addEventListener('click', async () => {
      resendBtn.disabled = true;
      const msg = document.querySelector('.vupvup-resend-msg');
      try {
        const fd = new FormData();
        fd.append('action', 'vupvup_resend_verification');
        fd.append('nonce',  resendBtn.dataset.nonce);
        const res  = await fetch(d.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();
        if (msg) {
          msg.textContent = json.data || 'E-mail sendt!';
          msg.style.display = 'inline';
        }
      } catch {
        resendBtn.disabled = false;
      }
    });
  }

  // ─── Scene management ────────────────────────────────────────────────────────
  const sceneList        = document.getElementById('vv-scene-list');
  const sceneError       = document.getElementById('vv-scene-error');
  const addSceneForm     = document.getElementById('vv-add-scene-form');
  const addSceneBtn      = document.getElementById('vv-add-scene-btn');

  if (sceneList && d.eventId && d.canManageScenes) {
    loadScenes();
  }

  if (addSceneForm) {
    addSceneForm.addEventListener('submit', async e => {
      e.preventDefault();
      hideEl(sceneError);
      addSceneBtn.disabled = true;
      addSceneBtn.textContent = 'Opretter…';

      const name  = document.getElementById('vv-scene-name').value.trim();
      const email = document.getElementById('vv-facilitator-email').value.trim();
      if (!name) {
        showError(sceneError, 'Scenens navn er påkrævet.');
        addSceneBtn.disabled = false;
        addSceneBtn.textContent = '+ Opret scene';
        return;
      }

      try {
        const body = { name };
        if (email) body.facilitator_email = email;
        const res  = await fetch(`${d.restUrl}/events/${d.eventId}/scenes`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': d.nonce },
          body:    JSON.stringify(body),
        });
        const json = await res.json();
        if (res.ok) {
          document.getElementById('vv-scene-name').value       = '';
          document.getElementById('vv-facilitator-email').value = '';
          renderScene(json);
        } else {
          showError(sceneError, json.message || 'Noget gik galt.');
        }
      } catch { showError(sceneError, 'Netværksfejl. Prøv igen.'); }
      addSceneBtn.disabled = false;
      addSceneBtn.textContent = '+ Opret scene';
    });
  }

  async function loadScenes() {
    try {
      const res    = await fetch(`${d.restUrl}/events/${d.eventId}/scenes`, { headers: { 'X-WP-Nonce': d.nonce } });
      const scenes = await res.json();
      if (!Array.isArray(scenes)) return;
      sceneList.innerHTML = scenes.length === 0
        ? '<p class="vv-muted">Ingen scener endnu.</p>'
        : '';
      scenes.forEach(renderScene);
    } catch {}
  }

  function renderScene(scene) {
    const placeholder = sceneList && sceneList.querySelector('.vv-muted');
    if (placeholder) placeholder.remove();
    const row = document.createElement('div');
    row.className = 'vv-scene-row';
    row.dataset.sceneId = scene.id;
    row.innerHTML = `
      <span class="vv-scene-name">${escHtml(scene.name)}</span>
      <span class="vv-scene-fac">${scene.facilitator_name ? escHtml(scene.facilitator_name) : '<em>Ingen facilitator</em>'}</span>
      <a href="${escHtml(scene.dashboard_url)}" class="vupvup-btn vupvup-btn-ghost vupvup-btn-sm" target="_blank">Åbn ↗</a>
      <button class="vupvup-btn vupvup-btn-danger vupvup-btn-sm vv-del-scene">Slet</button>`;
    row.querySelector('.vv-del-scene').addEventListener('click', () => deleteScene(scene.id, row));
    if (sceneList) sceneList.appendChild(row);
  }

  async function deleteScene(sceneId, row) {
    if (!confirm('Er du sikker på, at du vil slette denne scene?')) return;
    try {
      const res = await fetch(`${d.restUrl}/scenes/${sceneId}`, {
        method: 'DELETE', headers: { 'X-WP-Nonce': d.nonce },
      });
      if (res.ok) {
        row.remove();
        if (sceneList && !sceneList.querySelector('.vv-scene-row')) {
          sceneList.innerHTML = '<p class="vv-muted">Ingen scener endnu.</p>';
        }
      }
    } catch { alert('Fejl. Prøv igen.'); }
  }

  // ─── Facilitator creation ─────────────────────────────────────────────────────
  const addFacForm  = document.getElementById('vv-add-facilitator-form');
  const addFacBtn   = document.getElementById('vv-add-facilitator-btn');
  const facError    = document.getElementById('vv-facilitator-error');
  const facSuccess  = document.getElementById('vv-facilitator-success');

  if (addFacForm) {
    addFacForm.addEventListener('submit', async e => {
      e.preventDefault();
      hideEl(facError); hideEl(facSuccess);
      addFacBtn.disabled = true;
      addFacBtn.textContent = 'Opretter…';

      const first = document.getElementById('vv-fac-first').value.trim();
      const last  = document.getElementById('vv-fac-last').value.trim();
      const email = document.getElementById('vv-fac-email').value.trim();
      const pass  = document.getElementById('vv-fac-pass').value;

      if (!first || !email || !pass) {
        showError(facError, 'Fornavn, e-mail og adgangskode er påkrævet.');
        addFacBtn.disabled = false;
        addFacBtn.textContent = '+ Opret facilitator';
        return;
      }

      try {
        const res  = await fetch(`${d.restUrl}/facilitators`, {
          method:  'POST',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': d.nonce },
          body:    JSON.stringify({ first_name: first, last_name: last, email, password: pass }),
        });
        const json = await res.json();
        if (res.ok) {
          addFacForm.reset();
          if (facSuccess) {
            facSuccess.textContent = `Facilitator "${json.display_name}" (${json.email}) er oprettet.`;
            facSuccess.classList.remove('vupvup-hidden');
          }
        } else {
          showError(facError, json.message || 'Noget gik galt.');
        }
      } catch { showError(facError, 'Netværksfejl. Prøv igen.'); }
      addFacBtn.disabled = false;
      addFacBtn.textContent = '+ Opret facilitator';
    });
  }

  // ─── Helpers ─────────────────────────────────────────────────────────────────
  async function post(url, body) {
    return fetch(url, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(body),
    });
  }
  function showError(el, msg) { if (!el) return; el.textContent = msg; el.classList.remove('vupvup-hidden'); }
  function hideEl(el)          { if (el) el.classList.add('vupvup-hidden'); }
  function fmtTime(str)        { return str ? new Date(str.replace(' ','T')).toLocaleTimeString('da-DK', { hour:'2-digit', minute:'2-digit' }) : ''; }
  function flashTitle(n)       { document.title = `(${n}) ${document.title.replace(/^\(\d+\) /, '')}`; }
  function statusLabel(s)      { return ({ pending:'Afventer', approved:'Godkendt', asked:'Stillet', rejected:'Afvist', hidden:'Skjult' })[s] || s; }
  function escHtml(s)          { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

})();
