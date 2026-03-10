/* ============================================
   LAWHA HIJABS — Navbar Controller
   Sticky navbar, mobile hamburger, scroll-based styling
   ============================================ */

(function() {
  'use strict';

  function initNavbar() {
    var navbar = document.querySelector('.navbar');
    var hamburger = document.querySelector('.navbar__hamburger');
    var mobileOverlay = document.querySelector('.navbar__mobile-overlay');
    var mobileLinks = document.querySelectorAll('.navbar__mobile-overlay .navbar__link');

    if (!navbar) return;

    /* ---- Scroll-based navbar styling ---- */
    var lastScrollY = 0;
    var ticking = false;

    function updateNavbar() {
      var scrollY = window.scrollY;

      if (scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }

      lastScrollY = scrollY;
      ticking = false;
    }

    window.addEventListener('scroll', function() {
      if (!ticking) {
        window.requestAnimationFrame(updateNavbar);
        ticking = true;
      }
    }, { passive: true });

    // Initial check
    updateNavbar();

    /* ---- Mobile hamburger toggle ---- */
    if (hamburger && mobileOverlay) {
      hamburger.addEventListener('click', function() {
        hamburger.classList.toggle('active');
        mobileOverlay.classList.toggle('open');
        document.body.style.overflow = mobileOverlay.classList.contains('open') ? 'hidden' : '';
      });

      // Close mobile nav when a link is clicked
      mobileLinks.forEach(function(link) {
        link.addEventListener('click', function() {
          hamburger.classList.remove('active');
          mobileOverlay.classList.remove('open');
          document.body.style.overflow = '';
        });
      });

      // Close on escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileOverlay.classList.contains('open')) {
          hamburger.classList.remove('active');
          mobileOverlay.classList.remove('open');
          document.body.style.overflow = '';
        }
      });
    }
    /* ---- Account dropdown toggle ---- */
    var navAccount = document.getElementById('navAccount');
    var accountBtn = navAccount ? navAccount.querySelector('.navbar__account-btn') : null;

    if (accountBtn && navAccount) {
      accountBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = navAccount.classList.toggle('is-open');
        accountBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      });

      // Close dropdown on outside click
      document.addEventListener('click', function (e) {
        if (!navAccount.contains(e.target)) {
          navAccount.classList.remove('is-open');
          accountBtn.setAttribute('aria-expanded', 'false');
        }
      });

      // Close on escape key
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && navAccount.classList.contains('is-open')) {
          navAccount.classList.remove('is-open');
          accountBtn.setAttribute('aria-expanded', 'false');
        }
      });
    }
  }

  // Export initialization
  window.Navbar = {
    init: initNavbar
  };
})();
