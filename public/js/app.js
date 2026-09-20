const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

document.documentElement.classList.add('js-ready');
requestAnimationFrame(() => document.body.classList.add('page-ready'));

document.querySelectorAll('.chip').forEach(chip => {
  chip.addEventListener('click', () => {
    const group = chip.closest('[data-chip-group]') || document;
    group.querySelectorAll('.chip').forEach(item => {
      item.classList.remove('active');
      if (item.matches('button, [role="button"]')) item.setAttribute('aria-pressed', 'false');
    });
    chip.classList.add('active');
    if (chip.matches('button, [role="button"]')) chip.setAttribute('aria-pressed', 'true');
  });
});

function setupSidebar() {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.querySelector('[data-sidebar-toggle], .menu-toggle');
  const overlay = document.querySelector('[data-sidebar-overlay]');
  if (!sidebar || !toggle) return;

  const mobile = window.matchMedia('(max-width: 780px)');
  toggle.removeAttribute('onclick');
  toggle.setAttribute('aria-controls', sidebar.id);

  const setOpen = (open, { restoreFocus = false } = {}) => {
    const shouldOpen = mobile.matches && open;
    sidebar.classList.toggle('open', shouldOpen);
    document.body.classList.toggle('sidebar-open', shouldOpen);
    toggle.setAttribute('aria-expanded', String(shouldOpen));
    sidebar.setAttribute('aria-hidden', String(mobile.matches && !shouldOpen));
    sidebar.inert = mobile.matches && !shouldOpen;

    if (overlay) {
      overlay.classList.toggle('is-visible', shouldOpen);
      overlay.setAttribute('aria-hidden', String(!shouldOpen));
      overlay.tabIndex = -1;
    }

    if (restoreFocus && !shouldOpen) toggle.focus({ preventScroll: true });
  };

  toggle.addEventListener('click', event => {
    event.preventDefault();
    setOpen(!sidebar.classList.contains('open'));
  });
  overlay?.addEventListener('click', () => setOpen(false, { restoreFocus: true }));
  sidebar.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', () => {
      if (mobile.matches) setOpen(false);
    });
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && sidebar.classList.contains('open')) {
      setOpen(false, { restoreFocus: true });
    }
  });
  mobile.addEventListener('change', () => setOpen(false));
  setOpen(false);
}

function setupProgressiveGrids() {
  const grids = [...document.querySelectorAll('[data-progressive-grid]')];
  const buttons = [...document.querySelectorAll('[data-load-more]')];

  grids.forEach((grid, gridIndex) => {
    const items = [...grid.children].filter(item => item.matches('[data-progressive-item], .role-card'));
    if (!items.length) return;

    const batchSize = Math.max(1, Number.parseInt(grid.dataset.batchSize || '6', 10) || 6);
    let visibleCount = Math.min(batchSize, items.length);
    const button = buttons.find(candidate => candidate.getAttribute('aria-controls') === grid.id)
      || (grids.length === 1 ? buttons[0] : buttons[gridIndex]);
    const count = document.querySelector(`[data-progressive-count][aria-controls="${grid.id}"]`)
      || grid.parentElement?.querySelector('[data-progressive-count]');

    if (button && grid.id) button.setAttribute('aria-controls', grid.id);
    if (count) {
      count.setAttribute('role', 'status');
      count.setAttribute('aria-live', 'polite');
      count.setAttribute('aria-atomic', 'true');
    }

    const update = () => {
      items.forEach((item, index) => {
        const visible = index < visibleCount;
        item.hidden = !visible;
        item.setAttribute('aria-hidden', String(!visible));
      });

      const remaining = Math.max(0, items.length - visibleCount);
      if (count) count.textContent = `Menampilkan ${visibleCount} dari ${items.length} pilihan karier`;
      if (!button) return;

      button.hidden = remaining === 0;
      button.setAttribute('aria-expanded', String(remaining === 0));
      if (remaining > 0) {
        const amount = Math.min(batchSize, remaining);
        const label = button.querySelector('[data-load-more-label]') || button;
        label.textContent = `Tampilkan ${amount} pilihan lagi`;
        button.setAttribute('aria-label', `Tampilkan ${amount} pilihan karier lagi`);
      }
    };

    button?.addEventListener('click', () => {
      const previousCount = visibleCount;
      visibleCount = Math.min(items.length, visibleCount + batchSize);
      update();
      items.slice(previousCount, visibleCount).forEach(item => item.classList.add('is-progressively-visible'));
    });

    update();
  });
}

