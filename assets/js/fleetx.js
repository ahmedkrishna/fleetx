// ============================================================
// FleetX — animations.js
// Motion, interactions, timers, toasts, auction engine
// ============================================================

/* ── Scroll Navbar Effect ─────────────────────────────────── */
const navbar = document.getElementById('navbar');
if (navbar) {
  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 50);
  }, { passive: true });
}

/* ── Scroll Reveal Animation ──────────────────────────────── */
function initScrollReveal() {
  const revealEls = document.querySelectorAll('.reveal');
  if (!revealEls.length) return;
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -60px 0px' });
  revealEls.forEach(el => observer.observe(el));
}

/* ── Countdown Timers ─────────────────────────────────────── */
function formatPad(n) { return String(n).padStart(2, '0'); }

function initCountdown(el, endTime) {
  if (!el || !endTime) return;
  const end = new Date(endTime).getTime();

  function update() {
    const now = Date.now();
    const diff = Math.max(0, Math.floor((end - now) / 1000));
    const days  = Math.floor(diff / 86400);
    const hours = Math.floor((diff % 86400) / 3600);
    const mins  = Math.floor((diff % 3600) / 60);
    const secs  = diff % 60;

    const d = el.querySelector('[data-unit="days"]');
    const h = el.querySelector('[data-unit="hours"]');
    const m = el.querySelector('[data-unit="mins"]');
    const s = el.querySelector('[data-unit="secs"]');
    if (d) d.textContent = formatPad(days);
    if (h) h.textContent = formatPad(hours);
    if (m) m.textContent = formatPad(mins);
    if (s) { s.textContent = formatPad(secs); }

    // Urgency when < 1 hour
    if (diff < 3600 && diff > 0) {
      el.classList.add('urgent');
    }
    if (diff <= 0) {
      el.closest('.auction-card')?.classList.add('auction-ended');
      clearInterval(interval);
    }
  }
  update();
  const interval = setInterval(update, 1000);
}

function initAllCountdowns() {
  document.querySelectorAll('[data-countdown]').forEach(el => {
    initCountdown(el, el.dataset.countdown);
  });
}

