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

  <!-- QR Code Modal Component (Clean Minimalist) -->
  <div id="qrModal" class="modal-overlay">
    <div class="modal-card" style="max-width: 440px;">
      <div class="modal-header">
        <h3 class="font-heading" style="font-size: 1.2rem; color: var(--pine-900);">📱 EUDR Traceability QR Code</h3>
        <button onclick="App.closeModal('qrModal')" style="background:none; border:none; font-size:1.5rem; color:var(--text-light); cursor:pointer;">&times;</button>
      </div>
      <div class="modal-body" style="text-align: center;">
        <div id="qr-plot-title" style="font-weight: 700; font-size: 1.15rem; margin-bottom: 2px; color: var(--pine-900); font-family: 'Open Sans', 'Google Sans', sans-serif;">-</div>
        <div id="qr-plot-code" style="font-size: 0.85rem; color: var(--sage-500); font-family: monospace; font-weight: 700; margin-bottom: 15px;">-</div>
        
        <div id="qrcode-canvas" style="display: flex; justify-content: center; padding: 18px; background: white; border: 1px solid var(--border-medium); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); margin-bottom: 15px;"></div>
        
        <div style="font-size: 0.8rem; background: var(--sage-50); border: 1px solid var(--sage-200); padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 15px; word-break: break-all;">
          <strong style="color: var(--pine-800);">รหัสรับรอง (Token):</strong><br>
          <span id="qr-token-display" style="font-family: monospace; color: var(--sage-600); font-weight: 700;">-</span>
        </div>

        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 18px; line-height: 1.5;">
          สแกนด้วยสมาร์ทโฟนเพื่อเข้าสู่หน้าหนังสือรับรองแหล่งกำเนิดและตรวจสอบการไม่บุกรุกป่า (EUDR Passport)
        </div>

        <div style="display: flex; gap: 8px;">
          <a id="qr-url-link" href="#" target="_blank" class="btn btn-primary btn-sm" style="flex: 1;">
            🌐 เปิดหน้า Passport
          </a>
          <button onclick="App.copyToClipboard(document.getElementById('qr-url-link').href)" class="btn btn-outline btn-sm">
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