function setupReveals() {
  const selectors = [
    '.dashboard-hero',
    '.discovery-hero',
    '.career-page-hero',
    '.profile-hero',
    '.career-detail-hero',
    '.course-detail-hero',
    '.target-overview',
    '.metric-grid > .metric-card',
    '.dashboard-grid > .card',
    '.dashboard-aside > .card',
    '.recommendation-list > .recommendation-row',
    '.career-role-grid > .role-card',
    '.catalog-grid > .catalog-card',
    '.project-grid > .project-card',
    '.career-profile-form > .form-section',
    '.assessment-list > .assessment-row',
    '.assessment-question-list > .assessment-question',
    '.assessment-result-hero',
    '.assessment-breakdown'
  ];
  const items = [...document.querySelectorAll(selectors.join(','))];
  if (!items.length) return;

  const groupSelector = '.metric-grid, .dashboard-grid, .recommendation-list, .career-role-grid, .catalog-grid, .project-grid, .career-profile-form, .assessment-list, .assessment-question-list';
  const groupCounts = new Map();

  items.forEach(item => {
    const group = item.closest(groupSelector) || item.parentElement || document.body;
    const index = groupCounts.get(group) || 0;
    const delay = Math.min(index, 5) * 40;
    groupCounts.set(group, index + 1);
    item.classList.add('reveal-item');
    item.style.setProperty('--reveal-delay', `${delay}ms`);
    item.style.transitionDelay = `${delay}ms`;
  });

  const revealAll = () => {
    items.forEach(item => {
      item.style.transitionDelay = '0ms';
      item.classList.add('is-visible');
    });
  };

  if (reducedMotion.matches || !('IntersectionObserver' in window)) {
    revealAll();
    return;
  }

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -6% 0px' });

  items.forEach(item => observer.observe(item));
  const handleReducedMotion = event => {
    if (!event.matches) return;
    observer.disconnect();
    revealAll();
  };
  reducedMotion.addEventListener('change', handleReducedMotion, { once: true });
  window.addEventListener('pagehide', () => observer.disconnect(), { once: true });
}

function setupProgressBars() {
  const bars = [...document.querySelectorAll('.progress > i')];
  if (!bars.length) return;

  bars.forEach(bar => {
    const inlineValue = bar.style.width;
    const value = Number.parseFloat(bar.closest('[role="progressbar"]')?.getAttribute('aria-valuenow') ?? bar.dataset.progress ?? (inlineValue || '0'));
    const normalized = Number.isFinite(value) ? Math.min(100, Math.max(0, value)) : 0;
    bar.style.removeProperty('width');
    bar.style.setProperty('--progress-scale', String(normalized / 100));
    bar.dataset.progress = String(normalized);
    bar.classList.add('progress-fill', 'is-filled');
  });

}

