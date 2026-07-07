/* ============================================================
   MAZADI — Auction Engine JavaScript
   Live Bidding Simulation, Real-Time Feed, Countdown
   ============================================================ */

'use strict';

// ─── Auction Engine ───────────────────────────────────────
class AuctionEngine {
  constructor(config) {
    this.auctionId = config.auctionId;
    this.currentBid = config.startBid || 50000;
    this.minIncrement = config.minIncrement || 500;
    this.endTime = config.endTime;
    this.bids = config.initialBids || [];
    this.isLive = config.isLive || false;
    this.userBid = 0;
    this.autoBid = false;
    this.autoBidLimit = 0;
    
    this.bidAmountEl = document.getElementById('current-bid-amount');
    this.bidCountEl = document.getElementById('bid-count');
    this.bidHistoryEl = document.getElementById('bid-history');
    this.bidInputEl = document.getElementById('bid-input');
    this.submitBtn = document.getElementById('submit-bid');
    this.countdownEl = document.getElementById('auction-countdown');
    this.nextBidEl = document.getElementById('next-min-bid');
    
    this.init();
  }
  
  init() {
    this.renderBids();
    this.updateDisplay();
    this.bindEvents();
    this.startCountdown();
    
    if (this.isLive) {
      this.startLiveFeed();
    }
  }
  
  bindEvents() {
    if (this.submitBtn) {
      this.submitBtn.addEventListener('click', () => this.placeBid());
    }
    
    if (this.bidInputEl) {
      this.bidInputEl.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') this.placeBid();
      });
    }
    
    // Quick bid buttons
    document.querySelectorAll('.quick-bid-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const amount = parseInt(btn.dataset.amount);
        if (this.bidInputEl) {
          this.bidInputEl.value = this.currentBid + amount;
        }
      });
    });
    
    // Auto-bid toggle
    const autoBidToggle = document.getElementById('auto-bid-toggle');
    if (autoBidToggle) {
      autoBidToggle.addEventListener('change', (e) => {
        this.autoBid = e.target.checked;
        const limitSection = document.getElementById('auto-bid-limit-section');
        if (limitSection) {
          limitSection.classList.toggle('hidden', !this.autoBid);
        }
      });
    }
  }
  
  placeBid(amount) {
    const bidAmount = amount || parseInt(this.bidInputEl?.value);
    const minBid = this.currentBid + this.minIncrement;
    
    if (!bidAmount || bidAmount < minBid) {
      mazadi.showToast(`الحد الأدنى للمزايدة: ${minBid.toLocaleString('ar-SA')} ريال`, 'warning');
      return false;
    }
    
    // Add bid
    const bid = {
      id: Date.now(),
      user: 'أنت',
      amount: bidAmount,
      time: new Date(),
      isUser: true,
      isWinning: true
    };
    
    this.currentBid = bidAmount;
    this.bids.unshift(bid);
    
    // Update previous bids
    this.bids.forEach((b, i) => {
      if (i > 0) b.isWinning = false;
    });
    
    this.renderBids();
    this.updateDisplay();
    this.animateBidUpdate();
    
    if (this.bidInputEl) this.bidInputEl.value = '';
    
    mazadi.showToast('✓ تم تقديم مزايدتك بنجاح!', 'success');
    
    // Simulate competitor bid after delay
    if (this.isLive) {
      const delay = Math.random() * 8000 + 3000;
      setTimeout(() => this.simulateCompetitorBid(), delay);
    }
    
    return true;
  }
  
  simulateCompetitorBid() {
    const competitors = ['م. خالد', 'أبو فهد', 'م. أحمد', 'سعيد ع.', 'المزايد 447', 'ع.م.'];
    const competitor = competitors[Math.floor(Math.random() * competitors.length)];
    const increment = this.minIncrement * (Math.floor(Math.random() * 3) + 1);
    
    const bid = {
      id: Date.now(),
      user: competitor,
      amount: this.currentBid + increment,
      time: new Date(),
      isUser: false,
      isWinning: true
    };
    
    this.currentBid = bid.amount;
    this.bids.forEach(b => { b.isWinning = false; });
    this.bids.unshift(bid);
    
    this.renderBids();
    this.updateDisplay();
    this.animateBidUpdate(false);
    
    mazadi.showToast(`⚡ ${competitor} قدّم مزايدة جديدة!`, 'warning');
    
    // Auto-bid response
    if (this.autoBid && this.autoBidLimit > this.currentBid + this.minIncrement) {
      setTimeout(() => {
        this.placeBid(this.currentBid + this.minIncrement);
      }, 1500);
    }
  }
  
  renderBids() {
    if (!this.bidHistoryEl) return;
    
    const recentBids = this.bids.slice(0, 20);
    this.bidHistoryEl.innerHTML = recentBids.map(bid => `
      <div class="bid-history-item ${bid.isWinning ? 'winning' : ''}">
        <div>
          <div class="bid-history-user">${bid.isUser ? '👤 أنت' : bid.user}</div>
          <div class="bid-history-time">${this.formatTime(bid.time)}</div>
        </div>
        <div class="bid-history-amount">${bid.amount.toLocaleString('ar-SA')} ر.س</div>
      </div>
    `).join('');
    
    if (this.bidCountEl) {
      this.bidCountEl.textContent = this.bids.length;
    }
  }
  
  updateDisplay() {
    if (this.bidAmountEl) {
      this.bidAmountEl.textContent = this.currentBid.toLocaleString('ar-SA');
    }
    
    const nextMin = this.currentBid + this.minIncrement;
    if (this.nextBidEl) {
      this.nextBidEl.textContent = nextMin.toLocaleString('ar-SA');
    }
    
    if (this.bidInputEl && !this.bidInputEl.value) {
      this.bidInputEl.placeholder = nextMin.toLocaleString('ar-SA');
    }
    
    // Update quick bid buttons
    document.querySelectorAll('.quick-bid-btn').forEach(btn => {
      const increment = parseInt(btn.dataset.amount);
      const total = this.currentBid + increment;
      btn.innerHTML = `
        <div style="font-size:0.7rem;color:var(--gray-500)">+${increment.toLocaleString('ar-SA')}</div>
        <div style="font-weight:700">${total.toLocaleString('ar-SA')}</div>
      `;
    });
  }
  
  animateBidUpdate(isUserBid = true) {
    if (!this.bidAmountEl) return;
    
    this.bidAmountEl.style.color = isUserBid ? 'var(--success)' : 'var(--danger)';
    this.bidAmountEl.style.transform = 'scale(1.1)';
    
    setTimeout(() => {
      this.bidAmountEl.style.color = 'var(--gold)';
      this.bidAmountEl.style.transform = 'scale(1)';
    }, 600);
  }
  
  startCountdown() {
    if (!this.countdownEl || !this.endTime) return;
    
    window.mazadi.createCountdown(this.endTime, this.countdownEl);
  }
  
  startLiveFeed() {
    // Simulate periodic bids in the first few minutes
    const delays = [12000, 25000, 40000, 65000];
    delays.forEach(delay => {
      setTimeout(() => {
        if (Math.random() > 0.4) this.simulateCompetitorBid();
      }, delay);
    });
  }
  
  formatTime(date) {
    if (!(date instanceof Date)) date = new Date(date);
    return date.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  }
}

