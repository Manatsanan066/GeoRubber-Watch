# GeoRubber Watch: Intelligent Monitoring Platform for Sustainable Rubber Plantations
## แพลตฟอร์มภูมิสารสนเทศอัจฉริยะสำหรับติดตามและเฝ้าระวังพื้นที่ปลูกยางพารา

> **โครงงานวิจัยระดับปริญญาตรี**  
> **สาขาวิชาเทคโนโลยีสารสนเทศ คณะวิทยาศาสตร์และเทคโนโลยีอุตสาหกรรม**  
> **มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี**  
> 
> **คณะผู้ดำเนินการวิจัย:**
> 1. นางสาวมาทินี โรยนรินทร์ (รหัสนักศึกษา 6640011044)
> 2. นางสาวมนัสนันท์ อนันตณรงค์ (รหัสนักศึกษา 6640011066)
> 
> **อาจารย์ที่ปรึกษาโครงงาน:** รองศาสตราจารย์ ดร. สุพัตรา พุฒิเนาวรัตน์

---

## 🌟 ฟังก์ชันและความสามารถของแพลตฟอร์ม (Features)

1. **ระบบแผนที่ Web GIS และการวาดขอบเขตแปลง (Interactive Polygon Mapping)**
   - สลับแผนที่ภาพถ่ายดาวเทียมความละเอียดสูง (Satellite Imagery), OpenStreetMap, และแผนที่ภูมิประเทศ
   - เครื่องมือวาดขอบเขตแปลง (Leaflet.draw) สามารถเพิ่ม แก้ไข และลบขอบเขตแปลงหลายเหลี่ยม (Polygon)
   - คำนวณเนื้อที่แปลงอัตโนมัติตามมาตราวัดไทย (**ไร่ - งาน - ตารางวา**) และหน่วยสากล (**เฮกตาร์ / ตารางเมตร**)
   - คำนวณพิกัดจุดกึ่งกลางแปลง (Centroid Latitude/Longitude) อัตโนมัติ

2. **ระบบวิเคราะห์การทับซ้อนเขตป่าสงวนและการประเมินมาตรฐาน EUDR (Spatial Overlay Engine)**
   - วิเคราะห์การทับซ้อนเชิงพื้นที่แบบ Real-time ระหว่างแปลงปลูกยางพารากับแนวเขตป่าสงวนแห่งชาติและป่าคุ้มครอง
   - คำนวณร้อยละการทับซ้อน (% Overlap) และวิเคราะห์ระยะห่าง Buffer Zone (< 500 เมตร)
   - ตรวจสอบเกณฑ์การปลอดการตัดไม้ทำลายป่า (Deforestation-Free) และเกณฑ์ EUDR Cut-off Date (31 ธ.ค. 2020)

3. **ระบบตรวจสอบย้อนกลับด้วย QR Code (EUDR Traceability Passport)**
   - สร้าง QR Code และรหัสรับรอง (Traceability Token) ประจำแปลงและรอบผลผลิต
   - หน้าตรวจสอบย้อนกลับสาธารณะ (`trace.php`): สแกนด้วยสมาร์ทโฟนเพื่อดูแผนที่แปลง พิกัด ข้อมูลเกษตรกร และหนังสือรับรองความสอดคล้องตามมาตรฐาน EUDR ได้ทันทีโดยไม่ต้องเข้าสู่ระบบ
   - รองรับการพิมพ์เอกสาร Due Diligence Statement (DDS)

4. **ระบบบันทึกผลผลิตน้ำยางสดและคุณภาพ (Latex Yield & Production Logbook)**
   - บันทึกน้ำหนักน้ำยางสด (กก.), ปริมาณเนื้อยางแห้ง (% DRC), ราคาขาย, รายได้ และผู้รับซื้อ
   - คำนวณน้ำหนักเนื้อยางแห้งและรายได้รวมโดยอัตโนมัติ

5. **ระบบสนับสนุนการตัดสินใจและแดชบอร์ดสถิติ (DSS Analytics Dashboard)**
   - สรุปตัวชี้วัดสำคัญ (KPIs): จำนวนแปลงรวม, พื้นที่เพาะปลูกรวม, อัตราความสอดคล้อง EUDR (%), ปริมาณผลผลิตรวม
   - กราฟแนวโน้มผลผลิตรายเดือน (Chart.js)
   - กราฟสัดส่วนพันธุ์ยางพารา (RRIM 600, RRIT 251, RRIT 452, BPM 24 ฯลฯ)
   - กราฟโครงสร้างอายุแปลงยางพาราและระยะการเจริญเติบโต
   - ตารางรายการแปลงที่มีความเสี่ยงและต้องเฝ้าระวัง (At-Risk / Forest Overlap List)

6. **ระบบจัดการฐานข้อมูลแบบยืดหยุ่น (Multi-Database Support)**
   - **SQLite (Zero-Config Default)**: ใช้งานได้ทันที 100% โดยไม่ต้องติดตั้งหรือตั้งค่า Database Server เพิ่มเติม
   - **PostgreSQL + PostGIS Extension** (`sql/schema_postgresql.sql`): รองรับคำสั่งเชิงพื้นที่ `ST_Intersects`, `ST_Area`
   - **MySQL / MariaDB** (`sql/schema_mysql.sql`): รองรับการนำเข้าผ่าน XAMPP phpMyAdmin

---

## 🚀 วิธีการติดตั้งและเปิดใช้งานบน XAMPP (Getting Started)

