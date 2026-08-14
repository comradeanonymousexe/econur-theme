/*!
 * Econur — single product interactions.
 * Lightweight gallery: click a thumbnail to swap the main image.
 * No flexslider/photoswipe (protects the mobile-speed budget, spec §8).
 * Vanilla, deferred. Buy-box (size + add-to-cart) is handled by buybox.js.
 */
(function () {
  'use strict';

  var doc = document;
  var mainWrap = doc.querySelector('[data-econ-gallery-main]');
  if (!mainWrap) return;

  doc.addEventListener('click', function (e) {
    var thumb = e.target.closest('[data-econ-thumb]');
    if (!thumb) return;
    e.preventDefault();

    var img = mainWrap.querySelector('img');
    var full = thumb.getAttribute('data-full');
    var srcset = thumb.getAttribute('data-srcset');
    if (img && full) {
      img.src = full;
      if (srcset) { img.srcset = srcset; } else { img.removeAttribute('srcset'); }
    }

    doc.querySelectorAll('[data-econ-thumb]').forEach(function (t) { t.classList.remove('is-active'); });
    thumb.classList.add('is-active');
  });
})();
