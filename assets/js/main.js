/*!
 * Econur — front-end interactions.
 * Vanilla JS, dependency-free, deferred. Progressive enhancement only:
 * everything degrades gracefully if JS fails. (spec §2, §8)
 */
(function () {
  'use strict';

  var doc = document;
  var body = doc.body;

  /* -------------------------------------------------------------------------
   * Mobile navigation drawer
   * ---------------------------------------------------------------------- */
  var navToggle = doc.querySelector('[data-econ-nav-toggle]');
  var scrim = doc.querySelector('[data-econ-nav-scrim]');

  function setNav(open) {
    body.classList.toggle('econ-nav-open', open);
    if (navToggle) {
      navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
  }

  if (navToggle) {
    navToggle.addEventListener('click', function () {
      setNav(!body.classList.contains('econ-nav-open'));
    });
  }
  if (scrim) {
    scrim.addEventListener('click', function () { setNav(false); });
  }
  doc.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && body.classList.contains('econ-nav-open')) {
      setNav(false);
    }
  });

  /* -------------------------------------------------------------------------
   * Sticky header scroll state (adds a subtle shadow once scrolled)
   * ---------------------------------------------------------------------- */
  var header = doc.querySelector('[data-econ-header]');
  if (header) {
    var ticking = false;
    var applyHeaderState = function () {
      var y = window.pageYOffset || doc.documentElement.scrollTop;
      header.classList.toggle('is-scrolled', y > 8);
      ticking = false;
    };
    window.addEventListener('scroll', function () {
      if (!ticking) {
        window.requestAnimationFrame(applyHeaderState);
        ticking = true;
      }
    }, { passive: true });
    applyHeaderState();
  }

  /* -------------------------------------------------------------------------
   * Scroll-reveal — fade/slide-up on enter. Respects reduced-motion and
   * falls back to "always visible" without IntersectionObserver. (spec §3.2)
   * ---------------------------------------------------------------------- */
  var reveals = doc.querySelectorAll('[data-econ-reveal]');
  var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (reveals.length) {
    if (!prefersReduced && 'IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-inview');
            io.unobserve(entry.target);
          }
        });
      }, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });

      reveals.forEach(function (el) { io.observe(el); });
    } else {
      reveals.forEach(function (el) { el.classList.add('is-inview'); });
    }
  }

  /* -------------------------------------------------------------------------
   * Cache-safe cart count
   * If the WooCommerce "items in cart" cookie is set, sync the header count from
   * a lightweight endpoint — so the count is correct even when the page HTML is
   * full-page cached. Empty carts make no request. (spec §8)
   * ---------------------------------------------------------------------- */
  var econdata = window.econurData || {};
  if (econdata.cartCount && /woocommerce_items_in_cart=1|woocommerce_cart_hash=/.test(doc.cookie)) {
    fetch(econdata.cartCount, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && typeof res.count !== 'undefined') {
          doc.querySelectorAll('[data-econ-cart-count]').forEach(function (el) { el.textContent = res.count; });
        }
      })
      .catch(function () {});
  }
})();