/* ── Toast Notifications ─────────────────────────────────── */
function showToast(message, type = 'success', duration = 4000) {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const icons = {
    success: '<i class="ph ph-check-circle" style="color:var(--success); font-size: 20px;"></i>',
    error: '<i class="ph ph-x-circle" style="color:var(--danger); font-size: 20px;"></i>',
    info: '<i class="ph ph-info" style="color:var(--info); font-size: 20px;"></i>',
    warning: '<i class="ph ph-warning" style="color:var(--warning); font-size: 20px;"></i>'
  };
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <span style="display:inline-flex; align-items:center;">${icons[type] || icons.success}</span>
    <span style="flex:1;font-size:14px;font-weight:600;color:var(--text-dark);">${message}</span>
    <button onclick="this.closest('.toast').remove()" style="background:none;border:none;cursor:pointer;color:#98A2B3;font-size:18px;line-height:1">×</button>
  `;
  container.appendChild(toast);
  setTimeout(() => {
    toast.classList.add('closing');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

/* ── Filter Tabs ─────────────────────────────────────────── */
function initFilterTabs() {
  document.querySelectorAll('.filter-tabs').forEach(tabGroup => {
    tabGroup.querySelectorAll('.filter-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        tabGroup.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const filter = tab.dataset.filter;
        const grid = document.querySelector('.auctions-grid');
        if (!grid) return;

        grid.querySelectorAll('.auction-card').forEach(card => {
          const type = card.dataset.type;
          const show = filter === 'all' || type === filter;
          card.style.transition = 'all 0.3s ease';
          card.style.opacity = show ? '1' : '0';
          card.style.transform = show ? 'scale(1)' : 'scale(0.95)';
          setTimeout(() => { card.style.display = show ? 'block' : 'none'; }, 300);
          if (show) setTimeout(() => { card.style.opacity='1'; card.style.transform='scale(1)'; }, 10);
        });

        // Update URL param without reload
        const url = new URL(window.location);
        if (filter === 'all') url.searchParams.delete('type');
        else url.searchParams.set('type', filter);
        history.pushState({}, '', url);
      });
    });
  });
}

/* ── Search / Quick Filter ───────────────────────────────── */
function initSearch() {
  const searchInput = document.getElementById('quickSearch');
  if (!searchInput) return;

  let debounceTimer;
  searchInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      const q = searchInput.value.trim().toLowerCase();
      document.querySelectorAll('.auction-card').forEach(card => {
        const text = card.querySelector('.card-title')?.textContent.toLowerCase() ?? '';
        card.style.display = !q || text.includes(q) ? '' : 'none';
      });
    }, 300);
  });
}

/* ── Number Counter Animation ────────────────────────────── */
function animateCounter(el, target, duration = 2000) {
  const start = performance.now();
  const startVal = 0;
  const isDecimal = target % 1 !== 0;

  function update(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3); // ease out cubic
    const current = Math.round(startVal + (target - startVal) * ease);
    el.textContent = current.toLocaleString('ar-SA');
    if (progress < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
}

function initCounters() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseFloat(el.dataset.count);
        animateCounter(el, target);
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('[data-count]').forEach(el => observer.observe(el));
}

function initCountUp() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      if (el.classList.contains('counted')) return;
      el.classList.add('counted');

      const raw = el.getAttribute('data-val') ?? '0';
      const targetNum = parseFloat(raw);
      const hasDecimals = raw.includes('.');
      const duration = 2000;
      let startTime = null;

      function animateCount(timestamp) {
        if (!startTime) startTime = timestamp;
        let progress = timestamp - startTime;
        if (progress > duration) progress = duration;
        const t = progress / duration - 1;
        const current = targetNum * (1 - (t * t * t * t));
        el.textContent = hasDecimals ? current.toFixed(1) : String(Math.floor(current));
        if (progress < duration) {
          requestAnimationFrame(animateCount);
        } else {
          el.textContent = raw;
        }
      }
      requestAnimationFrame(animateCount);
      observer.unobserve(el);
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.count-up').forEach(el => observer.observe(el));
}

/* ── Hero Rotating Text ──────────────────────────────────── */
function initHeroRotator() {
  const el = document.getElementById('heroRotatingText');
  if (!el) return;
  const phrases = el.dataset.phrases?.split('|') ?? [];
  if (!phrases.length) return;
  let i = 0;
  setInterval(() => {
    i = (i + 1) % phrases.length;
    el.style.opacity = '0';
    el.style.transform = 'translateY(10px)';
    setTimeout(() => {
      el.textContent = phrases[i];
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    }, 300);
  }, 3000);
  el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
}

/* ── Auction Gallery ─────────────────────────────────────── */
function initGallery() {
  const thumbs = document.querySelectorAll('.gallery-thumb');
  const mainImg = document.querySelector('.gallery-main img');
  if (!thumbs.length || !mainImg) return;

  thumbs.forEach(thumb => {
    thumb.addEventListener('click', () => {
      thumbs.forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      mainImg.style.opacity = '0';
      mainImg.style.transform = 'scale(0.98)';
      setTimeout(() => {
        mainImg.src = thumb.querySelector('img').src.replace('w=100', 'w=800');
        mainImg.style.opacity = '1';
        mainImg.style.transform = 'scale(1)';
      }, 200);
    });
  });
  mainImg.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
}

/* ── LIVE Auction Engine ─────────────────────────────────── */
let currentBidPrice = 0;
let bidIncrement = 500;
let auctionId = null;

function initLiveAuction(aucId, startPrice, increment) {
  auctionId = aucId;
  currentBidPrice = parseFloat(startPrice);
  bidIncrement = parseFloat(increment) || 500;
  updateBidDisplay();

  // Quick bid buttons — directly submit bid at current price + amount
  document.querySelectorAll('.quick-bid-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const amount = parseFloat(btn.dataset.amount);
      const nextBid = currentBidPrice + amount;
      const inputEl = document.getElementById('bidAmount');
      if (inputEl) inputEl.value = nextBid;
      // Auto-submit immediately
      submitBid();
    });
  });

  // Submit bid
  const submitBtn = document.getElementById('submitBid');
  if (submitBtn) {
    submitBtn.addEventListener('click', submitBid);
    document.getElementById('bidAmount')?.addEventListener('keydown', e => {
      if (e.key === 'Enter') submitBid();
    });
  }

  // Simulate live bids
  simulateLiveBids();
}

function updateBidDisplay() {
  const display = document.getElementById('currentBidDisplay');
  const minBidInfo = document.getElementById('minBidInfo');
  if (display) {
    display.style.transform = 'scale(1.05)';
    display.style.color = '#00C853';
    setTimeout(() => { display.style.transform = 'scale(1)'; }, 300);
    display.textContent = currentBidPrice.toLocaleString('ar-SA') + ' ر.س';
  }
  if (minBidInfo) {
    minBidInfo.textContent = 'الحد الأدنى: ' + (currentBidPrice + bidIncrement).toLocaleString('ar-SA') + ' ر.س';
  }
  // Update quick bid button labels
  document.querySelectorAll('.quick-bid-btn').forEach(btn => {
    const add = parseFloat(btn.dataset.amount);
    btn.textContent = '+' + add.toLocaleString('ar-SA');
  });
}

async function submitBid() {
  const input = document.getElementById('bidAmount');
  const amount = parseFloat(input?.value);
  const minBid = currentBidPrice + bidIncrement;

  if (!amount || amount < minBid) {
    showToast(`الحد الأدنى للمزايدة ${minBid.toLocaleString('ar-SA')} ر.س`, 'error');
    input?.classList.add('error');
    input.style.animation = 'shake 0.4s ease';
    setTimeout(() => { input.classList.remove('error'); input.style.animation = ''; }, 500);
    return;
  }

  const btn = document.getElementById('submitBid');
  btn.textContent = '⏳ جاري الإرسال...';
  btn.disabled = true;

  try {
    const resp = await fetch('/api/bid.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ auction_id: auctionId, amount })
    });
    const data = await resp.json();

    if (data.success) {
      currentBidPrice = amount;
      updateBidDisplay();
      addBidToHistory({ name: 'أنت', amount, time: 'الآن', isYou: true });
      showToast(data.message || '✅ تمت مزايدتك بنجاح!', 'success');
      if (input) input.value = '';
    } else {
      showToast(data.message || 'فشلت المزايدة، حاول مجدداً', 'error');
      if (data.redirect) {
        setTimeout(() => { window.location.href = data.redirect; }, 1500);
      }
    }
  } catch (e) {
    console.error(e);
    showToast('تعذر إرسال المزايدة. تحقق من الاتصال وحاول مجدداً.', 'error');
  }

  btn.textContent = 'زايد الآن';
  btn.disabled = false;
}

function addBidToHistory(bid) {
  const list = document.getElementById('bidHistory');
  if (!list) return;
  const initial = bid.name?.charAt(0) ?? '؟';
  const item = document.createElement('div');
  item.className = `bid-history-item new-bid${bid.isYou ? ' winner' : ''}`;
  item.innerHTML = `
    <div class="bid-avatar">${initial}</div>
    <div>
      <div class="bid-user">${bid.name}</div>
      <div class="bid-time">${bid.time}</div>
    </div>
    <div class="bid-amount">${Number(bid.amount).toLocaleString('ar-SA')} ر.س</div>
  `;
  list.prepend(item);

  // Keep only 10 items
  const items = list.querySelectorAll('.bid-history-item');
  if (items.length > 10) items[items.length - 1].remove();

  // Update count badge
  const badge = document.getElementById('bidCountBadge');
  if (badge) badge.textContent = parseInt(badge.textContent || 0) + 1;
}

const bidderNames = ['م.عبدالله','فهد الرشيدي','ع. العتيبي','سلطان م','محمد ع','خالد الزهراني','عمر السعدي'];
function simulateLiveBids() {
  function scheduleNext() {
    const delay = 8000 + Math.random() * 12000;
    setTimeout(() => {
      const amount = currentBidPrice + bidIncrement * (Math.random() > 0.5 ? 1 : 2);
      currentBidPrice = Math.round(amount / 100) * 100;
      updateBidDisplay();
      const name = bidderNames[Math.floor(Math.random() * bidderNames.length)];
      addBidToHistory({ name, amount: currentBidPrice, time: 'الآن' });
      showToast(`${name} زايد بـ ${currentBidPrice.toLocaleString('ar-SA')} ر.س!`, 'info');
      scheduleNext();
    }, delay);
  }
  scheduleNext();
}

/* ── Card Click Navigation ───────────────────────────────── */
function initCardNavigation() {
  document.querySelectorAll('.auction-card[data-id]').forEach(card => {
    card.addEventListener('click', (e) => {
      // Don't navigate if clicking fav button or CTA button
      if (e.target.closest('.card-fav') || e.target.closest('.card-btn')) return;
      
      // If the card has its own onclick handler, let it handle the navigation
      if (card.hasAttribute('onclick')) return;

      const id = card.dataset.id;
      window.location.href = `/vehicle-details.php?id=${id}`;
    });
    card.style.cursor = 'pointer';
  });

  // CTA buttons
  document.querySelectorAll('.card-btn[data-id]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const id = btn.dataset.id;
      window.location.href = `/vehicle-details.php?id=${id}`;
    });
  });
}

/* ── Forms ───────────────────────────────────────────────── */
function initOTP() {
  const inputs = document.querySelectorAll('.otp-input');
  inputs.forEach((input, i) => {
    input.addEventListener('input', () => {
      if (input.value.length === 1 && i < inputs.length - 1) {
        inputs[i + 1].focus();
      }
      // Auto-submit when all filled
      const allFilled = [...inputs].every(inp => inp.value.length === 1);
      if (allFilled) {
        const code = [...inputs].map(inp => inp.value).join('');
        document.getElementById('otpCode').value = code;
        document.getElementById('otpForm')?.submit();
      }
    });
    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !input.value && i > 0) {
        inputs[i - 1].focus();
      }
    });
    input.addEventListener('paste', e => {
      e.preventDefault();
      const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
      [...pasted].forEach((char, j) => { if (inputs[i+j]) inputs[i+j].value = char; });
      const next = Math.min(i + pasted.length, inputs.length - 1);
      inputs[next].focus();
    });
  });
}

/* ── Navbar Dropdown Touch Support ───────────────────────── */
function initNavDropdown() {
  document.querySelectorAll('.nav-dropdown > a').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
      // Only intercept on touch/small screen
      if (window.innerWidth <= 992) return;
      const dropdown = toggle.nextElementSibling;
      if (!dropdown) return;
      e.preventDefault();
      const isOpen = dropdown.style.display === 'block';
      document.querySelectorAll('.nav-dropdown-content').forEach(d => d.style.display = 'none');
      dropdown.style.display = isOpen ? 'none' : 'block';
    });
  });
  // Close on outside click
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-dropdown')) {
      document.querySelectorAll('.nav-dropdown-content').forEach(d => d.style.display = '');
    }
  });
}

/* ── Swiper Carousels ────────────────────────────────────── */
function initHomeSwiper(selector, key) {
  if (typeof Swiper === 'undefined') return null;
  const el = document.querySelector(selector);
  if (!el) return null;

  const isFeatured = el.classList.contains('fx-auctions-swiper--featured');
  const isMarquee = el.classList.contains('fx-auctions-swiper--marquee');
  if (isMarquee) {
    const wrapper = el.querySelector('.swiper-wrapper');
    if (wrapper) {
      const originals = [...wrapper.querySelectorAll('.swiper-slide')];
      originals.forEach((slide) => wrapper.appendChild(slide.cloneNode(true)));
    }
  }
  let slideCount = el.querySelectorAll('.swiper-slide').length;

  // Featured carousels use fixed-width slides (CSS). With only 3 slides, Swiper
  // loop mode never creates duplicates and autoplay stalls on the last slide.
  if (isFeatured && slideCount >= 3 && slideCount <= 4) {
    const wrapper = el.querySelector('.swiper-wrapper');
    if (wrapper) {
      const originals = [...wrapper.querySelectorAll('.swiper-slide')];
      originals.forEach((slide) => wrapper.appendChild(slide.cloneNode(true)));
      slideCount = wrapper.querySelectorAll('.swiper-slide').length;
    }
  }

  const featuredInitial = slideCount > 1 ? Math.min(1, slideCount - 1) : 0;

  const swiper = isFeatured
    ? new Swiper(el, {
        slidesPerView: 'auto',
        centeredSlides: true,
        spaceBetween: 18,
        loop: slideCount >= 3,
        loopAdditionalSlides: Math.max(2, Math.floor(slideCount / 2)),
        rewind: false,
        initialSlide: featuredInitial,
        speed: 650,
        autoplay: {
          delay: 3600,
          disableOnInteraction: false,
          pauseOnMouseEnter: true,
        },
        grabCursor: true,
        observer: true,
        observeParents: true,
        watchSlidesProgress: true,
        slideToClickedSlide: true,
        pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
        navigation: {
          nextEl: el.querySelector('.swiper-button-next'),
          prevEl: el.querySelector('.swiper-button-prev'),
        },
      })
    : isMarquee
    ? new Swiper(el, {
        slidesPerView: 'auto',
        centeredSlides: true,
        spaceBetween: 28,
        loop: slideCount >= 4,
        loopAdditionalSlides: Math.max(3, Math.floor(slideCount / 2)),
        speed: 9000,
        autoplay: {
          delay: 0,
          disableOnInteraction: false,
          pauseOnMouseEnter: true,
        },
        grabCursor: true,
        allowTouchMove: true,
        observer: true,
        observeParents: true,
        watchSlidesProgress: true,
        pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
        navigation: {
          nextEl: el.querySelector('.swiper-button-next'),
          prevEl: el.querySelector('.swiper-button-prev'),
        },
      })
    : new Swiper(el, {
        grabCursor: true,
        centeredSlides: false,
        loop: slideCount > 3,
        spaceBetween: 28,
        autoplay: {
          delay: 5000,
          disableOnInteraction: false,
        },
        pagination: { el: el.querySelector('.swiper-pagination'), clickable: true },
        navigation: {
          nextEl: el.querySelector('.swiper-button-next'),
          prevEl: el.querySelector('.swiper-button-prev'),
        },
        slidesPerView: 1.1,
        breakpoints: {
          768: { slidesPerView: 2, spaceBetween: 24 },
          1024: { slidesPerView: 3, spaceBetween: 28 },
        },
      });

  if (isFeatured && swiper) {
    requestAnimationFrame(() => {
      if (swiper.params.loop && typeof swiper.slideToLoop === 'function') {
        swiper.slideToLoop(featuredInitial, 0);
      } else {
        swiper.slideTo(featuredInitial, 0);
      }
      if (swiper.autoplay && typeof swiper.autoplay.start === 'function') {
        swiper.autoplay.start();
      }
    });
  }

  if (!window.fxHomeSwipers) window.fxHomeSwipers = {};
  window.fxHomeSwipers[key] = swiper;
  return swiper;
}

/* ── Hero bidding signs (gavel paddle cards → live listings) ── */
function initHeroBiddingSigns() {
  const container = document.getElementById('fxBiddingSigns');
  if (!container || container.dataset.ready === '1') return;

  const bids = Array.isArray(window.FX_HERO_BIDS) && window.FX_HERO_BIDS.length
    ? window.FX_HERO_BIDS
    : [
        { text: 'مزايدة جديدة', car: 'كامري 2023', amount: '٨٥,٠٠٠ ر.س', url: '/event.php?id=1' },
        { text: 'عرض مباشر', car: 'توسان 2022', amount: '٧٢,٥٠٠ ر.س', url: '/auctions.php?type=live' },
        { text: 'مزايدة فورية', car: 'باترول 2021', amount: '١٤٣,٠٠٠ ر.س', url: '/event.php?id=2' },
        { text: 'شراء فوري', car: 'سبورتاج 2022', amount: '٦٨,٠٠٠ ر.س', url: '/auctions.php?type=instant' },
      ];

  const activeTops = [];
  const isMobile = () => window.matchMedia('(max-width: 768px)').matches;

  function pickTop() {
    if (isMobile()) return (42 + Math.random() * 12) + '%';
    for (let attempt = 0; attempt < 12; attempt++) {
      const top = 12 + Math.floor(Math.random() * 52);
      const clash = activeTops.some((t) => Math.abs(t - top) < 14);
      if (!clash) {
        activeTops.push(top);
        if (activeTops.length > 5) activeTops.shift();
        return top + '%';
      }
    }
    return (14 + Math.floor(Math.random() * 48)) + '%';
  }

  function spawnSign() {
    const bid = bids[Math.floor(Math.random() * bids.length)];
    const isLeft = Math.random() > 0.5;
    const sign = document.createElement('a');
    sign.href = bid.url;
    sign.className = 'fx-bid-sign ' + (isLeft ? 'fx-bid-sign--left' : 'fx-bid-sign--right');
    sign.style.top = pickTop();
    sign.setAttribute('aria-label', bid.text + ' على ' + bid.car + ' — ' + bid.amount);
    const board =
      '<div class="fx-bid-sign__board">' +
        '<div class="fx-bid-sign__text">' +
          '<span class="fx-bid-sign__label">' + bid.text + '<br>على ' + bid.car + '</span>' +
          '<strong class="fx-bid-sign__amount">' + bid.amount + '</strong>' +
        '</div>' +
      '</div>';
    const gavel = '<div class="fx-bid-sign__gavel"><i class="ph-fill ph-gavel"></i></div>';
    const stem = '<div class="fx-bid-sign__stem"></div>';
    sign.innerHTML = isLeft ? (stem + gavel + board) : (board + gavel + stem);
    container.appendChild(sign);
    requestAnimationFrame(() => sign.classList.add('is-visible'));
    setTimeout(() => {
      sign.classList.remove('is-visible');
      setTimeout(() => sign.remove(), 600);
    }, 4800);
  }

  setTimeout(spawnSign, 700);
  setTimeout(spawnSign, 2200);
  setInterval(spawnSign, 5000);
  container.dataset.ready = '1';
}

/* ── Why FleetX card tilt on hover / touch ───────────────── */
function initWhyCardMotion() {
  const cards = document.querySelectorAll('.fx-why-card, .fx-why-journey');
  if (!cards.length) return;

  const reset = (card) => {
    card.style.transform = '';
    card.classList.remove('is-tilt-active');
  };

  const applyTilt = (card, clientX, clientY) => {
    const rect = card.getBoundingClientRect();
    const x = (clientX - rect.left) / rect.width - 0.5;
    const y = (clientY - rect.top) / rect.height - 0.5;
    card.classList.add('is-tilt-active');
    card.style.transform = `perspective(900px) rotateY(${x * 10}deg) rotateX(${-y * 10}deg) translateY(-8px) scale(1.02)`;
  };

  cards.forEach((card) => {
    card.addEventListener('mousemove', (e) => applyTilt(card, e.clientX, e.clientY));
    card.addEventListener('mouseleave', () => reset(card));
    card.addEventListener('touchstart', (e) => {
      const touch = e.touches[0];
      if (touch) applyTilt(card, touch.clientX, touch.clientY);
    }, { passive: true });
    card.addEventListener('touchend', () => reset(card), { passive: true });
  });
}

function initSwipers() {
  initHomeSwiper('.live-auctions-swiper', 'live');
  initHomeSwiper('.instant-buy-swiper', 'instant');
}

/* ── Global Favorite Toggle (API & UI) ──────────────── */
window.requireLogin = function(message) {
    if (window.FX_LOGGED_IN) return true;
    const msg = message || window.FX_GUEST_MSG_FAV || 'سجّل الدخول للمتابعة';
    if (typeof showToast === 'function') showToast(msg, 'warning');
    setTimeout(() => { window.location.href = (window.FX_LOGIN_URL || '/login.php') + '?redirect=' + encodeURIComponent(window.location.pathname + window.location.search); }, 600);
    return false;
};

window.toggleFavorite = async function(id, btn) {
    if (!id) return;
    if (!window.requireLogin(window.FX_GUEST_MSG_FAV)) return;
    try {
        const resp = await fetch('/api/toggle_favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const data = await resp.json();
        if (!data.success && data.message && typeof showToast === 'function') {
            showToast(data.message, 'warning');
            return;
        }
        if (data.success) {
            let icon = btn.querySelector('i');
            if (icon) {
                if (data.is_favorite) {
                    icon.classList.remove('ph');
                    icon.classList.add('ph-fill');
                    icon.style.color = 'var(--danger)';
                    btn.classList.add('active');
                    if (typeof showToast === 'function') showToast('تمت الإضافة للمفضلة', 'success');
                } else {
                    icon.classList.remove('ph-fill');
                    icon.classList.add('ph');
                    icon.style.color = '';
                    btn.classList.remove('active');
                    if (typeof showToast === 'function') showToast('تم الإزالة من المفضلة', 'info');
                    if (btn.dataset.removeOnUnfav === '1') {
                        const card = btn.closest('.fx-fav-card, .auction-card');
                        if (card) {
                            card.style.transition = 'opacity 0.3s, transform 0.3s';
                            card.style.opacity = '0';
                            card.style.transform = 'scale(0.95)';
                            setTimeout(() => {
                                card.remove();
                                if (!document.querySelector('.fx-fav-card, .fav-grid .auction-card')) {
                                    location.reload();
                                }
                            }, 300);
                        }
                    }
                }
            }
            if (Array.isArray(window.userFavorites)) {
                const n = parseInt(id, 10);
                if (data.is_favorite) {
                    if (!window.userFavorites.includes(n)) window.userFavorites.push(n);
                } else {
                    window.userFavorites = window.userFavorites.filter(f => parseInt(f, 10) !== n);
                }
            }
        }
    } catch (e) {
        console.error('Favorite toggle failed', e);
    }
};

function initFavorites() {
    // Attach click listeners to all .card-fav elements
    document.querySelectorAll('.card-fav').forEach(btn => {
        // Prevent multiple bindings
        if (btn.dataset.favBound) return;
        btn.dataset.favBound = 'true';
        
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation(); // prevent card click
            const id = btn.dataset.id;
            if (id) toggleFavorite(id, btn);
        });
    });
}

document.addEventListener('DOMContentLoaded', initFavorites);

/* ── Initialize All ──────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initScrollReveal();
  initAllCountdowns();
  if (typeof syncFavoritesUI === 'function') syncFavoritesUI();
  initFilterTabs();
  initSearch();
  initCounters();
  initCountUp();
  initHeroRotator();
  initGallery();
  initCardNavigation();
  initOTP();
  initNavDropdown();
  initSwipers();
  initHeroBiddingSigns();
  initWhyCardMotion();
});




