(function () {
  'use strict';

  var DAY_MS = 24 * 60 * 60 * 1000;

  function clamp(value, min, max) {
    return Math.min(max, Math.max(min, value));
  }

  function getPrefix(label) {
    return label === 'until' ? 'あと' : '残り';
  }

  function calculateState(startDate, targetDate, nowDate) {
    var start = startDate.getTime();
    var target = targetDate.getTime();
    var now = nowDate.getTime();

    if (!Number.isFinite(start) || !Number.isFinite(target) || target <= start) {
      return null;
    }

    if (now >= target) {
      return {
        ended: true,
        days: 0,
        progress: 100
      };
    }

    var remainingMs = Math.max(0, target - now);
    var totalMs = Math.max(1, target - start);
    var elapsedMs = clamp(now - start, 0, totalMs);

    return {
      ended: false,
      days: Math.ceil(remainingMs / DAY_MS),
      progress: clamp(Math.round((elapsedMs / totalMs) * 100), 0, 100)
    };
  }

  function renderTimer(timer, nowDate) {
    var startDate = new Date(timer.dataset.start || '');
    var targetDate = new Date(timer.dataset.target || '');
    var state = calculateState(startDate, targetDate, nowDate);
    var text = timer.querySelector('.wpcm-countdown__text');
    var meter = timer.querySelector('.wpcm-countdown__meter');
    var bar = timer.querySelector('.wpcm-countdown__bar');

    if (!state || !text || !meter || !bar) {
      return;
    }

    if (state.ended) {
      text.innerHTML = '<span class="wpcm-countdown__ended"></span>';
      text.querySelector('.wpcm-countdown__ended').textContent = timer.dataset.endText || '終了';
    } else {
      var redUnder = Number.parseInt(timer.dataset.redUnder || '-1', 10);
      var shouldRed = Number.isFinite(redUnder) && redUnder >= 0 && state.days <= redUnder;

      text.innerHTML =
        '<span class="wpcm-countdown__prefix"></span>' +
        '<span class="wpcm-countdown__days"></span>' +
        '<span class="wpcm-countdown__suffix">日</span>';

      text.querySelector('.wpcm-countdown__prefix').textContent = getPrefix(timer.dataset.label || 'remaining');

      var days = text.querySelector('.wpcm-countdown__days');
      days.textContent = String(state.days);
      days.classList.toggle('is-red', shouldRed);
    }

    meter.setAttribute('aria-valuenow', String(state.progress));
    bar.style.width = state.progress + '%';
  }

  function renderAll() {
    var timers = document.querySelectorAll('.wpcm-countdown');
    var nowDate = new Date();

    timers.forEach(function (timer) {
      renderTimer(timer, nowDate);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderAll);
  } else {
    renderAll();
  }

  window.setInterval(renderAll, 1000);
})();
