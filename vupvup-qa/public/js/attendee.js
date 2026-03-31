/* global vupvupData */
(function () {
  'use strict';

  const d = vupvupData;

  // ─── Guest name flow ────────────────────────────────────────────────────────
  const guestForm    = document.getElementById('vupvup-guest-form');
  const guestInput   = document.getElementById('vupvup-guest-name-input');
  const guestSubmit  = document.getElementById('vupvup-guest-submit');
  const qSection     = document.getElementById('vupvup-question-section');
  const greeting     = document.getElementById('vupvup-greeting');

  let currentGuestName = d.guestName || '';

  if (guestSubmit) {
    guestSubmit.addEventListener('click', async () => {
      const name = guestInput.value.trim();
      if (name.length < 2) {
        guestInput.focus();
        return;
      }

      guestSubmit.disabled = true;

      const fd = new FormData();
      fd.append('action', 'vupvup_guest_login');
      fd.append('nonce', guestSubmit.dataset.nonce);
      fd.append('guest_name', name);

      try {
        const res = await fetch(d.ajaxUrl, { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
          currentGuestName = json.data.guest_name;
          d.nonce = json.data.rest_nonce;
          guestForm.style.display = 'none';
          qSection.classList.remove('vupvup-hidden');
          if (greeting) greeting.textContent = d.i18n.namePlaceholder + ': ' + currentGuestName;
        } else {
          alert(json.data || d.i18n.error);
        }
      } catch {
        alert(d.i18n.error);
      } finally {
        guestSubmit.disabled = false;
      }
    });

    if (guestInput) {
      guestInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') guestSubmit.click();
      });
    }
  }

  // ─── Question submission ─────────────────────────────────────────────────────
  const textarea    = document.getElementById('vupvup-question-input');
  const submitBtn   = document.getElementById('vupvup-submit-btn');
  const charDisplay = document.getElementById('vupvup-chars');
  const successBox  = document.getElementById('vupvup-form-success');
  const errorBox    = document.getElementById('vupvup-form-error');
  const speakerSel  = document.getElementById('vupvup-speaker-select');

  if (textarea && charDisplay) {
    textarea.addEventListener('input', () => {
      charDisplay.textContent = textarea.value.length;
    });
  }

  if (submitBtn && textarea) {
    submitBtn.addEventListener('click', async () => {
      const question = textarea.value.trim();
      if (!question) { textarea.focus(); return; }

      hideMessages();
      submitBtn.disabled = true;
      submitBtn.textContent = d.i18n.submitting;

      const body = {
        question,
        guest_name: currentGuestName || undefined,
        speaker_id: speakerSel ? (parseInt(speakerSel.value, 10) || undefined) : undefined,
      };

      try {
        const res = await fetch(`${d.restUrl}/events/${d.eventId}/questions`, {
          method:  'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce':   d.nonce,
          },
          body: JSON.stringify(body),
        });

        const json = await res.json();

        if (res.ok && json.success) {
          textarea.value = '';
          if (charDisplay) charDisplay.textContent = '0';
          showSuccess(json.message || d.i18n.success);
        } else {
          showError(json.message || d.i18n.error);
        }
      } catch {
        showError(d.i18n.error);
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = d.i18n.submit;
      }
    });
  }

  // ─── Helpers ─────────────────────────────────────────────────────────────────
  function showSuccess(msg) {
    if (!successBox) return;
    successBox.textContent = msg;
    successBox.classList.remove('vupvup-hidden');
    setTimeout(() => successBox.classList.add('vupvup-hidden'), 5000);
  }

  function showError(msg) {
    if (!errorBox) return;
    errorBox.textContent = msg;
    errorBox.classList.remove('vupvup-hidden');
    setTimeout(() => errorBox.classList.add('vupvup-hidden'), 6000);
  }

  function hideMessages() {
    successBox && successBox.classList.add('vupvup-hidden');
    errorBox   && errorBox.classList.add('vupvup-hidden');
  }
})();
