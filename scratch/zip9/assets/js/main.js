/* ============================================================
   MAZADI — Main JavaScript
   Navigation, Modals, Animations, UI Interactions
   ============================================================ */

'use strict';

// ─── DOM Ready ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  initNavbar();
  initHamburger();
  initRevealAnimations();
  initCounters();
  initTabs();
  initModals();
  initFavorites();
  initToasts();
  initLangToggle();
  initFormValidation();
  initSupportWidget();
});

// ─── Navbar Scroll Effect ─────────────────────────────────
function initNavbar() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;
  
  const handleScroll = () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
  };
  
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();
}

// ─── Hamburger Mobile Menu ────────────────────────────────
function initHamburger() {
  const hamburger = document.querySelector('.hamburger');
  const mobileMenu = document.querySelector('.mobile-menu');
  const mobileClose = document.querySelector('.mobile-menu-close');
  
  if (!hamburger || !mobileMenu) return;
  
  hamburger.addEventListener('click', () => {
    mobileMenu.classList.add('open');
    document.body.style.overflow = 'hidden';
  });
  
  const closeMenu = () => {
    mobileMenu.classList.remove('open');
    document.body.style.overflow = '';
  };
  
  if (mobileClose) mobileClose.addEventListener('click', closeMenu);
  
  mobileMenu.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', closeMenu);
  });
}

// ─── Reveal on Scroll ─────────────────────────────────────
function initRevealAnimations() {
  const reveals = document.querySelectorAll('.reveal');
  if (!reveals.length) return;
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.classList.add('visible');
        }, i * 80);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
  
  reveals.forEach(el => observer.observe(el));
}

// ─── Animated Counters ────────────────────────────────────
function initCounters() {
  const counters = document.querySelectorAll('[data-counter]');
  if (!counters.length) return;
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });
  
  counters.forEach(el => observer.observe(el));
}

function animateCounter(el) {
  const target = parseInt(el.dataset.counter);
  const suffix = el.dataset.suffix || '';
  const duration = 2000;
  const start = performance.now();
  
  const update = (now) => {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    const current = Math.floor(eased * target);
    el.textContent = current.toLocaleString('ar-SA') + suffix;
    if (progress < 1) requestAnimationFrame(update);
  };
  
  requestAnimationFrame(update);
}

// ─── Tabs ─────────────────────────────────────────────────
function initTabs() {
  const tabGroups = document.querySelectorAll('[data-tabs]');
  
  tabGroups.forEach(group => {
    const tabs = group.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll(`.tab-content[data-tab-group="${group.dataset.tabs}"]`);
    
    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));
        
        tab.classList.add('active');
        const target = document.querySelector(`.tab-content[data-tab-id="${tab.dataset.tab}"]`);
        if (target) target.classList.add('active');
      });
    });
  });
}

// ─── Modals ───────────────────────────────────────────────
function initModals() {
  const triggers = document.querySelectorAll('[data-modal]');
  const overlays = document.querySelectorAll('.modal-overlay');
  
  triggers.forEach(trigger => {
    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      const modalId = trigger.dataset.modal;
      const overlay = document.getElementById(modalId);
      if (overlay) openModal(overlay);
    });
  });
  
  overlays.forEach(overlay => {
    const closeBtn = overlay.querySelector('.modal-close');
    
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) closeModal(overlay);
    });
    
    if (closeBtn) {
      closeBtn.addEventListener('click', () => closeModal(overlay));
    }
  });
  
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      overlays.forEach(overlay => {
        if (overlay.classList.contains('open')) closeModal(overlay);
      });
    }
  });
}

function openModal(overlay) {
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal(overlay) {
  overlay.classList.remove('open');
  document.body.style.overflow = '';
}

// ─── Favorites / Watchlist ────────────────────────────────
function initFavorites() {
  const favBtns = document.querySelectorAll('.auction-card-favorite');
  let favorites = JSON.parse(localStorage.getItem('mazadi_favorites') || '[]');
  
  favBtns.forEach(btn => {
    const id = btn.dataset.id;
    if (favorites.includes(id)) btn.classList.add('active');
    
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      btn.classList.toggle('active');
      
      if (btn.classList.contains('active')) {
        if (!favorites.includes(id)) favorites.push(id);
        showToast('تمت الإضافة إلى قائمة المتابعة', 'success');
      } else {
        favorites = favorites.filter(f => f !== id);
        showToast('تمت الإزالة من قائمة المتابعة', 'info');
      }
      
      localStorage.setItem('mazadi_favorites', JSON.stringify(favorites));
    });
  });
}