function setupAsciiField(field) {
  const ramp = ' .:-=+*#';
  const frameInterval = 1000 / 14;
  const fixedTimeValue = new URLSearchParams(window.location.search).get('ascii-t');
  const fixedTime = fixedTimeValue === null ? null : Number.parseFloat(fixedTimeValue);
  const seedText = field.dataset.asciiSeed || 'is-path';
  const seed = [...seedText].reduce((value, character) => ((value * 31) + character.charCodeAt(0)) >>> 0, 2166136261);
  const phase = (seed % 628) / 100;
  let columns = 48;
  let rows = 14;
  let animationFrame = 0;
  let lastFrameAt = -Infinity;
  let lastRenderedTime = 0;
  let isVisible = true;
  let stopped = false;

  const measure = () => {
    const requestedColumns = Number.parseInt(field.dataset.asciiColumns || '', 10);
    const measuredColumns = Math.round((field.clientWidth || 540) / 11);
    columns = Math.max(34, Math.min(56, requestedColumns || measuredColumns));
    rows = Math.max(10, Math.min(18, Math.round(columns * (14 / 48))));
  };

  const render = time => {
    const frameTime = Number.isFinite(time) ? time : 0;
    let output = '';
    const safeColumns = Math.max(2, columns - 1);
    const safeRows = Math.max(2, rows - 1);

    for (let y = 0; y < rows; y += 1) {
      for (let x = 0; x < columns; x += 1) {
        const nx = x / safeColumns;
        const ny = y / safeRows;
        const path = 0.72 - (nx * 0.43) + (Math.sin((nx * Math.PI * 2.3) + phase + (frameTime * 0.00055)) * 0.065);
        const distance = Math.abs(ny - path);
        const trail = Math.exp(-distance * 24);
        const current = 0.55 + (Math.sin((x * 0.38) - (frameTime * 0.0022) + phase) * 0.45);
        const atmosphere = (Math.sin((x * 0.19) + (y * 0.47) + phase + (frameTime * 0.00035)) + 1) * 0.035;
        const nodePosition = Math.min(...[0.12, 0.36, 0.62, 0.86].map(node => Math.abs(nx - node)));
        const node = nodePosition < 0.018 && distance < 0.13 ? Math.exp(-(distance + nodePosition) * 19) : 0;
        const luminance = Math.min(1, atmosphere + (trail * (0.28 + (current * 0.48))) + (node * 0.48));
        output += ramp[Math.round(luminance * (ramp.length - 1))];
      }
      output += '\n';
    }

    field.textContent = output;
    field.dataset.asciiReady = 'true';
    lastRenderedTime = frameTime;
  };

  const stop = () => {
    if (animationFrame) cancelAnimationFrame(animationFrame);
    animationFrame = 0;
  };

  const loop = now => {
    if (stopped || document.hidden || !isVisible || reducedMotion.matches) {
      animationFrame = 0;
      return;
    }
    if (now - lastFrameAt >= frameInterval) {
      render(now);
      lastFrameAt = now;
    }
    animationFrame = requestAnimationFrame(loop);
  };

  const start = () => {
    if (animationFrame || stopped || document.hidden || !isVisible || reducedMotion.matches || Number.isFinite(fixedTime)) return;
    animationFrame = requestAnimationFrame(loop);
  };

  const renderCurrentMode = () => {
    stop();
    if (Number.isFinite(fixedTime)) render(fixedTime);
    else if (reducedMotion.matches) render(1400);
    else start();
  };

  measure();
  render(Number.isFinite(fixedTime) ? fixedTime : 0);

  const visibilityObserver = 'IntersectionObserver' in window
    ? new IntersectionObserver(entries => {
      isVisible = entries[0]?.isIntersecting ?? true;
      if (isVisible) start();
      else stop();
    }, { threshold: 0.01 })
    : null;
  visibilityObserver?.observe(field);

  const resizeObserver = 'ResizeObserver' in window
    ? new ResizeObserver(() => {
      measure();
      render(Number.isFinite(fixedTime) ? fixedTime : (reducedMotion.matches ? 1400 : lastRenderedTime));
    })
    : null;
  resizeObserver?.observe(field);

  const handleVisibility = () => document.hidden ? stop() : start();
  const handleMotionPreference = () => renderCurrentMode();
  document.addEventListener('visibilitychange', handleVisibility);
  reducedMotion.addEventListener('change', handleMotionPreference);
  renderCurrentMode();

  window.addEventListener('pagehide', () => {
    stopped = true;
    stop();
    visibilityObserver?.disconnect();
    resizeObserver?.disconnect();
    document.removeEventListener('visibilitychange', handleVisibility);
    reducedMotion.removeEventListener('change', handleMotionPreference);
  }, { once: true });
}