### 1. เปิด Apache ผ่าน XAMPP Control Panel
- ตรวจสอบให้แน่ใจว่า **Apache** ในโปรแกรม XAMPP ทำงานอยู่ (สถานะ Running)

### 2. เข้าใช้งานแพลตฟอร์มผ่านเว็บเบราว์เซอร์
เปิดเบราว์เซอร์แล้วพิมพ์ URL:
```
http://localhost/RB/
```
หรือหากรันผ่าน Built-in PHP Server:
```bash
php -S localhost:8000
```
แล้วเข้าที่ `http://localhost:8000`

### 3. หน้าต่างการทำงานหลัก
- **หน้าแผนที่ Web-GIS**: `http://localhost/RB/index.php`
- **หน้าแดชบอร์ด DSS**: `http://localhost/RB/dashboard.php`
- **หน้าบันทึกผลผลิต**: `http://localhost/RB/yields.php`
- **หน้าส่งออกข้อมูล**: `http://localhost/RB/export_data.php`
- **หน้าตั้งค่าฐานข้อมูล**: `http://localhost/RB/setup.php`
- **หน้า EUDR Passport สาธารณะ**: `http://localhost/RB/trace.php`

---

## 👥 บัญชีผู้ใช้งานทดสอบ (Default Demo Accounts)

| บทบาท (Role) | ชื่อผู้ใช้ (Username) | รหัสผ่าน (Password) | รายละเอียด |
| :--- | :--- | :--- | :--- |
| **ผู้ดูแลระบบ (Admin)** | `admin` | `admin123` | ดร. สุพัตรา พุฒิเนาวรัตน์ (เข้าถึงข้อมูลทุกแปลง) |
| **เกษตรกร (Farmer 1)** | `matinee` | `farmer123` | นางสาวมาทินี โรยนรินทร์ |
| **เกษตรกร (Farmer 2)** | `manatsanan` | `farmer123` | นางสาวมนัสนันท์ อนันตณรงค์ |
| **เกษตรกร (Farmer 3)** | `somchai` | `farmer123` | นายสมชาย ยางเจริญสุข |

*(หมายเหตุ: สามารถคลิกปุ่ม **"สลับบทบาท: Admin / Farmer"** ที่แถบเมนูด้านบนขวาเพื่อสลับดูมุมมองผู้ใช้งานได้ทันที)*

---

## 📁 โครงสร้างโปรเจกต์ (Project Directory Structure)

```
/Applications/XAMPP/xamppfiles/htdocs/RB/
├── config/
│   ├── database.php            # ตัวจัดการเชื่อมต่อฐานข้อมูล (SQLite / PostgreSQL / MySQL)
│   └── seed_data.php           # สคริปต์จำลองพิกัด ม.อ. สุราษฎร์ธานี แปลง และแนวเขตป่าสงวน
├── data/
│   └── georubber.db            # ไฟล์ฐานข้อมูล SQLite พร้อมใช้งานทันที
├── sql/
│   ├── schema_postgresql.sql   # สคริปต์ฐานข้อมูล PostgreSQL + PostGIS (Geometry & Spatial Views)
│   └── schema_mysql.sql        # สคริปต์ฐานข้อมูล MySQL สำหรับ phpMyAdmin
├── api/
│   ├── auth.php                # API จัดการผู้ใช้และการเข้าสู่ระบบ
│   ├── plots.php               # API จัดการแปลงปลูก (CRUD, GeoJSON, คำนวณเนื้อที่)
│   ├── spatial_check.php       # API วิเคราะห์การทับซ้อนแนวเขตป่าสงวนและ EUDR
│   ├── forests.php             # API ดึงชั้นข้อมูลป่าสงวนจำลอง (GeoJSON)
│   ├── yields.php              # API บันทึกและดึงประวัติผลผลิตน้ำยางสด
│   ├── dashboard_stats.php     # API สถิติภาพรวมและข้อมูลกราฟ DSS
│   └── export.php              # API ส่งออกข้อมูล GeoJSON และ CSV
├── assets/
│   ├── css/
│   │   └── style.css           # สไตล์โมเดิร์น สวยงาม รองรับทุกขนาดหน้าจอ
│   └── js/
│       ├── app.js              # State Manager, Toasts, Modals, QR Code Generator
│       ├── map.js              # Leaflet GIS Map, Polygon Drawing, Spatial Check
│       └── charts.js           # ระบบแสดงผลกราฟ DSS Analytics (Chart.js)
├── includes/
│   ├── header.php              # ส่วนหัวของหน้าเว็บและแถบเมนูนำทาง
│   └── footer.php              # ส่วนท้ายของหน้าเว็บและโมดอล QR Code
├── index.php                   # หน้าหลัก: Web-GIS & แปลงปลูกยางพารา
├── dashboard.php               # หน้าแดชบอร์ด DSS วิเคราะห์สถิติผลผลิตและความเสี่ยง EUDR
├── yields.php                  # หน้าระบบบันทึกผลผลิตน้ำยางสดและคุณภาพ DRC
├── trace.php                   # หน้าสาธารณะสแกน QR Code ตรวจสอบย้อนกลับ (EUDR Passport)
├── export_data.php             # หน้าศูนย์ส่งออกชุดข้อมูล GeoJSON และรายงาน CSV
├── setup.php                   # หน้าตรวจสอบสถานะและรีเซ็ตฐานข้อมูล 1-Click
└── README.md                   # เอกสารคู่มือการใช้งานระบบ
```
