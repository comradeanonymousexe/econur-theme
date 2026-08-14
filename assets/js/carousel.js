/*!
 * Econur — auto-advancing carousel (homepage showcase: featured products + offers).
 * Vanilla, deferred. Auto-play pauses on hover/focus/tab-hidden, is disabled for
 * prefers-reduced-motion, and STOPS for good once the user takes control (arrow,
 * dot, or swipe) — a clear stop mechanism for touch users (WCAG 2.2.2). Supports
 * arrows, dots, and touch/pointer swipe. With no JS all slides stack and remain usable.
 */
(function () {
  'use strict';

  var carousels = document.querySelectorAll('[data-econ-carousel]');
  Array.prototype.forEach.call(carousels, function (root) {
    var track = root.querySelector('[data-econ-carousel-track]');
    if (!track) return;

    var slides = Array.prototype.slice.call(track.children);
    var count = slides.length;
    if (count <= 1) { root.classList.add('econ-carousel--single'); return; }

    var dotsWrap = root.querySelector('[data-econ-carousel-dots]');
    var prev = root.querySelector('[data-econ-carousel-prev]');
    var next = root.querySelector('[data-econ-carousel-next]');
    var interval = parseInt(root.getAttribute('data-interval'), 10) || 6000;
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var index = 0;
    var timer = null;
    var stopped = false; // set once the user interacts — auto-advance never resumes.
    var dots = [];

    function goTo(i) {
      index = (i + count) % count;
      track.style.transform = 'translateX(' + (-index * 100) + '%)';
      dots.forEach(function (d, di) { d.classList.toggle('is-active', di === index); });
    }
    function nextSlide() { goTo(index + 1); }
    function prevSlide() { goTo(index - 1); }

    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    function start() { if (reduce || stopped) return; stop(); timer = setInterval(nextSlide, interval); }
    function engage() { stopped = true; stop(); } // user took control → halt auto-advance.

    if (dotsWrap) {
      for (var i = 0; i < count; i++) {
        var d = document.createElement('button');
        d.type = 'button';
        d.className = 'econ-carousel__dot' + (i === 0 ? ' is-active' : '');
        d.setAttribute('aria-label', 'Go to slide ' + (i + 1));
        (function (idx) { d.addEventListener('click', function () { goTo(idx); engage(); }); })(i);
        dotsWrap.appendChild(d);
        dots.push(d);
      }
    }

    if (next) next.addEventListener('click', function () { nextSlide(); engage(); });
    if (prev) prev.addEventListener('click', function () { prevSlide(); engage(); });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);
    document.addEventListener('visibilitychange', function () { if (document.hidden) { stop(); } else { start(); } });

    var startX = 0, dragging = false;
    track.addEventListener('pointerdown', function (e) { dragging = true; startX = e.clientX; stop(); });
    track.addEventListener('pointerup', function (e) {
      if (!dragging) return;
      dragging = false;
      var dx = e.clientX - startX;
      if (Math.abs(dx) > 45) { if (dx < 0) { nextSlide(); } else { prevSlide(); } engage(); }
      else { start(); }
    });
    track.addEventListener('pointercancel', function () { dragging = false; start(); });

    goTo(0);
    start();
  });
})();