function setupPasswordToggles() {
  document.querySelectorAll('[data-password-toggle]').forEach(toggle => {
    toggle.addEventListener('click', () => {
      const controlledId = toggle.getAttribute('aria-controls');
      const input = (controlledId && document.getElementById(controlledId))
        || toggle.closest('.password-field')?.querySelector('input');
      if (!(input instanceof HTMLInputElement)) return;

      const reveal = input.type === 'password';
      input.type = reveal ? 'text' : 'password';
      toggle.classList.toggle('is-revealed', reveal);
      toggle.setAttribute('aria-pressed', String(reveal));
      toggle.setAttribute('aria-label', reveal ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
      toggle.setAttribute('title', reveal ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    });
  });
}

function setupSubmitLoading() {
  const forms = document.querySelectorAll('.career-profile-form, [data-submit-loading]');
  forms.forEach(form => {
    form.addEventListener('submit', event => {
      if (event.defaultPrevented) return;
      const button = event.submitter instanceof HTMLButtonElement
        ? event.submitter
        : form.querySelector('button[type="submit"], .profile-submit .btn');
      if (!(button instanceof HTMLButtonElement) || button.disabled) return;

      button.dataset.originalAriaLabel = button.getAttribute('aria-label') || '';
      button.disabled = true;
      button.classList.add('is-loading');
      button.setAttribute('aria-busy', 'true');
      button.setAttribute('aria-label', form.dataset.loadingLabel || 'Sedang menghitung rekomendasi');

      if (!button.querySelector('.button-spinner')) {
        const spinner = document.createElement('span');
        spinner.className = 'button-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        button.prepend(spinner);
      }
    });
  });

  window.addEventListener('pageshow', event => {
    if (!event.persisted) return;
    document.querySelectorAll('button.is-loading').forEach(button => {
      button.disabled = false;
      button.classList.remove('is-loading');
      button.removeAttribute('aria-busy');
      const originalLabel = button.dataset.originalAriaLabel;
      if (originalLabel) button.setAttribute('aria-label', originalLabel);
      else button.removeAttribute('aria-label');
      delete button.dataset.originalAriaLabel;
      button.querySelector('.button-spinner')?.remove();
    });
  });
}

function setupSuccessBanners() {
  const timers = [];
  document.querySelectorAll('.success-banner').forEach(banner => {
    if (!banner.hasAttribute('role')) banner.setAttribute('role', 'status');
    banner.setAttribute('aria-live', 'polite');
    banner.setAttribute('aria-atomic', 'true');

    timers.push(window.setTimeout(() => {
      if (reducedMotion.matches) banner.remove();
      else banner.classList.add('is-hiding');
    }, 4500));
    timers.push(window.setTimeout(() => banner.remove(), 4900));
  });
  window.addEventListener('pagehide', () => timers.forEach(timer => clearTimeout(timer)), { once: true });
}

function setupAssessmentForms() {
  document.querySelectorAll('[data-completed-draft]').forEach(marker => {
    try { sessionStorage.removeItem(marker.dataset.completedDraft); } catch {}
  });
  document.querySelectorAll('[data-error-summary]').forEach(summary => {
    summary.focus({ preventScroll: true });
    summary.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'center' });
  });

  document.querySelectorAll('[data-assessment-form]').forEach(form => {
    const draftKey = form.dataset.draftKey;
    const draftStatus = form.querySelector('[data-draft-status]');
    const sessionNotice = form.querySelector('[data-session-notice]');
    let lastServerContact = Date.now();
    let draftTimer;
    let draftQueue = Promise.resolve();
    let submitting = false;
    const sendDraft = () => {
      if (submitting || !form.dataset.draftUrl) return;
      const answers = Object.fromEntries([...form.querySelectorAll('input[type="radio"]:checked')].map(input => [input.name.match(/\[(\d+)\]/)[1], input.value]));
      if (!Object.keys(answers).length) return;
      draftQueue = draftQueue.then(async () => {
        if (submitting) return;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
          const response = await fetch(form.dataset.draftUrl, {
            signal: controller.signal,
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
            body: JSON.stringify({ answers }),
          });
          if (response.status === 401 || response.status === 419) {
            if (draftStatus) draftStatus.textContent = 'Sesi berakhir. Pilihan terbaru masih ada di tab ini. Masuk kembali sebelum melanjutkan.';
            return;
          }
          if (!response.ok || response.redirected) throw new Error('Draft rejected');
          lastServerContact = Date.now();
          if (sessionNotice) sessionNotice.hidden = true;
          if (draftStatus) draftStatus.textContent = 'Draft tersimpan di akunmu. Kamu dapat melanjutkan setelah masuk kembali.';
        } catch {
          if (draftStatus) draftStatus.textContent = 'Draft belum tersimpan di server. Tetap buka tab ini; penyimpanan dicoba lagi saat koneksi kembali.';
        } finally {
          clearTimeout(timeout);
        }
      });
    };
    const saveDraft = () => {
      if (!draftKey) return;
      try {
        const answers = Object.fromEntries([...form.querySelectorAll('input[type="radio"]:checked')].map(input => [input.name, input.value]));
        sessionStorage.setItem(draftKey, JSON.stringify({ answers, savedAt: Date.now() }));
        if (draftStatus) draftStatus.textContent = 'Menyimpan jawaban ke akunmu…';
      } catch {
        if (draftStatus) draftStatus.textContent = 'Draft tidak dapat disimpan. Jangan tutup atau muat ulang halaman sebelum mengirim.';
      }
      clearTimeout(draftTimer);
      draftTimer = setTimeout(sendDraft, 600);
    };
    try {
      const draft = JSON.parse(sessionStorage.getItem(draftKey) || 'null');
      if (draft && Date.now() - draft.savedAt < 86400000 && draft.savedAt > (Date.parse(form.dataset.serverDraftAt) || 0)) {
        form.querySelectorAll('input[type="radio"]').forEach(input => {
          if (Object.hasOwn(draft.answers, input.name)) input.checked = draft.answers[input.name] === input.value;
        });
        if (draftStatus) draftStatus.textContent = 'Draft sebelumnya dipulihkan. Periksa jawaban sebelum mengirim.';
        draftTimer = setTimeout(sendDraft, 600);
      } else if (draftKey) sessionStorage.removeItem(draftKey);
    } catch {
      if (draftStatus) draftStatus.textContent = 'Penyimpanan draft tidak tersedia. Jangan muat ulang halaman sebelum mengirim.';
    }
    form.addEventListener('change', saveDraft);
    form.addEventListener('submit', saveDraft);
    form.addEventListener('submit', event => { queueMicrotask(() => { if (!event.defaultPrevented) { submitting = true; clearTimeout(draftTimer); } }); });
    window.addEventListener('online', sendDraft);
    const updateSessionNotice = () => {
      const remaining = Number(form.dataset.sessionSeconds) - Math.floor((Date.now() - lastServerContact) / 1000);
      if (!sessionNotice || remaining > 30) return;
      sessionNotice.hidden = false;
      sessionNotice.textContent = remaining > 0
        ? `Sesi tanpa penyimpanan berakhir sekitar ${remaining} detik lagi. Pilih jawaban untuk menyimpan progres.`
        : 'Sesi mungkin berakhir. Draft yang sudah tersimpan dapat dilanjutkan setelah masuk kembali.';
    };
    let sessionTimer = setInterval(updateSessionNotice, 1000);
    window.addEventListener('pagehide', () => { clearInterval(sessionTimer); clearTimeout(draftTimer); });
    window.addEventListener('pageshow', () => {
      submitting = false;
      clearInterval(sessionTimer);
      sessionTimer = setInterval(updateSessionNotice, 1000);
      updateSessionNotice();
    });
    const questions = [...form.querySelectorAll('[data-assessment-question]')];
    const pages = [...form.querySelectorAll('[data-assessment-page]')];
    const trackerButtons = [...form.querySelectorAll('[data-question-jump]')];
    const count = form.querySelector('[data-answered-count]');
    const progress = form.querySelector('[data-assessment-progress]');
    const fill = progress?.querySelector('i');
    const status = form.querySelector('[data-assessment-status]');
    const section = form.querySelector('[data-assessment-section]');
    const range = form.querySelector('[data-assessment-range]');
    const previous = form.querySelector('[data-assessment-previous]');
    const next = form.querySelector('[data-assessment-next]');
    const submit = form.querySelector('[data-assessment-submit]');
    let currentPage = 0;
    let currentQuestion = 1;

    const showPage = (pageIndex, focusQuestion = null) => {
      if (!pages.length) return;
      currentPage = Math.max(0, Math.min(pageIndex, pages.length - 1));
      pages.forEach((page, index) => {
        const isActive = index === currentPage;
        page.hidden = !isActive;
        page.classList.toggle('is-active', isActive);
      });

      const first = currentPage * 5 + 1;
      const last = Math.min(first + 4, questions.length);
      currentQuestion = focusQuestion || (currentQuestion >= first && currentQuestion <= last ? currentQuestion : first);
      if (section) section.textContent = `Bagian ${currentPage + 1} dari ${pages.length}`;
      if (range) range.textContent = `Soal ${first}-${last}`;
      if (previous instanceof HTMLButtonElement) previous.disabled = currentPage === 0;
      if (next instanceof HTMLButtonElement) next.hidden = currentPage === pages.length - 1;
      trackerButtons.forEach((button, index) => {
        const isCurrent = index + 1 === currentQuestion;
        button.classList.toggle('is-current', isCurrent);
        if (isCurrent) button.setAttribute('aria-current', 'step');
        else button.removeAttribute('aria-current');
      });

      if (focusQuestion) {
        const target = questions[focusQuestion - 1];
        target?.focus({ preventScroll: true });
        target?.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'start' });
      }
    };

    const update = () => {
      const answered = questions.filter(question => question.querySelector('input[type="radio"]:checked')).length;
      const total = questions.length;
      const ratio = total > 0 ? answered / total : 0;
      if (count) count.textContent = String(answered);
      if (progress) progress.setAttribute('aria-valuenow', String(answered));
      if (fill) fill.style.setProperty('--progress-scale', String(ratio));
      questions.forEach((question, index) => {
        const isAnswered = Boolean(question.querySelector('input[type="radio"]:checked'));
        question.classList.toggle('is-answered', isAnswered);
        const tracker = trackerButtons[index];
        tracker?.classList.toggle('is-answered', isAnswered);
        if (tracker) tracker.setAttribute('aria-label', `Soal ${index + 1}, ${isAnswered ? 'sudah dijawab' : 'belum dijawab'}`);
      });
      if (submit instanceof HTMLButtonElement) submit.disabled = total === 0 || answered !== total;
      if (status) status.textContent = total === 0
        ? 'Pertanyaan belum tersedia.'
        : answered === total
        ? 'Semua pertanyaan terjawab. Hasil siap dikirim.'
        : `${total - answered} pertanyaan belum dijawab.`;
    };

    form.addEventListener('change', event => {
      if (event.target.matches('input[type="radio"]')) {
        const question = event.target.closest('[data-assessment-question]');
        currentQuestion = Number(question?.dataset.questionNumber || currentQuestion);
        showPage(currentPage);
        update();
      }
    });

    previous?.addEventListener('click', () => {
      showPage(currentPage - 1);
      form.querySelector('.assessment-page-heading')?.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'start' });
    });

    next?.addEventListener('click', () => {
      showPage(currentPage + 1);
      form.querySelector('.assessment-page-heading')?.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'start' });
    });

    trackerButtons.forEach((button, index) => {
      button.addEventListener('click', () => showPage(Math.floor(index / 5), index + 1));
    });

    form.addEventListener('submit', event => {
      const firstUnanswered = questions.findIndex(question => !question.querySelector('input[type="radio"]:checked'));
      if (firstUnanswered === -1) return;

      event.preventDefault();
      showPage(Math.floor(firstUnanswered / 5), firstUnanswered + 1);
      if (status) status.textContent = `Soal ${firstUnanswered + 1} belum dijawab.`;
    });

    showPage(0);
    update();
  });
}

