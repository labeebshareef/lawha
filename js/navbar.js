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
  }

  // Export initialization
  window.Navbar = {
    init: initNavbar
  };
})();
