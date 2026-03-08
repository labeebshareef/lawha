/* ============================================
   LAWHA HIJABS — WooCommerce Interactions
   Quick View, Mini Cart, AJAX Add to Cart
   No jQuery — Pure Vanilla JS + Fetch API
   ============================================ */

(function () {
  'use strict';

  /* =========================================
     QUICK VIEW MODAL
     ========================================= */
  var QuickView = {
    modal: null,
    overlay: null,
    body: null,
    isOpen: false,

    init: function () {
      this.body = document.querySelector('.lawha-quickview');
      if (!this.body) return;
      this.modal = this.body;
      this.overlay = this.body.querySelector('.lawha-quickview__overlay');
      this.bindEvents();
    },

    bindEvents: function () {
      var self = this;

      // Quick View buttons (event delegation)
      document.addEventListener('click', function (e) {
        var btn = e.target.closest('.lawha-quick-view');
        if (btn) {
          e.preventDefault();
          e.stopPropagation();
          var productId = btn.getAttribute('data-product_id');
          if (productId) {
            self.open(productId);
          }
        }
      });

      // Close button
      var closeBtn = this.modal.querySelector('.lawha-quickview__close');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          self.close();
        });
      }

      // Overlay click
      if (this.overlay) {
        this.overlay.addEventListener('click', function () {
          self.close();
        });
      }

      // Escape key
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && self.isOpen) {
          self.close();
        }
      });
    },

    open: function (productId) {
      var self = this;
      var content = this.modal.querySelector('.lawha-quickview__content');
      var loader = this.modal.querySelector('.lawha-quickview__loader');

      // Show modal with loader
      this.modal.classList.add('is-active');
      this.isOpen = true;
      document.body.style.overflow = 'hidden';
      if (loader) loader.style.display = 'flex';
      if (content) content.innerHTML = '';

      // Fetch product data
      var formData = new FormData();
      formData.append('action', 'lawha_quick_view');
      formData.append('product_id', productId);
      formData.append('nonce', lawhaWC.nonce);

      fetch(lawhaWC.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (loader) loader.style.display = 'none';
          if (data.success && content) {
            content.innerHTML = data.data.html;
            self.initQuantity();
            self.initAddToCart();
          }
        })
        .catch(function () {
          if (loader) loader.style.display = 'none';
          if (content) {
            content.innerHTML = '<p style="text-align:center;padding:var(--space-7);">Unable to load product.</p>';
          }
        });
    },

    close: function () {
      this.modal.classList.remove('is-active');
      this.isOpen = false;
      document.body.style.overflow = '';
    },

    initQuantity: function () {
      var minus = this.modal.querySelector('.lawha-qty-minus');
      var plus = this.modal.querySelector('.lawha-qty-plus');
      var input = this.modal.querySelector('.lawha-qty-input');
      if (!minus || !plus || !input) return;

      minus.addEventListener('click', function () {
        var val = parseInt(input.value, 10) || 1;
        if (val > 1) input.value = val - 1;
      });

      plus.addEventListener('click', function () {
        var val = parseInt(input.value, 10) || 1;
        var max = parseInt(input.getAttribute('max'), 10) || 99;
        if (val < max) input.value = val + 1;
      });
    },

    initAddToCart: function () {
      var self = this;
      var form = this.modal.querySelector('.lawha-quickview-cart-form');
      if (!form) return;

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var productId = form.getAttribute('data-product_id');
        var qty = form.querySelector('.lawha-qty-input');
        var quantity = qty ? parseInt(qty.value, 10) : 1;
        var btn = form.querySelector('.btn');

        if (btn) {
          btn.textContent = 'Adding...';
          btn.disabled = true;
        }

        var formData = new FormData();
        formData.append('action', 'lawha_add_to_cart');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        formData.append('nonce', lawhaWC.nonce);

        fetch(lawhaWC.ajaxUrl, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.success) {
              if (btn) {
                btn.textContent = 'Added ✓';
                btn.style.backgroundColor = 'var(--accent)';
                btn.style.borderColor = 'var(--accent)';
                btn.style.color = 'var(--primary)';
              }
              MiniCart.updateFragments();
              setTimeout(function () {
                self.close();
                MiniCart.open();
              }, 600);
            } else {
              if (btn) {
                btn.textContent = 'Error — Try Again';
                btn.disabled = false;
              }
            }
          })
          .catch(function () {
            if (btn) {
              btn.textContent = 'Error — Try Again';
              btn.disabled = false;
            }
          });
      });
    }
  };


  /* =========================================
     MINI CART DRAWER
     ========================================= */
  var MiniCart = {
    drawer: null,
    overlay: null,
    isOpen: false,

    init: function () {
      this.drawer = document.querySelector('.lawha-minicart');
      if (!this.drawer) return;
      this.overlay = this.drawer.querySelector('.lawha-minicart__overlay');
      this.bindEvents();
    },

    bindEvents: function () {
      var self = this;

      // Cart toggle buttons
      document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.lawha-cart-trigger');
        if (trigger) {
          e.preventDefault();
          self.toggle();
        }
      });

      // Close button
      var closeBtn = this.drawer.querySelector('.lawha-minicart__close');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          self.close();
        });
      }

      // Overlay click
      if (this.overlay) {
        this.overlay.addEventListener('click', function () {
          self.close();
        });
      }

      // Escape key
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && self.isOpen) {
          self.close();
        }
      });

      // Delegate: remove item
      this.drawer.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.lawha-minicart-remove');
        if (removeBtn) {
          e.preventDefault();
          var cartKey = removeBtn.getAttribute('data-cart-key');
          if (cartKey) self.removeItem(cartKey);
        }
      });

      // Delegate: quantity change
      this.drawer.addEventListener('change', function (e) {
        if (e.target.classList.contains('lawha-minicart-qty')) {
          var cartKey = e.target.getAttribute('data-cart-key');
          var qty = parseInt(e.target.value, 10);
          if (cartKey && qty >= 0) self.updateQuantity(cartKey, qty);
        }
      });
    },

    toggle: function () {
      if (this.isOpen) {
        this.close();
      } else {
        this.open();
      }
    },

    open: function () {
      if (!this.drawer) return;
      this.drawer.classList.add('is-active');
      this.isOpen = true;
      document.body.style.overflow = 'hidden';
    },

    close: function () {
      if (!this.drawer) return;
      this.drawer.classList.remove('is-active');
      this.isOpen = false;
      document.body.style.overflow = '';
    },

    updateFragments: function () {
      var formData = new FormData();
      formData.append('action', 'lawha_get_mini_cart');
      formData.append('nonce', lawhaWC.nonce);

      fetch(lawhaWC.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            // Update mini cart body
            var body = document.querySelector('.lawha-minicart__body');
            if (body && data.data.cart_html) {
              body.innerHTML = data.data.cart_html;
            }
            // Update subtotal
            var subtotal = document.querySelector('.lawha-minicart__subtotal-amount');
            if (subtotal && data.data.subtotal) {
              subtotal.innerHTML = data.data.subtotal;
            }
            // Update counter badges
            var counters = document.querySelectorAll('.lawha-cart-count');
            counters.forEach(function (c) {
              c.textContent = data.data.count || '0';
              if (parseInt(data.data.count, 10) > 0) {
                c.style.display = '';
              } else {
                c.style.display = 'none';
              }
            });
            // Show/hide footer
            var footer = document.querySelector('.lawha-minicart__footer');
            var empty = document.querySelector('.lawha-minicart__empty');
            if (parseInt(data.data.count, 10) > 0) {
              if (footer) footer.style.display = '';
              if (empty) empty.style.display = 'none';
            } else {
              if (footer) footer.style.display = 'none';
              if (empty) empty.style.display = '';
            }
          }
        });
    },

    removeItem: function (cartKey) {
      var formData = new FormData();
      formData.append('action', 'lawha_remove_cart_item');
      formData.append('cart_key', cartKey);
      formData.append('nonce', lawhaWC.nonce);

      fetch(lawhaWC.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            MiniCart.updateFragments();
          }
        });
    },

    updateQuantity: function (cartKey, qty) {
      var formData = new FormData();
      formData.append('action', 'lawha_update_cart_qty');
      formData.append('cart_key', cartKey);
      formData.append('quantity', qty);
      formData.append('nonce', lawhaWC.nonce);

      fetch(lawhaWC.ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.success) {
            MiniCart.updateFragments();
          }
        });
    }
  };


  /* =========================================
     AJAX ADD TO CART (Product Pages)
     ========================================= */
  var AjaxCart = {
    init: function () {
      this.bindSingleProduct();
    },

    bindSingleProduct: function () {
      var form = document.querySelector('.product-detail__add-to-cart form.cart');
      if (!form) return;

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        var productId = btn ? btn.value : null;
        var qtyInput = form.querySelector('input[name="quantity"]');
        var quantity = qtyInput ? parseInt(qtyInput.value, 10) : 1;

        if (!productId) return;

        var originalText = btn.innerHTML;
        btn.innerHTML = 'Adding...';
        btn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'lawha_add_to_cart');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        formData.append('nonce', lawhaWC.nonce);

        fetch(lawhaWC.ajaxUrl, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.success) {
              btn.innerHTML = 'Added to Cart ✓';
              btn.style.backgroundColor = 'var(--accent)';
              btn.style.borderColor = 'var(--accent)';
              btn.style.color = 'var(--primary)';
              MiniCart.updateFragments();
              setTimeout(function () {
                MiniCart.open();
                btn.innerHTML = originalText;
                btn.style.backgroundColor = '';
                btn.style.borderColor = '';
                btn.style.color = '';
                btn.disabled = false;
              }, 1200);
            } else {
              btn.innerHTML = 'Error — Try Again';
              btn.disabled = false;
              setTimeout(function () {
                btn.innerHTML = originalText;
              }, 2000);
            }
          })
          .catch(function () {
            btn.innerHTML = originalText;
            btn.disabled = false;
          });
      });
    }
  };


  /* =========================================
     INITIALIZE ON DOM READY
     ========================================= */
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof lawhaWC === 'undefined') return;
    QuickView.init();
    MiniCart.init();
    AjaxCart.init();
  });

  // Expose MiniCart for external use
  window.LawhaMiniCart = MiniCart;

})();
