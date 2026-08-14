/*!
 * Econur — buy-box (shared: carousel, product cards, single product).
 * Size/weight selection, AJAX Add-to-Cart, and the "Order" buy-now action
 * (add to cart → straight to checkout). Vanilla, deferred, delegated.
 */
(function () {
  'use strict';

  var doc = document;
  var data = window.econurData || {};
  var i18n = data.i18n || {};

  /* ---- Size / weight selection ---- */
  doc.addEventListener('click', function (e) {
    var sizeBtn = e.target.closest('.econ-size');
    if (!sizeBtn) return;
    var box = sizeBtn.closest('[data-econ-buybox]');
    if (!box) return;

    box.querySelectorAll('.econ-size').forEach(function (s) {
      s.classList.remove('is-active');
      s.setAttribute('aria-pressed', 'false');
    });
    sizeBtn.classList.add('is-active');
    sizeBtn.setAttribute('aria-pressed', 'true');

    var price = sizeBtn.getAttribute('data-price');
    var priceEl = box.querySelector('[data-econ-price]');
    if (priceEl && price) priceEl.textContent = price;

    var variationId = sizeBtn.getAttribute('data-variation-id') || '0';
    box.querySelectorAll('[data-econ-add],[data-econ-order]').forEach(function (b) {
      b.setAttribute('data-variation-id', variationId);
    });
  });

  /* ---- Cart-count refresh ---- */
  function updateCartCount(res) {
    if (res && typeof res.count !== 'undefined') {
      doc.querySelectorAll('[data-econ-cart-count]').forEach(function (el) { el.textContent = res.count; });
    } else if (res && res.fragments && res.fragments['span.econ-cart-count']) {
      doc.querySelectorAll('[data-econ-cart-count]').forEach(function (el) { el.outerHTML = res.fragments['span.econ-cart-count']; });
    }
  }

  /* ---- Add to cart (redirect != null => buy-now / Order) ---- */
  function addToCart(productId, variationId, qty, btn, redirect) {
    if (!data.addToCart) { window.location.href = redirect || data.cartUrl || '/cart/'; return; }
    var original = btn.textContent;
    btn.classList.add('is-loading');
    btn.textContent = i18n.adding || 'Adding…';

    var body = 'nonce=' + encodeURIComponent(data.nonce || '') +
      '&product_id=' + encodeURIComponent(productId) +
      '&variation_id=' + encodeURIComponent(variationId || 0) +
      '&quantity=' + encodeURIComponent(qty || 1);

    fetch(data.addToCart, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.success) {
          updateCartCount(res);
          if (redirect) { window.location.href = redirect; return; }
          btn.classList.remove('is-loading');
          btn.classList.add('is-added');
          btn.textContent = i18n.added || 'Added to cart';
          setTimeout(function () { btn.classList.remove('is-added'); btn.textContent = original; }, 1800);
        } else {
          btn.classList.remove('is-loading');
          btn.textContent = (res && res.message) ? res.message : (i18n.error || 'Could not add.');
          setTimeout(function () { btn.textContent = original; }, 2200);
        }
      })
      .catch(function () {
        btn.classList.remove('is-loading');
        btn.textContent = i18n.error || 'Could not add.';
        setTimeout(function () { btn.textContent = original; }, 2200);
      });
  }

  function fire(btn, redirect) {
    var box = btn.closest('[data-econ-buybox]');
    var qtyEl = box ? box.querySelector('[data-econ-qty]') : null;
    var qty = qtyEl ? (parseInt(qtyEl.value, 10) || 1) : 1;
    addToCart(btn.getAttribute('data-product-id'), btn.getAttribute('data-variation-id'), qty, btn, redirect);
  }

  doc.addEventListener('click', function (e) {
    var orderBtn = e.target.closest('[data-econ-order]');
    if (orderBtn && !orderBtn.disabled) {
      e.preventDefault();
      fire(orderBtn, data.checkoutUrl || data.cartUrl || '/checkout/');
      return;
    }
    var addBtn = e.target.closest('[data-econ-add]');
    if (addBtn && !addBtn.disabled) {
      e.preventDefault();
      fire(addBtn, null);
    }
  });

  window.econurAddToCart = addToCart;
})();
