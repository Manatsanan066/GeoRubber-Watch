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
  defaultNgrokUrl: null,

  // Initialize App
  init() {
    this.checkSession();
    this.detectNgrokUrl(false);
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
    
    let icon = '<i class="fa-solid fa-circle-info"></i>';
    if (type === 'success') icon = '<i class="fa-solid fa-circle-check"></i>';
    if (type === 'error') icon = '<i class="fa-solid fa-circle-exclamation"></i>';
    if (type === 'warning') icon = '<i class="fa-solid fa-triangle-exclamation"></i>';

    toast.innerHTML = `<span class="toast-icon">${icon}</span> <div>${message}</div>`;
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
      modal.style.display = 'flex';
      modal.style.opacity = '1';
      modal.style.pointerEvents = 'auto';
      document.body.style.overflow = 'hidden';
    }
  },

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('active');
      modal.style.display = 'none';
      modal.style.opacity = '0';
      modal.style.pointerEvents = 'none';
      document.body.style.overflow = '';
    }
  },

  // Synchronous Quick Base URL Resolver (0ms instant response)
  getInstantBaseUrl() {
    const custom = localStorage.getItem('georubber_public_url');
    if (custom && custom.includes('http')) {
      return {
        url: custom,
        isNgrok: custom.includes('ngrok'),
        source: 'custom_url'
      };
    }
    if (window.NGROK_PUBLIC_URL && window.NGROK_PUBLIC_URL.includes('http')) {
      return {
        url: window.NGROK_PUBLIC_URL,
        isNgrok: window.NGROK_PUBLIC_URL.includes('ngrok'),
        source: 'ngrok_window'
      };
    }
    if (this.detectedNgrokUrl && this.detectedNgrokUrl.includes('http')) {
      return {
        url: this.detectedNgrokUrl,
        isNgrok: true,
        source: 'ngrok_detected'
      };
    }
    return {
      url: window.location.origin,
      isNgrok: false,
      source: 'local'
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

    if (titleEl) titleEl.textContent = this.currentPlotName;
    if (codeEl) codeEl.textContent = `รหัสแปลง: ${this.currentPlotCode}`;
    if (tokenEl) tokenEl.textContent = this.currentQRToken;

    // Open Modal Instantly
    this.openModal('qrModal');

    // 1. Render immediately with instant base URL (0ms lag)
    const instantBase = this.getInstantBaseUrl();
    this.buildAndRenderQR(instantBase.url, instantBase.isNgrok);
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

    // Local in-browser verify URL (always works directly for current user session)
    const localVerifyUrl = `${window.location.origin}${basePath}/trace.php?token=${encodeURIComponent(this.currentQRToken)}`;

    // Prioritize ngrok/public URL if available so smartphones can scan across the internet, fallback to local origin
    let cleanBase = (baseUrl && baseUrl.includes('http'))
      ? baseUrl.trim().replace(/\/+$/, '')
      : window.location.origin;
    
    // Prevent duplicate subpath
    let fullUrl;
    if (cleanBase.endsWith(basePath) && basePath !== '') {
      fullUrl = `${cleanBase}/trace.php?token=${encodeURIComponent(this.currentQRToken)}`;
    } else {
      fullUrl = `${cleanBase}${basePath}/trace.php?token=${encodeURIComponent(this.currentQRToken)}`;
    }
    this.currentGeneratedUrl = (isNgrok || (baseUrl && baseUrl.includes('http') && !baseUrl.includes('localhost') && !baseUrl.includes('127.0.0.1'))) ? fullUrl : localVerifyUrl;

    // Update Links & Displays: Browser button ALWAYS opens localVerifyUrl so user can view certificate without network errors!
    const linkEl = document.getElementById('qr-url-link');
    if (linkEl) {
      linkEl.href = localVerifyUrl;
    }

    qrContainer.style.cursor = 'pointer';
    qrContainer.title = 'คลิกเพื่อเปิดตรวจสอบหนังสือรับรอง (EUDR Passport) ในแท็บใหม่';
    qrContainer.onclick = () => window.open(localVerifyUrl, '_blank');

    const tokenEl = document.getElementById('qr-token-display');
    if (tokenEl) {
      tokenEl.textContent = this.currentQRToken;
    }

    // Generate High-Contrast Crisp QR Code in dark pine green matching reference
    try {
      if (typeof QRCode !== 'undefined') {
        new QRCode(qrContainer, {
          text: fullUrl,
          width: 220,
          height: 220,
          colorDark: "#064e3b",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.M
        });
      } else {
        // Fallback to high-speed QR API if JS library is blocked
        const img = document.createElement('img');
        img.src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(fullUrl)}`;
        img.alt = 'QR Code';
        img.className = 'w-[220px] h-[220px] rounded-lg shadow-sm';
        qrContainer.appendChild(img);
      }
    } catch (e) {
      console.warn('QRCode canvas generation fallback:', e);
      const img = document.createElement('img');
      img.src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(fullUrl)}`;
      img.alt = 'QR Code';
      img.className = 'w-[220px] h-[220px] rounded-lg shadow-sm';
      qrContainer.appendChild(img);
    }
  },

  // Auto-detect running ngrok tunnel
  async detectNgrokUrl(interactive = false) {
    if (interactive) {
      this.showToast('กำลังค้นหา ngrok tunnel บนพอร์ต 4040...', 'info');
    }

    try {
      const res = await fetch('api/get_public_url.php');
      const data = await res.json();

      if (data.success && data.public_url && data.is_ngrok && data.source === 'ngrok_api') {
        this.detectedNgrokUrl = data.public_url;
        localStorage.setItem('georubber_public_url', data.public_url);
        const inputEl = document.getElementById('qr-custom-url-input');
        if (inputEl) inputEl.value = data.public_url;

        // Re-render QR Code with ngrok URL
        if (this.currentQRToken) {
          this.buildAndRenderQR(data.public_url, true);
        }

        if (interactive) {
          this.showToast(`เชื่อมต่อ ngrok สำเร็จ: ${data.public_url}`, 'success');
        }
      } else {
        if (interactive) {
          this.showToast('ไม่พบ ngrok ที่กำลังทำงานอยู่ กรุณารันคำสั่ง ngrok http 80 หรือกรอก URL ด้วยตนเอง', 'warning');
        }
      }
    } catch (e) {
      if (interactive) {
        this.showToast('ไม่สามารถเชื่อมต่อ API ตรวจจับ ngrok ได้', 'error');
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
    this.showToast('อัปเดตและสร้าง QR Code ด้วยลิงก์ใหม่เรียบร้อยแล้ว!', 'success');
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
      this.showToast('คัดลอกลิงก์ไปยังคลิปบอร์ดแล้ว', 'success');
    }).catch(() => {
      this.showToast('คัดลอก: ' + text, 'info');
    });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