// ─── Toast Notifications ──────────────────────────────────
function initToasts() {
  if (!document.querySelector('.toast-container')) {
    const container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
}

function showToast(message, type = 'success', duration = 3500) {
  const container = document.querySelector('.toast-container');
  if (!container) return;
  
  const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
  
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <span style="font-size:1.2rem">${icons[type] || icons.info}</span>
    <span>${message}</span>
  `;
  
  container.appendChild(toast);
  
  setTimeout(() => {
    toast.style.animation = 'slideInToast 0.4s ease reverse';
    setTimeout(() => toast.remove(), 400);
  }, duration);
}

// ─── Language Toggle ──────────────────────────────────────
function initLangToggle() {
  const toggles = document.querySelectorAll('.lang-toggle');
  let currentLang = localStorage.getItem('mazadi_lang') || 'ar';
  
  applyLang(currentLang);
  
  toggles.forEach(toggle => {
    toggle.addEventListener('click', () => {
      currentLang = currentLang === 'ar' ? 'en' : 'ar';
      localStorage.setItem('mazadi_lang', currentLang);
      applyLang(currentLang);
    });
  });
}

function applyLang(lang) {
  document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
  document.body.classList.toggle('lang-en', lang === 'en');
  
  document.querySelectorAll('.lang-toggle').forEach(t => {
    t.textContent = lang === 'ar' ? 'EN' : 'عربي';
  });
  
  // Update visible text elements
  document.querySelectorAll('[data-ar]').forEach(el => {
    el.textContent = lang === 'ar' ? el.dataset.ar : el.dataset.en;
  });
}

// ─── Form Validation ──────────────────────────────────────
function initFormValidation() {
  const forms = document.querySelectorAll('form[data-validate]');
  
  forms.forEach(form => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const isValid = validateForm(form);
      if (isValid) {
        showToast('تم الإرسال بنجاح!', 'success');
      }
    });
  });
}

function validateForm(form) {
  let isValid = true;
  const required = form.querySelectorAll('[required]');
  
  required.forEach(field => {
    const value = field.value.trim();
    const errorEl = field.nextElementSibling;
    
    if (!value) {
      field.style.borderColor = 'var(--danger)';
      isValid = false;
    } else {
      field.style.borderColor = 'var(--success)';
    }
  });
  
  return isValid;
}

// ─── Support Widget ───────────────────────────────────────
function initSupportWidget() {
  const widget = document.querySelector('.support-widget');
  if (!widget) return;
  
  widget.addEventListener('click', () => {
    showToast('سيتصل بك فريق الدعم قريباً!', 'info');
  });
}

// ─── Countdown Timer ──────────────────────────────────────
function createCountdown(targetDate, container) {
  if (!container) return;
  
  const units = container.querySelectorAll('.countdown-unit');
  if (!units.length) return;
  
  const update = () => {
    const now = new Date().getTime();
    const distance = new Date(targetDate).getTime() - now;
    
    if (distance <= 0) {
      container.innerHTML = '<span class="badge badge-ended">انتهى المزاد</span>';
      return;
    }
    
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    const values = [days, hours, minutes, seconds];
    units.forEach((unit, i) => {
      const numEl = unit.querySelector('.countdown-num');
      if (numEl) {
        const val = String(values[i]).padStart(2, '0');
        if (numEl.textContent !== val) {
          numEl.style.transform = 'translateY(-100%)';
          numEl.style.opacity = '0';
          setTimeout(() => {
            numEl.textContent = val;
            numEl.style.transform = 'translateY(0)';
            numEl.style.opacity = '1';
          }, 100);
        }
      }
      // Urgent styling if less than 1 hour
      unit.classList.toggle('urgent', distance < 3600000);
    });
  };
  
  update();
  return setInterval(update, 1000);
}

// ─── Initialize all countdowns on page ───────────────────
function initAllCountdowns() {
  document.querySelectorAll('[data-countdown]').forEach(container => {
    const targetDate = container.dataset.countdown;
    createCountdown(targetDate, container);
  });
}

// Call after DOM ready
document.addEventListener('DOMContentLoaded', initAllCountdowns);

// ─── Gallery ──────────────────────────────────────────────
function initGallery() {
  const gallery = document.querySelector('.photo-gallery-main');
  if (!gallery) return;
  
  const thumbs = document.querySelectorAll('.thumb');
  const mainImg = gallery.querySelector('img');
  const counter = gallery.querySelector('.gallery-counter');
  let currentIndex = 0;
  
  const images = Array.from(thumbs).map(t => t.querySelector('img')?.src);
  
  function goTo(index) {
    currentIndex = (index + images.length) % images.length;
    if (mainImg) mainImg.src = images[currentIndex];
    thumbs.forEach((t, i) => t.classList.toggle('active', i === currentIndex));
    if (counter) counter.textContent = `${currentIndex + 1} / ${images.length}`;
  }
  
  thumbs.forEach((thumb, i) => thumb.addEventListener('click', () => goTo(i)));
  
  const prevBtn = gallery.querySelector('.gallery-nav.prev');
  const nextBtn = gallery.querySelector('.gallery-nav.next');
  
  if (prevBtn) prevBtn.addEventListener('click', () => goTo(currentIndex - 1));
  if (nextBtn) nextBtn.addEventListener('click', () => goTo(currentIndex + 1));
  
  // Touch support
  let startX = 0;
  gallery.addEventListener('touchstart', e => { startX = e.touches[0].clientX; });
  gallery.addEventListener('touchend', e => {
    const diff = startX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) goTo(currentIndex + (diff > 0 ? 1 : -1));
  });
}

document.addEventListener('DOMContentLoaded', initGallery);

// ─── View Toggle (Grid/List) ──────────────────────────────
function initViewToggle() {
  const grid = document.querySelector('.auctions-grid');
  const gridBtn = document.querySelector('[data-view="grid"]');
  const listBtn = document.querySelector('[data-view="list"]');
  
  if (!grid || !gridBtn || !listBtn) return;
  
  gridBtn.addEventListener('click', () => {
    grid.classList.remove('list-view');
    gridBtn.classList.add('active');
    listBtn.classList.remove('active');
    localStorage.setItem('mazadi_view', 'grid');
  });
  
  listBtn.addEventListener('click', () => {
    grid.classList.add('list-view');
    listBtn.classList.add('active');
    gridBtn.classList.remove('active');
    localStorage.setItem('mazadi_view', 'list');
  });
  
  const savedView = localStorage.getItem('mazadi_view');
  if (savedView === 'list') listBtn.click();
}

document.addEventListener('DOMContentLoaded', initViewToggle);

// ─── Price Range Slider ───────────────────────────────────
function initPriceRange() {
  const minSlider = document.getElementById('price-min');
  const maxSlider = document.getElementById('price-max');
  const minDisplay = document.getElementById('price-min-display');
  const maxDisplay = document.getElementById('price-max-display');
  
  if (!minSlider || !maxSlider) return;
  
  const updateDisplay = () => {
    const min = parseInt(minSlider.value);
    const max = parseInt(maxSlider.value);
    
    if (min > max) minSlider.value = max;
    if (max < min) maxSlider.value = min;
    
    if (minDisplay) minDisplay.textContent = parseInt(minSlider.value).toLocaleString('ar-SA');
    if (maxDisplay) maxDisplay.textContent = parseInt(maxSlider.value).toLocaleString('ar-SA');
  };
  
  minSlider.addEventListener('input', updateDisplay);
  maxSlider.addEventListener('input', updateDisplay);
  updateDisplay();
}

document.addEventListener('DOMContentLoaded', initPriceRange);

// ─── Registration Steps ───────────────────────────────────
function initRegistrationSteps() {
  const steps = document.querySelectorAll('.progress-step');
  const stepContents = document.querySelectorAll('.step-panel');
  const nextBtns = document.querySelectorAll('[data-next-step]');
  const prevBtns = document.querySelectorAll('[data-prev-step]');
  let currentStep = 0;
  
  if (!steps.length) return;
  
  function goToStep(index) {
    steps.forEach((s, i) => {
      s.classList.toggle('active', i === index);
      s.classList.toggle('done', i < index);
    });
    
    stepContents.forEach((c, i) => {
      c.classList.toggle('active', i === index);
    });
    
    currentStep = index;
  }
  
  nextBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep < steps.length - 1) goToStep(currentStep + 1);
    });
  });
  
  prevBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep > 0) goToStep(currentStep - 1);
    });
  });
  
  goToStep(0);
}

document.addEventListener('DOMContentLoaded', initRegistrationSteps);

// ─── Smooth Scroll ────────────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

// ─── Active Nav Link ──────────────────────────────────────
(function() {
  const currentPath = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-link').forEach(link => {
    const href = link.getAttribute('href');
    if (href && (href === currentPath || href.includes(currentPath))) {
      link.classList.add('active');
    }
  });
})();

// ─── Export utilities ─────────────────────────────────────
window.mazadi = {
  showToast,
  openModal,
  closeModal,
  createCountdown
};
