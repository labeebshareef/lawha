/* ============================================
   LAWHA HIJABS — Interactions
   Lazy loading, smooth scroll, scroll progress, hover effects
   ============================================ */

(function() {
  'use strict';

  /**
   * Native lazy loading with IntersectionObserver fallback
   */
  function initLazyLoading() {
    var lazyImages = document.querySelectorAll('img[data-src]');

    if (!lazyImages.length) return;

    if ('IntersectionObserver' in window) {
      var imageObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) {
            var img = entry.target;
            img.src = img.dataset.src;
            if (img.dataset.srcset) {
              img.srcset = img.dataset.srcset;
            }
            img.removeAttribute('data-src');
            img.removeAttribute('data-srcset');
            img.classList.add('loaded');
            imageObserver.unobserve(img);
          }
        });
      }, {
        rootMargin: '200px 0px'
      });

      lazyImages.forEach(function(img) {
        imageObserver.observe(img);
      });
    } else {
      // Fallback: load all images immediately
      lazyImages.forEach(function(img) {
        img.src = img.dataset.src;
        if (img.dataset.srcset) {
          img.srcset = img.dataset.srcset;
        }
        img.removeAttribute('data-src');
        img.removeAttribute('data-srcset');
      });
    }
  }

  /**
   * Smooth scroll for anchor links
   */
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function(link) {
      link.addEventListener('click', function(e) {
        var targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        var target = document.querySelector(targetId);
        if (target) {
          e.preventDefault();
          var offset = 80; // navbar height
          var targetPosition = target.getBoundingClientRect().top + window.scrollY - offset;
          window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
          });
        }
      });
    });
  }

  /**
   * Scroll progress bar
   */
  function initScrollProgress() {
    var progressBar = document.querySelector('.scroll-progress');
    if (!progressBar) return;

    window.addEventListener('scroll', function() {
      var scrollTop = window.scrollY;
      var docHeight = document.documentElement.scrollHeight - window.innerHeight;
      var scrollPercent = (scrollTop / docHeight) * 100;
      progressBar.style.width = scrollPercent + '%';
    }, { passive: true });
  }

  /**
   * Hero section reveal animation
   */
  function initHeroAnimation() {
    var hero = document.querySelector('.hero');
    if (!hero) return;

    // Small delay for page load feel
    setTimeout(function() {
      hero.classList.add('loaded');
    }, 300);
  }

  /**
   * Current year for copyright
   */
  function initCurrentYear() {
    var yearElements = document.querySelectorAll('.current-year');
    var year = new Date().getFullYear();
    yearElements.forEach(function(el) {
      el.textContent = year;
    });
  }

  /**
   * Active nav link highlighting
   */
  function initActiveNavLink() {
    var currentPath = window.location.pathname.split('/').pop() || 'index.html';
    var navLinks = document.querySelectorAll('.navbar__link');
    
    navLinks.forEach(function(link) {
      var href = link.getAttribute('href');
      if (href === currentPath || (currentPath === '' && href === 'index.html')) {
        link.classList.add('active');
      }
    });
  }

  // Export initialization
  window.Interactions = {
    init: function() {
      initLazyLoading();
      initSmoothScroll();
      initScrollProgress();
      initHeroAnimation();
      initCurrentYear();
      initActiveNavLink();
    }
  };
})();
