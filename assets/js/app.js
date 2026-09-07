/**
 * GeoRubber Watch - Application Core JavaScript
 * Supports Dynamic Ngrok Public URL Detection & Scannable EUDR QR Codes
 */

const App = {
  currentUser: null,
  currentQRToken: null,
  currentPlotName: null,
  currentPlotCode: null,
  currentGeneratedUrl: null,
  detectedNgrokUrl: null,

  // Initialize App
  init() {
    this.checkSession();
    this.detectNgrokUrl(false); // Background auto-detect
  },

  // Check logged-in user session
  async checkSession() {
    try {
      const res = await fetch('api/auth.php?action=me');
      const data = await res.json();
      if (data.authenticated) {
        this.currentUser = data.user;
        this.updateUserUI(data.user);
      }
    } catch (e) {
      console.warn('Session check failed:', e);
    }
  },

  // Update Navigation UI based on user role
  updateUserUI(user) {
    const roleElem = document.getElementById('user-role-badge');
    const nameElem = document.getElementById('user-name-display');
    if (roleElem && nameElem) {
      roleElem.textContent = user.role === 'admin' ? 'ผู้ดูแลระบบ (Admin)' : 'เกษตรกร (Farmer)';
      roleElem.className = 'role-badge ' + (user.role === 'admin' ? 'role-admin' : 'role-farmer');
      nameElem.textContent = user.full_name;
    }
  },

  // Switch demo roles dynamically (for review & testing)
  async switchRole(role) {
    try {
      const res = await fetch('api/auth.php?action=switch_demo_user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ role })
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message, 'success');
        setTimeout(() => window.location.reload(), 600);
      }
    } catch (e) {
      this.showToast('เกิดข้อผิดพลาดในการสลับผู้ใช้', 'error');
    }
  },

  // Logout
  async logout() {
    try {
      await fetch('api/auth.php?action=logout');
      this.showToast('ออกจากระบบเรียบร้อย', 'success');
      setTimeout(() => window.location.reload(), 500);
    } catch (e) {
      window.location.reload();
    }
  },

  // Show Toast Notification
  showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '⚠️';
    if (type === 'warning') icon = '🔔';

    toast.innerHTML = `<span>${icon}</span> <div>${message}</div>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  },

  // Modal Helpers
  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('active');
    }
  },

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('active');
    }
  },

  // Synchronous Quick Base URL Resolver (0ms instant response)
  getInstantBaseUrl() {
    const hostname = window.location.hostname;
    const isDirectPublic = (!hostname.includes('localhost') && !hostname.includes('127.0.0.1') && !hostname.match(/^\d+\.\d+\.\d+\.\d+$/));
    
    // 1. Direct public domain (e.g. running on cloud or accessed directly via ngrok)
    if (isDirectPublic) {
      return {
        url: window.location.origin,
        isNgrok: hostname.includes('ngrok'),
        source: 'direct_domain'
      };
    }

    // 2. User-configured custom URL in localStorage
    const savedCustomUrl = localStorage.getItem('georubber_public_url');
    if (savedCustomUrl && savedCustomUrl.startsWith('http')) {
      return {
        url: savedCustomUrl.replace(/\/+$/, ''),
        isNgrok: savedCustomUrl.includes('ngrok'),
        source: 'local_storage'
      };
    }

    // 3. Detected ngrok URL from earlier background scan
    if (this.detectedNgrokUrl && this.detectedNgrokUrl.startsWith('http')) {
      return {
        url: this.detectedNgrokUrl.replace(/\/+$/, ''),
        isNgrok: true,
        source: 'ngrok_api'
      };
    }

    // 4. Default: current origin (localhost) or server LAN IP
    return {
      url: window.location.origin,
      isNgrok: false,
      source: 'local_origin'
    };
  },

  // Display QR Code Modal (Instant sync open + async ngrok verify)
  showQRCodeModal(token, plotName, plotCode) {
    const modal = document.getElementById('qrModal');
    if (!modal) return;

    this.currentQRToken = (token && token !== 'undefined' && token !== 'null') ? token : (plotCode || 'EUDR-SAMPLE');
    this.currentPlotName = plotName || 'แปลงปลูกยางพารา';
    this.currentPlotCode = plotCode || '-';

    const titleEl = document.getElementById('qr-plot-title');
    const codeEl = document.getElementById('qr-plot-code');
    const tokenEl = document.getElementById('qr-token-display');
    const customInputEl = document.getElementById('qr-custom-url-input');

    if (titleEl) titleEl.textContent = this.currentPlotName;
    if (codeEl) codeEl.textContent = `รหัสแปลง: ${this.currentPlotCode}`;
    if (tokenEl) tokenEl.textContent = this.currentQRToken;

    // Open Modal Instantly
    this.openModal('qrModal');

    // 1. Render immediately with instant base URL (0ms lag)
    const instantBase = this.getInstantBaseUrl();
    if (customInputEl) {
      customInputEl.value = (instantBase.source === 'local_storage' || instantBase.isNgrok) ? instantBase.url : '';
    }
    this.buildAndRenderQR(instantBase.url, instantBase.isNgrok);

    // 2. Check for active ngrok tunnel asynchronously without blocking
    if (!instantBase.isNgrok && instantBase.source !== 'direct_domain') {
      this.detectNgrokUrl(false);
    }
  },

  // Construct full URL and draw QR Code
  buildAndRenderQR(baseUrl, isNgrok) {
    const qrContainer = document.getElementById('qrcode-canvas');
    if (!qrContainer) return;
    qrContainer.innerHTML = '';

    // Calculate app subpath (e.g., /RB)
    const pathname = window.location.pathname;
    let basePath = pathname.replace(/\/[^/]*$/, '');
    if (!basePath.startsWith('/')) basePath = '/' + basePath;
    if (basePath === '/') basePath = '';

    let cleanBase = (baseUrl || window.location.origin).replace(/\/+$/, '');
    
    // Prevent duplicate subpath
    let fullUrl;
    if (cleanBase.endsWith(basePath) && basePath !== '') {
      fullUrl = `${cleanBase}/trace.php?token=${encodeURIComponent(this.currentQRToken)}`;
    } else {
      fullUrl = `${cleanBase}${basePath}/trace.php?token=${encodeURIComponent(this.currentQRToken)}`;
    }
    this.currentGeneratedUrl = fullUrl;

    // Update Links & Displays
    const linkEl = document.getElementById('qr-url-link');
    if (linkEl) {
      linkEl.href = fullUrl;
    }

    const urlDisplayEl = document.getElementById('qr-full-url-display');
    if (urlDisplayEl) {
      urlDisplayEl.textContent = fullUrl;
    }

    const badgeEl = document.getElementById('qr-network-badge');
    const badgeTextEl = document.getElementById('qr-network-status');
    if (badgeEl && badgeTextEl) {
      if (isNgrok || (!fullUrl.includes('localhost') && !fullUrl.includes('127.0.0.1') && !fullUrl.includes('192.168.'))) {
        badgeEl.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-300 shadow-2xs';
        badgeEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> <span id="qr-network-status">🟢 ลิงก์สาธารณะ ngrok (สแกนได้จากทุกที่ทั่วโลก)</span>';
      } else {
        badgeEl.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs';
        badgeEl.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-500"></span> <span id="qr-network-status">🟡 เครือข่าย LAN/Localhost (เฉพาะวง Wi-Fi เดียวกัน)</span>';
      }
    }

    // Generate QR with QRCode.js or Fallback
    try {
      if (typeof QRCode !== 'undefined') {
        new QRCode(qrContainer, {
          text: fullUrl,
          width: 200,
          height: 200,
          colorDark: isNgrok ? "#008779" : "#064e3b",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.M
        });
      } else {
        // Fallback to high-speed QR API if JS library is blocked
        const img = document.createElement('img');
        img.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(fullUrl)}`;
        img.alt = 'QR Code';
        img.className = 'w-[200px] h-[200px] rounded-lg shadow-sm';
        qrContainer.appendChild(img);
      }
    } catch (e) {
      console.warn('QRCode canvas generation fallback:', e);
      const img = document.createElement('img');
      img.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(fullUrl)}`;
      img.alt = 'QR Code';
      img.className = 'w-[200px] h-[200px] rounded-lg shadow-sm';
      qrContainer.appendChild(img);
    }
  },

  // Auto-detect running ngrok tunnel
  async detectNgrokUrl(interactive = false) {
    if (interactive) {
      this.showToast('🔍 กำลังค้นหา ngrok tunnel บนพอร์ต 4040...', 'info');
    }

    try {
      const res = await fetch('api/get_public_url.php');
      const data = await res.json();

      if (data.success && data.public_url) {
        this.detectedNgrokUrl = data.public_url;
        localStorage.setItem('georubber_public_url', data.public_url);
        const inputEl = document.getElementById('qr-custom-url-input');
        if (inputEl) inputEl.value = data.public_url;

        // Re-render QR Code with ngrok URL
        if (this.currentQRToken) {
          this.buildAndRenderQR(data.public_url, true);
        }

        if (interactive) {
          this.showToast(`✅ เชื่อมต่อ ngrok สำเร็จ: ${data.public_url}`, 'success');
        }
      } else {
        if (interactive) {
          this.showToast('⚠️ ไม่พบ ngrok ที่กำลังทำงานอยู่ กรุณารันคำสั่ง ngrok http 80 หรือกรอก URL ด้วยตนเอง', 'warning');
        }
      }
    } catch (e) {
      if (interactive) {
        this.showToast('⚠️ ไม่สามารถเชื่อมต่อ API ตรวจจับ ngrok ได้', 'error');
      }
    }
  },

  // Apply custom public URL input
  applyCustomUrlInput() {
    const inputEl = document.getElementById('qr-custom-url-input');
    if (!inputEl) return;

    let customUrl = inputEl.value.trim();
    if (!customUrl) {
      localStorage.removeItem('georubber_public_url');
      this.detectedNgrokUrl = null;
      this.showToast('ล้างการตั้งค่า URL แล้ว กำลังคืนค่าตามค่าเริ่มต้น', 'info');
      const instantBase = this.getInstantBaseUrl();
      this.buildAndRenderQR(instantBase.url, instantBase.isNgrok);
      return;
    }

    // Ensure protocol
    if (!/^https?:\/\//i.test(customUrl)) {
      customUrl = 'https://' + customUrl;
      inputEl.value = customUrl;
    }
    customUrl = customUrl.replace(/\/+$/, '');

    localStorage.setItem('georubber_public_url', customUrl);
    this.detectedNgrokUrl = customUrl;
    
    // Also sync to backend
    fetch('api/get_public_url.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'set_custom_url', url: customUrl })
    }).catch(e => console.warn('Could not sync custom URL to server:', e));

    const isNgrok = customUrl.includes('ngrok');
    this.buildAndRenderQR(customUrl, isNgrok);
    this.showToast('✅ อัปเดตและสร้าง QR Code ด้วยลิงก์ใหม่เรียบร้อยแล้ว!', 'success');
  },

  // Copy current QR URL to clipboard helper
  copyCurrentQrUrl() {
    const url = this.currentGeneratedUrl || (document.getElementById('qr-url-link') ? document.getElementById('qr-url-link').href : '');
    if (url) {
      this.copyToClipboard(url);
    }
  },

  // Copy text to clipboard helper
  copyToClipboard(text) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
      this.showToast('📋 คัดลอกลิงก์ไปยังคลิปบอร์ดแล้ว', 'success');
    }).catch(() => {
      this.showToast('คัดลอก: ' + text, 'info');
    });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
