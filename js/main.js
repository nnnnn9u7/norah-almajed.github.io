/**
 * NORAH ALMAJED — PORTFOLIO SITE
 * Vanilla JS — Zero dependencies
 * ============================================================================
 */

// ============================================================================
// MOBILE NAVIGATION
// ============================================================================
const navToggle = document.getElementById('nav-toggle');
const navMenu = document.getElementById('nav-menu');
const navLinks = document.querySelectorAll('.nav-link');
const nav = document.querySelector('.nav');
const heroContent = document.querySelector('.hero-content');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function toggleMenu() {
  const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
  navToggle.setAttribute('aria-expanded', !isExpanded);
  navMenu.setAttribute('data-expanded', !isExpanded);
}

navToggle.addEventListener('click', toggleMenu);

// Add subtle nav depth on scroll for stronger visual feedback
function syncNavState() {
  if (!nav) return;
  nav.classList.toggle('scrolled', window.scrollY > 14);
}

syncNavState();
window.addEventListener('scroll', syncNavState, { passive: true });

// Close menu on link click
navLinks.forEach(link => {
  link.addEventListener('click', () => {
    navToggle.setAttribute('aria-expanded', 'false');
    navMenu.setAttribute('data-expanded', 'false');
  });
});

// Close menu on Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && navToggle.getAttribute('aria-expanded') === 'true') {
    navToggle.setAttribute('aria-expanded', 'false');
    navMenu.setAttribute('data-expanded', 'false');
  }
});

// ============================================================================
// SCROLL REVEAL — IntersectionObserver
// ============================================================================
const revealElements = document.querySelectorAll('section:not(.hero)');

const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.animation = 'sectionReveal 250ms cubic-bezier(0.23, 1, 0.32, 1) forwards';
      revealObserver.unobserve(entry.target);
    }
  });
}, {
  threshold: 0.1,
  rootMargin: '0px 0px -50px 0px'
});

revealElements.forEach(el => {
  revealObserver.observe(el);
});

// ============================================================================
// PROJECT CARDS — Staggered reveal on scroll
// ============================================================================
const projectCards = document.querySelectorAll('.project-card');
const projectDesignButtons = document.querySelectorAll('.project-design-btn');

const cardObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const index = Array.from(projectCards).indexOf(entry.target);
      const delay = 150 + (index * 50);
      entry.target.style.animationDelay = `${delay}ms`;
      cardObserver.unobserve(entry.target);
    }
  });
}, {
  threshold: 0.1,
  rootMargin: '0px 0px -50px 0px'
});

projectCards.forEach(card => {
  cardObserver.observe(card);
});

// Keep design buttons as direct links when placed inside <summary>
projectDesignButtons.forEach(button => {
  button.addEventListener('click', (event) => {
    event.stopPropagation();
  });
});

// Subtle hero parallax to make first fold feel alive
if (!prefersReducedMotion && heroContent) {
  window.addEventListener('scroll', () => {
    const offset = Math.min(window.scrollY * 0.08, 18);
    heroContent.style.transform = `translateY(${offset}px)`;
  }, { passive: true });
}

// ============================================================================
// PROJECTS SECTION — View all button visibility
// ============================================================================
const highlightsSection = document.getElementById('highlights');
const viewAllProjectsWrap = document.getElementById('view-all-projects-wrap');

if (highlightsSection && viewAllProjectsWrap) {
  const sectionTotal = Number(highlightsSection.dataset.totalProjects);
  const visibleProjects = highlightsSection.querySelectorAll('.project-card').length;
  const totalProjects = Number.isFinite(sectionTotal) && sectionTotal > 0 ? sectionTotal : visibleProjects;
  viewAllProjectsWrap.style.display = totalProjects > 4 ? 'flex' : 'none';
}

// ============================================================================
// SMOOTH SCROLL HANDLING (for anchor links)
// ============================================================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const href = this.getAttribute('href');
    if (href === '#') return;

    const target = document.querySelector(href);
    if (!target) return;

    e.preventDefault();

    const offset = 80; // nav height
    const targetTop = target.getBoundingClientRect().top + window.scrollY - offset;

    window.scrollTo({
      top: targetTop,
      behavior: 'smooth'
    });

    // Update URL without jumping
    window.history.pushState(null, null, href);
  });
});

// ============================================================================
// KEYBOARD NAVIGATION SUPPORT
// ============================================================================
// Focus visible management for better keyboard nav
document.addEventListener('keydown', (e) => {
  if (e.key === 'Tab') {
    document.body.classList.add('keyboard-nav');
  }
});

document.addEventListener('mousedown', () => {
  document.body.classList.remove('keyboard-nav');
});

// ============================================================================
// LAZY LOAD IMAGES (future-proofing)
// ============================================================================
if ('IntersectionObserver' in window) {
  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
        }
        observer.unobserve(img);
      }
    });
  }, {
    threshold: 0.1
  });

  document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
  });
}

// ============================================================================
// PERFORMANCE: Defer non-critical tasks
// ============================================================================
if ('requestIdleCallback' in window) {
  requestIdleCallback(() => {
    // Pre-fetch external resources
    const preconnectLinks = document.createElement('link');
    preconnectLinks.rel = 'dns-prefetch';
    preconnectLinks.href = 'https://fonts.googleapis.com';
    document.head.appendChild(preconnectLinks);
  });
}

// ============================================================================
// ANALYTICS PLACEHOLDER (if needed)
// ============================================================================
// Track section views
const analyticsObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      const sectionId = entry.target.id;
      // TODO: Send analytics event if needed
      // console.log(`User viewed section: ${sectionId}`);
    }
  });
}, { threshold: 0.5 });

document.querySelectorAll('section').forEach(section => {
  analyticsObserver.observe(section);
});

// ============================================================================
// UTILITY: GET REMAINING VIEWPORT HEIGHT (for debug)
// ============================================================================
function getViewportHeight() {
  return Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
}

// ============================================================================
// ENSURE ACCESSIBILITY: Hide skip link after use
// ============================================================================
const skipLink = document.querySelector('.skip-link');
if (skipLink) {
  skipLink.addEventListener('blur', () => {
    skipLink.style.top = '-40px';
  });

  skipLink.addEventListener('focus', () => {
    skipLink.style.top = '1rem';
    skipLink.style.left = '1rem';
  });
}

// ============================================================================
// LOG PAGE LOAD TIME (for performance monitoring)
// ============================================================================
if (window.performance && window.performance.timing) {
  window.addEventListener('load', () => {
    const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
    // console.log(`Page loaded in ${loadTime}ms`);
  });
}

// ============================================================================
// POLYFILL: Smooth scroll for older browsers
// ============================================================================
if (!('scrollBehavior' in document.documentElement.style)) {
  window.addEventListener('click', (e) => {
    const target = e.target.closest('a[href^="#"]');
    if (!target) return;

    const href = target.getAttribute('href');
    const element = document.querySelector(href);
    if (!element) return;

    e.preventDefault();
    element.scrollIntoView({ behavior: 'smooth' });
  });
}

console.log('✓ Portfolio initialized');