function setupPreAssessmentDialog() {
  const dialog = document.querySelector('[data-preassessment-dialog]');
  if (!(dialog instanceof HTMLDialogElement)) return;

  dialog.querySelectorAll('[data-dialog-close]').forEach(button => {
    button.addEventListener('click', () => dialog.close());
  });

  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });

  if (typeof dialog.showModal === 'function') dialog.showModal();
  else dialog.setAttribute('open', '');
}

function setupRoleSelector() {
  document.querySelectorAll('[data-role-selector]').forEach(form => {
    const maximum = Number(form.dataset.maxSelection || 3);
    const choices = [...form.querySelectorAll('[data-role-choice]')];
    const count = form.querySelector('[data-role-selection-count]');
    const help = form.querySelector('[data-role-selection-help]');
    const submit = form.querySelector('[data-role-submit]');
    const primary = form.querySelector('[data-primary-role]');
    let selectionOrder = choices.filter(choice => choice.checked).map(choice => choice.value);
    const update = () => {
      const selected = choices.filter(choice => choice.checked);
      const isFull = selected.length >= maximum;
      if (count) count.textContent = `${selected.length} dari ${maximum} peran dipilih`;
      if (primary) primary.value = selectionOrder[0] || '';
      if (help) help.textContent = selected.length === 0
        ? 'Pilih peran yang ingin kamu eksplorasi.'
        : selected.length === maximum
          ? 'Batas pilihan tercapai. Hapus satu pilihan untuk menggantinya.'
          : `Pilihan pertama menjadi fokus assessment. Kamu masih dapat menambahkan peran lain.`;
      if (submit instanceof HTMLButtonElement) submit.disabled = selected.length === 0;
      choices.forEach(choice => {
        const card = choice.closest('[data-role-card]');
        const blocked = isFull && !choice.checked;
        choice.disabled = blocked;
        card?.classList.toggle('is-unavailable', blocked);
      });
    };
    choices.forEach(choice => choice.addEventListener('change', () => {
      selectionOrder = selectionOrder.filter(value => value !== choice.value);
      if (choice.checked) selectionOrder.push(choice.value);
      update();
    }));
    update();
  });
}

setupSidebar();
setupProgressiveGrids();
setupReveals();
setupProgressBars();
document.querySelectorAll('[data-ascii-field]').forEach(setupAsciiField);
setupPasswordToggles();
setupSuccessBanners();
setupAssessmentForms();
setupSubmitLoading();
setupPreAssessmentDialog();
setupRoleSelector();
