/**
 * Sign in / create account toggle.
 *
 * Progressive enhancement only. The panels are already switched server-side from
 * ?econ_view=login|register, so with JS off the tabs behave as ordinary links
 * and everything still works. This just makes the switch instant.
 *
 * @package Econur
 */
(function () {
  'use strict';

  var root = document.querySelector('[data-econ-auth]');
  if (!root) {
    return;
  }

  var tabs = root.querySelectorAll('[data-econ-auth-tab]');
  if (!tabs.length) {
    return; // Registration disabled — nothing to switch between.
  }

  function show(view, focus) {
    root.setAttribute('data-view', view);

    Array.prototype.forEach.call(tabs, function (tab) {
      // Only the segmented control carries aria-selected; the inline crosslinks
      // are plain links and shouldn't claim to be tabs.
      if (tab.getAttribute('role') === 'tab') {
        tab.setAttribute('aria-selected', tab.getAttribute('data-econ-auth-tab') === view ? 'true' : 'false');
      }
    });

    // Keep the URL honest, so a refresh or a shared link lands on the same panel.
    if (window.history && window.history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.set('econ_view', view);
      window.history.replaceState({}, '', url.toString());
    }

    if (focus) {
      var panel = root.querySelector('[data-econ-auth-panel="' + view + '"]');
      var field = panel && panel.querySelector('input:not([type="hidden"])');
      if (field) {
        field.focus({ preventScroll: true });
      }
    }
  }

  Array.prototype.forEach.call(tabs, function (tab) {
    tab.addEventListener('click', function (event) {
      event.preventDefault();
      show(tab.getAttribute('data-econ-auth-tab'), true);
    });
  });
})();
