/**
 * GeoRubber Watch - Application Core JavaScript
 */

const App = {
  currentUser: null,

  // Initialize App
  init() {
    this.checkSession();
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

  // Display QR Code Modal
  showQRCodeModal(token, plotName, plotCode) {
    const modal = document.getElementById('qrModal');
    if (!modal) return;

    document.getElementById('qr-plot-title').textContent = plotName;
    document.getElementById('qr-plot-code').textContent = `รหัสแปลง: ${plotCode}`;
    document.getElementById('qr-token-display').textContent = token;

    const qrContainer = document.getElementById('qrcode-canvas');
    qrContainer.innerHTML = '';

    // Generate Verification URL
    const url = `${window.location.origin}${window.location.pathname.replace(/\/[^/]*$/, '')}/trace.php?token=${encodeURIComponent(token)}`;
    document.getElementById('qr-url-link').href = url;
    document.getElementById('qr-url-link').textContent = url;

    // Use QRCode.js library
    if (typeof QRCode !== 'undefined') {
      new QRCode(qrContainer, {
        text: url,
        width: 220,
        height: 220,
        colorDark: "#064e3b",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
      });
    }

    this.openModal('qrModal');
  },

  // Copy text to clipboard helper
  copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
      this.showToast('คัดลอกลิงก์ไปยังคลิปบอร์ดแล้ว', 'success');
    });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
