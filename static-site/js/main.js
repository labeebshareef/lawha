/* ============================================
   LAWHA HIJABS — Main Entry Point
   Initializes all modules on DOMContentLoaded
   ============================================ */

(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    // Initialize navbar (scroll behavior, hamburger menu)
    if (window.Navbar) {
      window.Navbar.init();
    }

    // Initialize interactions (lazy loading, smooth scroll, hero, etc.)
    if (window.Interactions) {
      window.Interactions.init();
    }

    // Initialize scroll animations (IntersectionObserver)
    if (window.ScrollAnimations) {
      window.ScrollAnimations.init();
    }
  });
})();
