/*!
 * Econur — homepage grid: filter + live search (front page only).
 * Cards carry their own size selector + Order/Add-to-Cart (handled by buybox.js),
 * so there is no quick-view modal. Vanilla, dependency-free, deferred.
 * No-JS users still see every card and can open the full product page.
 */
(function () {
  'use strict';

  var doc = document;
  var grid = doc.querySelector('[data-econ-grid]');
  if (!grid) return;

  var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-econ-card]'));
  var emptyMsg = doc.querySelector('[data-econ-grid-empty]');
  var statusEl = doc.querySelector('[data-econ-grid-status]');
  var searchInput = doc.querySelector('[data-econ-search]');
  var state = { cat: '*', concern: '*', q: '' };

  function tokenMatch(list, value) {
    if (value === '*') return true;
    return (' ' + (list || '') + ' ').indexOf(' ' + value + ' ') > -1;
  }

  function applyFilters() {
    var visible = 0;
    for (var i = 0; i < cards.length; i++) {
      var card = cards[i];
      var show =
        tokenMatch(card.getAttribute('data-cats'), state.cat) &&
        tokenMatch(card.getAttribute('data-concerns'), state.concern) &&
        (state.q === '' || (card.getAttribute('data-search') || '').indexOf(state.q) > -1);
      card.classList.toggle('is-hidden', !show);
      if (show) visible++;
    }
    if (emptyMsg) emptyMsg.hidden = visible !== 0;
    if (statusEl) statusEl.textContent = visible + ' of ' + cards.length + ' soaps shown';
  }

  doc.addEventListener('click', function (e) {
    var chip = e.target.closest('.econ-filters__row .econ-chip[data-value]');
    if (!chip) return;
    var row = chip.closest('[data-econ-filter]');
    if (!row) return;
    var group = row.getAttribute('data-econ-filter');
    row.querySelectorAll('.econ-chip').forEach(function (c) {
      c.classList.remove('is-active');
      if (c.hasAttribute('aria-pressed')) c.setAttribute('aria-pressed', 'false');
    });
    chip.classList.add('is-active');
    chip.setAttribute('aria-pressed', 'true');
    state[group] = chip.getAttribute('data-value');
    applyFilters();
  });

  if (searchInput) {
    var pending = false;
    searchInput.addEventListener('input', function () {
      if (pending) return;
      pending = true;
      window.requestAnimationFrame(function () {
        state.q = searchInput.value.trim().toLowerCase();
        applyFilters();
        pending = false;
      });
    });
  }
})();
