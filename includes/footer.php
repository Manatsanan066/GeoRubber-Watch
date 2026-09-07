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

  <!-- QR Code Modal Component (Matching Reference UI) -->
  <div id="qrModal" class="modal-overlay">
    <div class="modal-card max-w-[420px] w-full text-center p-6 bg-white rounded-3xl shadow-2xl space-y-3" style="max-width: 420px; border-radius: 24px; padding: 24px; background: #ffffff;">
      <!-- Header with title and circular close button -->
      <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 4px;">
        <h3 id="qr-plot-title" style="font-weight: 800; color: #064e3b; font-size: 1.15rem; margin: 0; text-align: left; font-family: 'Open Sans', 'Google Sans', sans-serif;">แปลงยางขุนทะเล</h3>
        <button type="button" onclick="App.closeModal('qrModal')" style="width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; border: none; color: #64748b; font-weight: bold; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
      </div>

      <!-- Plot Code Subtitle -->
      <div id="qr-plot-code" style="text-align: center; font-weight: 700; color: #00a699; font-size: 15px; font-family: monospace; letter-spacing: 0.02em; margin-bottom: 12px;">
        รหัสแปลง: -
      </div>

      <!-- QR Code Canvas Container with rounded border -->
      <div style="background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 20px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 12px;">
        <div id="qrcode-canvas" style="display: flex; align-items: center; justify-content: center;"></div>
      </div>

      <!-- Traceability Token Box -->
      <div id="qr-token-display" style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 10px 16px; text-align: center; font-family: monospace; font-size: 13px; font-weight: 700; color: #475569; letter-spacing: 0.05em; margin-bottom: 14px; user-select: all;">
        -
      </div>

      <!-- Action Buttons Matching Screenshot -->
      <div style="padding-top: 6px; display: flex; flex-direction: column; gap: 10px;">
        <a id="qr-url-link" href="#" target="_blank" style="width: 100%; box-sizing: border-box; padding: 12px 20px; border-radius: 9999px; background: #00a699; color: #ffffff; font-weight: 700; font-size: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,166,153,0.2);">
          <span>🌐</span> <span>เปิดตรวจสอบหนังสือรับรอง (EUDR Passport)</span>
        </a>
        <button type="button" onclick="App.copyCurrentQrUrl()" style="width: 100%; box-sizing: border-box; padding: 10px 20px; border-radius: 9999px; background: #ffffff; color: #064e3b; font-weight: 700; font-size: 14px; border: 1px solid #cbd5e1; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
          <span>📋</span> <span>คัดลอกลิงก์ตรวจสอบ</span>
        </button>
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
  <script src="assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
