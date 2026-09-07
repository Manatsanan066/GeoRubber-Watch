  <!-- Footer Section (Clean Eco-Minimalist) -->
  <footer style="background: #ffffff; border-top: 1px solid var(--border-subtle); padding: 2rem 1.5rem; margin-top: 3.5rem; font-size: 0.85rem; color: var(--text-muted);">
    <div style="max-width: 1520px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 32px; height: 32px; background: var(--pine-800); border-radius: var(--radius-pill); display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">🌿</div>
        <div>
          <strong style="color: var(--pine-900); font-family: 'Open Sans', 'Google Sans', sans-serif; font-size: 0.95rem;">GeoRubber Watch</strong> — <span style="color: var(--sage-600); font-weight: 600;">แพลตฟอร์มภูมิสารสนเทศอัจฉริยะสำหรับติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา</span>
          <div style="font-size: 0.775rem; margin-top: 2px; color: var(--text-light);">
            สาขาเทคโนโลยีสารสนเทศ คณะวิทยาศาสตร์และเทคโนโลยีอุตสาหกรรม มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี
          </div>
        </div>
      </div>
      <div style="text-align: right; font-size: 0.8rem;">
        <div style="color: var(--text-dark); font-weight: 600;">ผู้จัดทำ: นางสาวมาทินี โรยนรินทร์ & นางสาวมนัสนันท์ อนันตณรงค์</div>
        <div style="color: var(--sage-500); font-weight: 700;">อาจารย์ที่ปรึกษา: รศ.ดร.สุพัตรา พุฒิเนาวรัตน์</div>
      </div>
    </div>
  </footer>

  <!-- QR Code Modal Component (Clean Minimalist with ngrok support) -->
  <div id="qrModal" class="modal-overlay">
    <div class="modal-card" style="max-width: 440px;">
      <div class="modal-header">
        <h3 class="font-heading" style="font-size: 1.2rem; color: var(--pine-900);">📱 EUDR Traceability QR Code</h3>
        <button onclick="App.closeModal('qrModal')" style="background:none; border:none; font-size:1.5rem; color:var(--text-light); cursor:pointer;">&times;</button>
      </div>
      <div class="modal-body" style="text-align: center;">
        <div id="qr-plot-title" style="font-weight: 700; font-size: 1.15rem; margin-bottom: 2px; color: var(--pine-900); font-family: 'Open Sans', 'Google Sans', sans-serif;">-</div>
        <div id="qr-plot-code" style="font-size: 0.85rem; color: var(--sage-500); font-family: monospace; font-weight: 700; margin-bottom: 8px;">-</div>

        <!-- Network / ngrok Status Badge -->
        <div style="display: flex; justify-content: center; margin-bottom: 10px;">
          <div id="qr-network-badge" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; background: #ecfdf5; color: #047857; border: 1px solid #6ee7b7;">
            <span style="width: 8px; height: 8px; border-radius: 9999px; background: #10b981;"></span>
            <span id="qr-network-status">🌐 ตรวจสอบลิงก์สาธารณะ ngrok...</span>
          </div>
        </div>
        
        <div id="qrcode-canvas" style="display: flex; justify-content: center; padding: 16px; background: white; border: 1px solid var(--border-medium); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); margin-bottom: 12px;"></div>
        
        <div style="font-size: 0.75rem; background: var(--sage-50); border: 1px solid var(--sage-200); padding: 8px 12px; border-radius: var(--radius-sm); margin-bottom: 10px; word-break: break-all; text-align: left;">
          <strong style="color: var(--pine-800);">🔗 ลิงก์ที่ฝังใน QR Code:</strong><br>
          <span id="qr-full-url-display" style="font-family: monospace; color: var(--sage-600); font-weight: 700; word-break: break-all;">-</span>
        </div>

        <!-- ngrok Auto-Detect & Custom URL Toolbar -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 10px; margin-bottom: 12px; text-align: left;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span style="font-size: 0.75rem; font-weight: 700; color: #334155;">⚡ ngrok / Public Tunnel:</span>
            <button type="button" onclick="App.detectNgrokUrl(true)" style="font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 6px; background: #00a896; color: white; border: none; cursor: pointer;">
              🔄 ค้นหา ngrok อัตโนมัติ
            </button>
          </div>
          <div style="display: flex; gap: 6px;">
            <input type="url" id="qr-custom-url-input" placeholder="เช่น https://xxxx.ngrok-free.app" style="flex: 1; padding: 4px 8px; font-size: 0.75rem; font-family: monospace; border: 1px solid #cbd5e1; border-radius: 4px;">
            <button type="button" onclick="App.applyCustomUrlInput()" style="padding: 4px 10px; font-size: 0.75rem; font-weight: 700; background: #0e4d4e; color: white; border: none; border-radius: 4px; cursor: pointer;">บันทึก</button>
          </div>
        </div>

        <div style="display: flex; gap: 8px;">
          <a id="qr-url-link" href="#" target="_blank" class="btn btn-primary btn-sm" style="flex: 1;">
            🌐 เปิดหน้า Passport
          </a>
          <button onclick="App.copyCurrentQrUrl()" class="btn btn-outline btn-sm">
            📋 คัดลอกลิงก์
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- JavaScript CDN Libraries -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/6.5.0/turf.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

  <!-- Platform Core Scripts -->
  <script src="assets/js/app.js"></script>
</body>
</html>