// ─── Sample Data ──────────────────────────────────────────
const sampleBids = [
  { id: 1, user: 'م. خالد', amount: 85000, time: new Date(Date.now() - 120000), isWinning: true },
  { id: 2, user: 'أبو فهد', amount: 84000, time: new Date(Date.now() - 300000), isWinning: false },
  { id: 3, user: 'سعيد ع.', amount: 83000, time: new Date(Date.now() - 450000), isWinning: false },
  { id: 4, user: 'م. أحمد', amount: 82000, time: new Date(Date.now() - 600000), isWinning: false },
  { id: 5, user: 'المزايد 447', amount: 81500, time: new Date(Date.now() - 750000), isWinning: false },
];

// ─── Initialize Auction Engine ────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const auctionRoom = document.querySelector('.auction-room');
  if (!auctionRoom) return;
  
  const endTime = new Date();
  endTime.setHours(endTime.getHours() + 2);
  endTime.setMinutes(endTime.getMinutes() + 35);
  
  window.auctionEngine = new AuctionEngine({
    auctionId: 'AUC-2024-001',
    startBid: 85000,
    minIncrement: 500,
    endTime: endTime,
    initialBids: sampleBids,
    isLive: true
  });
});

// ─── Auction Cards Countdown ──────────────────────────────
function initAuctionCardCountdowns() {
  document.querySelectorAll('.auction-card').forEach((card, index) => {
    const countdown = card.querySelector('[data-countdown]');
    if (!countdown) return;
    
    const hours = Math.floor(Math.random() * 48) + 1;
    const minutes = Math.floor(Math.random() * 60);
    const endTime = new Date();
    endTime.setHours(endTime.getHours() + hours);
    endTime.setMinutes(endTime.getMinutes() + minutes);
    
    countdown.dataset.countdown = endTime.toISOString();
    window.mazadi?.createCountdown(endTime, countdown);
  });
}

document.addEventListener('DOMContentLoaded', initAuctionCardCountdowns);

// ─── Seller Dashboard Charts ──────────────────────────────
function initSellerCharts() {
  const revenueChart = document.getElementById('revenue-chart');
  if (!revenueChart || typeof Chart === 'undefined') return;
  
  new Chart(revenueChart, {
    type: 'line',
    data: {
      labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
      datasets: [{
        label: 'الإيرادات (ر.س)',
        data: [45000, 62000, 58000, 75000, 90000, 115000],
        borderColor: '#D4A843',
        backgroundColor: 'rgba(212,168,67,0.1)',
        borderWidth: 2,
        pointBackgroundColor: '#D4A843',
        tension: 0.4,
        fill: true
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,0.05)' },
          ticks: { color: '#94A3B8' }
        },
        y: {
          grid: { color: 'rgba(255,255,255,0.05)' },
          ticks: { color: '#94A3B8' }
        }
      }
    }
  });
}

document.addEventListener('DOMContentLoaded', initSellerCharts);
