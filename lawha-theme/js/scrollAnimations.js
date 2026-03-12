/* ============================================
   LAWHA HIJABS — Scroll Animations
   IntersectionObserver-based scroll triggered animations
   ============================================ */

(function() {
  'use strict';

  /**
   * Initialize scroll animations using IntersectionObserver
   * Elements with classes: anim-fade-up, anim-fade-in, anim-scale-in,
   * anim-slide-left, anim-slide-right, anim-image-reveal, anim-line
   * get class "is-visible" added when they enter the viewport
   */
  function initScrollAnimations() {
    var animatedElements = document.querySelectorAll(
      '.anim-fade-up, .anim-fade-in, .anim-scale-in, .anim-slide-left, .anim-slide-right, .anim-image-reveal, .anim-line'
    );

    if (!animatedElements.length) return;

    var observerOptions = {
      root: null,
      rootMargin: '0px 0px -60px 0px',
      threshold: 0.15
    };

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          // Once animated, stop observing (one-time animation)
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    animatedElements.forEach(function(el) {
      observer.observe(el);
    });
  }

  /**
   * Initialize stagger animations for child elements
   * Parent containers with class "stagger-children" will have
   * their children animated in sequence
   */
  function initStaggerAnimations() {
    var staggerContainers = document.querySelectorAll('.stagger-children');

    if (!staggerContainers.length) return;

    var observerOptions = {
      root: null,
      rootMargin: '0px 0px -40px 0px',
      threshold: 0.1
    };

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          var children = entry.target.children;
          Array.from(children).forEach(function(child, index) {
            child.style.transitionDelay = (index * 100) + 'ms';
            child.classList.add('is-visible');
          });
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    staggerContainers.forEach(function(container) {
      observer.observe(container);
    });
  }

  // Export initialization
  window.ScrollAnimations = {
    init: function() {
      initScrollAnimations();
      initStaggerAnimations();
    }
  };
})();
