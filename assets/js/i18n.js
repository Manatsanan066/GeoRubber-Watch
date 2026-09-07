/**
 * GeoRubber Watch • Centralized Bilingual (TH / EN) Language Engine
 * Multi-Page reactive translation module with persistent state in localStorage
 */

const I18N_DICTIONARY = {
  th: {
    // ================= COMMON / NAVBAR / FOOTER =================
    nav_brand: "GeoRubber Watch",
    nav_home: "หน้าแรก",
    nav_gis: "แผนที่ GIS",
    nav_dashboard: "แดชบอร์ด",
    nav_plots: "แปลงปลูก",
    nav_yields: "ผลผลิต",
    nav_contact: "ติดต่อเรา",
    nav_login_title: "เข้าสู่ระบบ",
    nav_logout: "ออกจากระบบ (Logout)",
    nav_logout_confirm: "ต้องการออกจากระบบหรือไม่?",
    nav_menu_label: "เปิดเมนูนำทาง",
    lang_toggle_title: "คลิกเพื่อสลับภาษา TH / EN (Switch Language)",
    
    // User Roles
    role_farmer: "เกษตรกร",
    role_super_admin: "ผู้ดูแลระบบสูงสุด",
    role_forestry_admin: "กรมป่าไม้",
    role_land_admin: "กรมที่ดิน",
    role_raot_admin: "การยางแห่งประเทศไทย",
    role_coop_admin: "สหกรณ์สวนยาง",
    role_rabber_admin: "ผู้ดูแลระบบ",
    role_admin: "ผู้ดูแลระบบ",

    // Common Buttons & Statuses
    btn_search: "ค้นหา",
    btn_filter: "กรองข้อมูล",
    btn_export: "ส่งออกข้อมูล",
    btn_save: "บันทึกข้อมูล",
    btn_cancel: "ยกเลิก",
    btn_close: "ปิด",
    btn_confirm: "ยืนยัน",
    btn_edit: "แก้ไข",
    btn_delete: "ลบ",
    btn_view: "ดูรายละเอียด",
    btn_copy: "คัดลอก",
    btn_copied: "คัดลอกสำเร็จ",
    btn_print: "พิมพ์เอกสาร",
    btn_download_dds: "ดาวน์โหลดหนังสือรับรอง (DDS)",
    lbl_loading: "กำลังโหลด...",
    lbl_view_plot: "ดูแปลง",
    lbl_total_area_colon: "เนื้อที่รวม:",
    lbl_average: "เฉลี่ย",
    
    // Units
    unit_plots: "แปลง",
    unit_rai: "ไร่",
    unit_kg: "กก.",
    unit_baht: "บาท",
    unit_trees: "ต้น",
    unit_percent: "%",
    unit_ha: "ha",

    // Status Badges
    status_compliant: "ผ่านเกณฑ์ EUDR",
    status_under_review: "เฝ้าระวัง Buffer",
    status_non_compliant: "ทับซ้อนเขตป่า",
    status_tapping: "เปิดกรีดแล้ว",
    status_growing: "ยังไม่เปิดกรีด",
    status_safe: "ปลอดภัย",
    status_outside_forest: "ไม่อยู่ในเขตป่าสงวน",
    status_buffer_500m: "แนวกันชน Buffer 500m",
    status_overlap_zone_c: "ทับซ้อน Zone C",

    // Footer
    foot_res_proj: "โครงการวิจัยระบบภูมิสารสนเทศ",
    foot_proj_name: "GeoRubber Watch • สุราษฎร์ธานี",
    foot_title: "ระบบบริการสารสนเทศภูมิศาสตร์เพื่อการตรวจสอบย้อนกลับและประเมินความสอดคล้องตามกฎหมายว่าด้วยสินค้าที่ปลอดจากการตัดไม้ทำลายป่าของสหภาพยุโรป (EUDR)",
    foot_title_en: "GeoRubber Watch: Intelligent Monitoring Platform for Sustainable Rubber Plantations",
    foot_dept: "สาขาวิทยาศาสตร์และเทคโนโลยี คณะศิลปศาสตร์และวิทยาการจัดการ<br>มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี<br><span class=\"text-white/75 text-[14px]\">31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง จ.สุราษฎร์ธานี 84000</span>",
    foot_dev_header: "ข้อมูลผู้พัฒนาและช่องทางติดต่อ",
    foot_dev_sub: "ระบบภูมิสารสนเทศบริการออนไลน์ตลอด 24 ชั่วโมง",
    foot_authors: "👩‍💻 <strong>ผู้จัดทำ:</strong> นางสาวมาทินี โรยนรินทร์ และ นางสาวมนัสนันท์ อนันตณรงค์",
    foot_advisor: "🎓 <strong>อาจารย์ที่ปรึกษา:</strong> รศ.ดร.สุพัตรา พุฒิเนาวรัตน์",
    foot_email: "✉️ <strong>อีเมล:</strong> <a href=\"mailto:6640011044@psu.ac.th\" class=\"hover:text-mezenc-mint underline\">6640011044@psu.ac.th</a>, <a href=\"mailto:6640011066@psu.ac.th\" class=\"hover:text-mezenc-mint underline\">6640011066@psu.ac.th</a>",
    foot_card_hdr: "SURAT THANI FOREST COVERAGE",
    foot_card_stat: "26 ผืนป่าสงวน (Zone C) • 784,618 ไร่",
    foot_card_source: "ฐานข้อมูลแนวเขตป่าเพื่อการอนุรักษ์ กรมป่าไม้",
    foot_copy: "© 2026 GeoRubber Watch • มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี",
    foot_eudr_cert: "EU Regulation (EU) 2023/1115 Zero Deforestation Compliant (EUDR)",

    // ================= INDEX.PHP =================
    idx_hero_tag: "THE FUTURE OF",
    idx_hero_title: "SUSTAINABLE RUBBER",
    idx_hero_sub: "ยกระดับการจัดการสวนยางพาราด้วยเทคโนโลยี GIS และดาวเทียม เพื่อความยั่งยืนและการปฏิบัติตามมาตรฐาน EUDR อย่างครบวงจร",
    idx_search_ph: "ระบุเลขที่โฉนด, น.ส.3ก หรือรหัสแปลงปลูก...",
    idx_search_btn: "ตรวจสอบ",
    idx_search_empty: "กรุณากรอกเลขที่โฉนด น.ส.3ก หรือรหัสแปลงปลูก",
    idx_search_loading: "กำลังตรวจสอบ...",

    idx_c1_title: "จัดการข้อมูลเกษตรกรและแปลงปลูก",
    idx_c1_desc: "จัดเก็บและบริหารจัดการข้อมูลเกษตรกรพร้อมแปลงปลูกยางพาราให้อยู่ในรูปแบบดิจิทัลบนระบบคลาวด์",
    idx_c2_title: "วาดขอบเขตแปลงปลูก",
    idx_c2_desc: "กำหนดและคำนวณขอบเขตแปลงปลูกจริงในรูปแบบ Polygon บนแผนที่ดิจิทัลแบบโต้ตอบ",
    idx_c3_title: "ตรวจสอบการทับซ้อนพื้นที่แปลงปลูก",
    idx_c3_desc: "วิเคราะห์ความถูกต้องเชิงพื้นที่เพื่อตรวจสอบการทับซ้อนของแปลงปลูกกับแนวเขตป่าสงวน",
    idx_c4_title: "บันทึกผลผลิตและสนับสนุนการตัดสินใจ",
    idx_c4_desc: "ระบบบันทึกผลผลิตน้ำยางสดพร้อมแดชบอร์ดวิเคราะห์ข้อมูลเพื่อการบริหารจัดการสวนยาง",
    idx_c5_title: "ตรวจสอบย้อนกลับตามมาตรฐาน EUDR",
    idx_c5_desc: "สร้างกลไกสนับสนุนการตรวจสอบย้อนกลับ (Traceability) ของผลผลิตประจำแปลงผ่านเทคโนโลยี QR Code เพื่อการส่งออก",
    idx_readmore: "อ่านเพิ่มเติม",

    idx_sec3_tag: "การจำแนกแนวเขตป่าสงวนและประเมินพื้นที่เสี่ยงเชิงภูมิสารสนเทศ",
    idx_sec3_heading: "พื้นที่คุ้มครองและการใช้ประโยชน์ที่ดิน<br>จังหวัดสุราษฎร์ธานี",
    idx_sec3_sub: "ศูนย์กลางข้อมูลเชิงพื้นที่เพื่อสร้างความเข้าใจและเฝ้าระวังแนวเขตป่าสงวนแห่งชาติ 26 แห่งในจังหวัดสุราษฎร์ธานี",
    idx_sec3_p1: "ศูนย์กลางข้อมูลเชิงพื้นที่เพื่อสร้างความเข้าใจและเฝ้าระวังแนวเขตป่าสงวนแห่งชาติ 26 แห่งในจังหวัดสุราษฎร์ธานี โดยเชื่อมโยงฐานข้อมูลสารสนเทศจริงร่วมกับแบบจำลองพื้นที่โดยรอบมหาวิทยาลัย เพื่อยกระดับการบริหารจัดการทรัพยากรธรรมชาติอย่างยั่งยืน",
    idx_sec3_p2: "ระบบรองรับทั้งการศึกษาเรียนรู้มิติด้านการอนุรักษ์ และการตรวจสอบพิกัดแปลงปลูกพืชเศรษฐกิจเทียบกับแนวเขตคุ้มครอง ช่วยประเมินและจำแนกโซนความเสี่ยงเพื่อป้องกันปัญหาการทับซ้อนพื้นที่หวงห้ามได้อย่างถูกต้อง",
    idx_sec3_stat1_lbl: "พื้นที่คุ้มครองรวม",
    idx_sec3_stat1_val: "784,618 ไร่",
    idx_sec3_stat2_lbl: "ป่าสงวนแห่งชาติ",
    idx_sec3_stat2_val: "26 ผืนป่า",
    idx_sec3_stat3_lbl: "ระยะกันชน Buffer",
    idx_sec3_stat3_val: "500 เมตร",
    idx_sec3_btn: "เปิดแผนที่ระบบภูมิสารสนเทศ (Full GIS Map) ➔",
    idx_sec3_map_title: "แผนที่แนวเขตป่าสงวนแห่งชาติ จังหวัดสุราษฎร์ธานี",
    idx_sec3_map_badge: "🔴 เขตคุ้มครองเข้มงวด",
    idx_sec3_info_title: "ป่าสงวนแห่งชาติ จ.สุราษฎร์ธานี (แตะหรือเลื่อนเมาส์บนแผนที่เพื่อดูข้อมูล)",
    idx_sec3_info_desc: "ฐานข้อมูลแนวเขตป่าสงวนแห่งชาติ 26 แห่ง (Zone-c) • ปลอดการตัดไม้ทำลายป่า 100%",
    idx_sec3_info_sub: "ครอบคลุมพื้นที่คุ้มครองรวมกว่า 784,618 ไร่",

    idx_sec4_tag: "ขั้นตอนการทำงานของระบบ",
    idx_sec4_heading: "4 ขั้นตอนสู่การรับรองมาตรฐาน EUDR",
    idx_sec4_sub: "คู่มือและขั้นตอนการใช้งานระบบภูมิสารสนเทศสำหรับเกษตรกรและผู้ประกอบการสวนยาง เพื่อการขึ้นทะเบียนและขอรับรองมาตรฐาน EUDR อย่างถูกต้องครบวงจร",
    idx_sec4_btn: "เริ่มต้นใช้งานทันที",
    idx_step1_title: "Step 1: วาดขอบเขตแปลงปลูก",
    idx_step1_desc: "ปักหมุดพิกัด WGS84 และวาดขอบเขตแปลงยางพาราด้วยเครื่องมือ GIS พร้อมคำนวณเนื้อที่ ไร่-งาน-วา อัตโนมัติ",
    idx_step2_title: "Step 2: ตรวจสอบการซ้อนทับพื้นที่แปลงปลูก",
    idx_step2_desc: "วิเคราะห์การทับซ้อนและวัดระยะห่าง Buffer Zone 500 เมตร เทียบกับแนวเขตป่าสงวนแห่งชาติจริงของสุราษฎร์ธานี (Zone-c)",
    idx_step3_title: "Step 3: บันทึกผลผลิต",
    idx_step3_desc: "บันทึกปริมาณน้ำยางสด ราคารับซื้อ และผลผลิตรายเดือน เชื่อมโยงกับรหัสแปลงปลูกเพื่อวิเคราะห์แนวโน้ม",
    idx_step4_title: "Step 4: ตรวจสอบย้อนกลับตามมาตรฐาน EUDR",
    idx_step4_desc: "สร้างเอกสารรับรองดิจิทัล พร้อม QR Code สำหรับผู้ซื้อและเจ้าหน้าที่สแกนตรวจสอบย้อนกลับ (Traceability) 100%",

    idx_sec5_tag: "EUDR KNOWLEDGE BASE",
    idx_sec5_heading: "3 ระดับสถานะความเสี่ยงเชิงพื้นที่",
    idx_sec5_sub: "คู่มือจำแนกแปลงปลูกยางพาราตามเกณฑ์ปลอดการตัดไม้ทำลายป่า (Zero Deforestation) และ พ.ร.บ. ป่าสงวนแห่งชาติ",
    idx_risk_c1_title: "พื้นที่อนุรักษ์ 26 ป่าสงวนแห่งชาติ",
    idx_risk_c1_desc: "แปลงที่ตั้งอยู่ในแนวเขตป่าสงวนแห่งชาติ 26 แห่ง ของสุราษฎร์ธานี (เขตป่าเพื่อการอนุรักษ์: Zone C) หรือพื้นที่ที่มีการแผ้วถางหลัง 31 ธ.ค. 2020 (EU Cut-off Date)",
    idx_risk_c1_btn: "สำรวจ 26 แนวเขตป่าสงวน ➔",
    idx_risk_c2_title: "แนวกันชนประชิดแนวป่าสงวน",
    idx_risk_c2_desc: "แปลงยางพาราที่มีเอกสารสิทธิ์ถูกต้อง แต่ตั้งอยู่ห่างจากแนวเขตป่าสงวนไม่เกิน 500 เมตร ต้องเฝ้าระวังและวิเคราะห์พิกัดไม่ให้ขยายขอบเขตล่วงล้ำแนวป่า",
    idx_risk_c2_btn: "ตรวจสอบระยะห่าง Buffer ➔",
    idx_risk_c3_title: "แปลงผ่านเกณฑ์มาตรฐานสากล",
    idx_risk_c3_desc: "แปลงยางพาราที่มีเอกสารสิทธิ์ถูกต้อง (โฉนด, น.ส.3ก, ส.ป.ก.4-01 ฯลฯ) อยู่นอกแนวป่าสงวน 100% และปลูกก่อนปี 2020 สามารถออกหนังสือรับรอง EUDR ได้ทันที",
    idx_risk_c3_btn: "ออกเอกสาร EUDR Passport ➔",

    // Deed Modal
    deed_lbl_farmer: "👨‍🌾 เจ้าของแปลง / เกษตรกร:",
    deed_lbl_doc: "📄 ประเภทเอกสารสิทธิ์:",
    deed_lbl_loc: "🗺️ ที่ตั้งแปลง:",
    deed_lbl_area: "📐 เนื้อที่คำนวณ:",
    deed_lbl_clone: "🌳 พันธุ์ยางพารา / สถานะ:",
    deed_lbl_dist: "🌲 ระยะห่างป่าสงวนที่ใกล้ที่สุด:",
    deed_lbl_checklist: "การประเมินความสอดคล้องตามมาตรฐาน EUDR:",
    deed_chk1: "พิกัด Polygon WGS84 บันทึกบน Supabase Cloud ครบถ้วน",
    deed_chk2: "ปลอดการตัดไม้ทำลายป่าหลัง 31 ธ.ค. 2020",
    deed_chk3: "เอกสารสิทธิ์ถูกต้อง สามารถออก EUDR Passport ได้ทันที",
    deed_notfound_title: "ไม่พบข้อมูลในฐานข้อมูล",
    deed_notfound_desc: "ระบบตรวจสอบกับฐานข้อมูล Supabase Cloud แล้ว ไม่พบรหัสแปลงปลูกหรือเลขที่เอกสารสิทธิ์นี้",
    deed_hint_hdr: "💡 <strong>คำแนะนำในการค้นหา:</strong>",
    deed_hint_1: "ตรวจสอบตัวสะกดหรือขีดคั่น เช่น <code class=\"bg-amber-100 px-1 rounded\">RB-ST-2026-006</code> หรือ <code class=\"bg-amber-100 px-1 rounded\">1234-5678</code>",
    deed_hint_2: "ลองค้นหาด้วย <strong>ชื่อแปลง</strong> หรือ <strong>ชื่อเกษตรกร</strong>",
    deed_hint_3: "หากยังไม่ได้ลงทะเบียน สามารถเข้าสู่ระบบเพื่อวาดแปลงปลูกใหม่ได้ทันที",
    deed_btn_close: "ปิดหน้าต่าง",
    deed_btn_map: "เปิดดูบนแผนที่ GIS ➔",

    // ================= OVERVIEW.PHP =================
    ov_hero_tag: "🌲 WEB-GIS FOREST CONSERVATION & LAND BOUNDARIES",
    ov_hero_title: "แผนที่ภูมิสารสนเทศป่าสงวนแห่งชาติ 26 แห่ง",
    ov_hero_sub: "ศูนย์กลางข้อมูลเชิงพื้นที่แสดงแนวเขตป่าสงวนแห่งชาติ จ.สุราษฎร์ธานี (Zone C) ระยะกันชน Buffer Zone 500 ม. และพิกัดแปลงปลูกยางพาราเพื่อการเฝ้าระวังอย่างยั่งยืน",
    ov_search_ph: "ค้นหาชื่อป่าสงวน, รหัสป่า (เช่น R1.001) หรืออำเภอ...",
    ov_btn_zoom_all: "มุมมองภาพรวมทั้งจังหวัด",
    ov_btn_reset: "กลับจุดเริ่มต้น",
    ov_btn_check_plot: "ตรวจสอบแปลงปลูก",
    ov_layer_panel: "แผงควบคุมแผนที่",
    ov_layer_panel_sub: "Layer Control & Tools",
    ov_lbl_search_forest: "ค้นหาป่าสงวนแห่งชาติ",
    ov_opt_select_forest: "เลือกพื้นที่เขตป่าสงวนแห่งชาติ",
    ov_btn_pin_tool: "ปักหมุดตรวจพิกัด",
    ov_btn_gps_locate: "ตำแหน่งฉัน (GPS)",
    ov_lbl_basemap: "แผนที่ฐาน (Basemap)",
    ov_lbl_layers: "เปิด/ปิดชั้นข้อมูล",
    ov_lbl_eudr_legend: "สถานะความสอดคล้อง EUDR",
    ov_psu_campus: "ม.อ. สุราษฎร์ธานี",
    ov_layer_satellite: "ภาพถ่ายดาวเทียม (Satellite Imagery)",
    ov_layer_osm: "แผนที่ถนน OpenStreetMap",
    ov_layer_forest: "แนวเขตป่าสงวน 26 แห่ง (Zone C)",
    ov_layer_buffer: "แนวกันชน 500 เมตร (Buffer Zone)",
    ov_layer_plots: "แปลงปลูกยางพารา (Rubber Plots)",
    ov_list_title: "รายชื่อ 26 ป่าสงวนแห่งชาติ จ.สุราษฎร์ธานี",
    ov_total_reserves: "26 ป่าสงวน",
    ov_total_area: "784,618.00 ไร่",
    ov_total_districts: "19 อำเภอ",
    ov_legend_forest: "แนวเขตป่าสงวนแห่งชาติ (Zone C)",
    ov_legend_buffer: "แนวกันชน (Buffer Zone 500 ม.)",
    ov_legend_rubber: "แปลงยางพาราในระบบ",

    // ================= DASHBOARD.PHP =================
    db_hero_tag: "DECISION SUPPORT SYSTEM (DSS) • SURAT THANI",
    db_hero_title: "แดชบอร์ดวิเคราะห์พื้นที่ปลูกและสถานะความสอดคล้อง",
    db_hero_title_farmer: "แดชบอร์ดสรุปข้อมูลแปลงปลูกและผลผลิตของคุณ",
    db_hero_sub: "ติดตามภาพรวมพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี และจำแนกสถานะแปลงผ่านเกณฑ์ เฝ้าระวัง และทับซ้อนเขตป่าสงวนแห่งชาติ",
    db_hero_sub_farmer: "ติดตามภาพรวมแปลงปลูก สถิติผลผลิตน้ำยางสด รายได้สะสม และตรวจสอบความสอดคล้องตามมาตรฐาน EUDR ของคุณ",
    
    db_card_my_plots: "แปลงปลูกของฉันทั้งหมด",
    db_card_total_plots: "แปลงปลูกทั้งหมดในระบบ",
    db_card_total_area: "เนื้อที่ปลูกยางพารารวม",
    db_card_total_area_all: "พื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี รวมทั้งหมด",
    db_card_compliant: "แปลงที่ผ่านเกณฑ์ (ปลอดภัย)",
    db_card_compliant_sub: "ปลอดการตัดไม้ทำลายป่า",
    db_card_review: "แปลงที่ควรเฝ้าระวัง",
    db_card_review_sub: "แนวกันชน Buffer 500m",
    db_card_non_compliant: "แปลงที่ซ้อนทับเขตป่าสงวน",
    db_card_non_compliant_sub: "ทับซ้อน Zone C",
    db_card_monthly_yield: "ผลผลิตน้ำยางสดสะสม",
    db_card_est_income: "รายได้สะสมรวม",
    db_card_eudr_status: "สถานะความสอดคล้อง EUDR",
    db_card_tapping_rate: "อัตราเปิดกรีดแล้ว",

    db_farmer_chart_yield: "📈 แนวโน้มผลผลิตน้ำยางสด (Latex Yield Trend)",
    db_farmer_chart_yield_sub: "ปริมาณน้ำยางสด (กก.) และเปอร์เซ็นต์เนื้อยางแห้ง DRC (%) ตามรอบการกรีด",
    db_farmer_chart_revenue: "💵 แนวโน้มราคารับซื้อและรายได้รวม (Price & Revenue Trend)",
    db_farmer_chart_revenue_sub: "ราคารับซื้อน้ำยางสด (บาท/กก.) และรายได้รวมต่อรอบการเก็บเกี่ยว (บาท)",
    db_badge_30_rounds: "30 รอบล่าสุด",
    db_badge_revenue_stats: "สถิติรายรับ",

    db_farmer_table_title: "📋 รายการแปลงปลูกของฉัน (My Rubber Plantations)",
    db_farmer_table_sub: "สรุปข้อมูลแปลงปลูก พันธุ์ยาง เนื้อที่ และผลการประเมินความสอดคล้องตามมาตรฐาน EUDR",
    db_btn_log_yield: "บันทึกผลผลิต",
    db_btn_add_plot: "เพิ่มแปลงปลูก",
    db_no_plots_farmer: "ยังไม่มีข้อมูลแปลงปลูกของคุณในระบบ",

    db_sec_charts_title: "การวิเคราะห์เชิงสถิติและการกระจายตัวของแปลงปลูก",
    db_status_ratio_title: "📊 สัดส่วนการจำแนกสถานะพื้นที่ปลูกยางพารา จ.สุราษฎร์ธานี",
    db_status_ratio_sub: "เปรียบเทียบสัดส่วนเนื้อที่และแปลงปลูกตามเกณฑ์การตรวจสอบกับแนวเขตป่าสงวนแห่งชาติ 26 แห่ง",
    db_filter_all: "ทั้งหมด",
    db_filter_compliant: "🟢 ผ่านเกณฑ์",
    db_filter_review: "🟡 เฝ้าระวัง",
    db_filter_non_compliant: "🔴 ซ้อนทับป่า",
    
    db_progress_green_title: "🟢 แปลงที่ผ่านเกณฑ์ (ปลอดภัย 100%)",
    db_progress_green_desc: "อยู่นอกแนวเขตป่าสงวนแห่งชาติและแนวกันชนทุกผืน",
    db_progress_yellow_title: "🟡 แปลงที่ควรเฝ้าระวัง (Buffer Zone 500m)",
    db_progress_yellow_desc: "ห่างจากแนวเขตป่าสงวนน้อยกว่า 500 เมตร ต้องติดตามพิกัดขอบเขต",
    db_progress_red_title: "🔴 แปลงที่ซ้อนทับพื้นที่เขตป่าสงวน",
    db_progress_red_desc: "มีพิกัด Polygon ซ้อนทับแนวเขตป่าสงวนแห่งชาติสุราษฎร์ธานี (Zone C)",

    db_chart_clone_title: "🧬 สัดส่วนสายพันธุ์ยางพารา (Clone Distribution)",
    db_chart_clone_sub: "การกระจายตัวของพันธุ์ยางพาราในพื้นที่ จ.สุราษฎร์ธานี",
    db_chart_monthly_title: "📅 แนวโน้มผลผลิตและรายได้รายเดือน (Monthly Provincial Trends)",
    db_chart_monthly_sub: "ปริมาณน้ำยางสด (กก.) และมูลค่ารวมรายเดือนทั้งจังหวัด",
    db_badge_provincial: "ภาพรวมจังหวัด",
    db_badge_monthly: "รายเดือน",

    db_table_title: "📋 ทะเบียนแปลงปลูกยางพารา จ.สุราษฎร์ธานี",
    db_table_sub: "แสดงรายละเอียดแปลงปลูก เกษตรกรผู้ถือครอง เนื้อที่ และผลการประเมินความสอดคล้องตามมาตรฐาน",
    db_search_ph: "ค้นหาชื่อแปลง, โฉนด, อำเภอ...",
    db_clear_filter: "ล้างตัวกรอง",
    db_no_plots_admin: "ไม่พบข้อมูลแปลงปลูกตามเงื่อนไขที่เลือก",

    db_th_code: "รหัส / ชื่อแปลงปลูก",
    db_th_farmer: "เกษตรกรเจ้าของแปลง",
    db_th_doc_loc: "เอกสารสิทธิ์ / ที่ตั้ง",
    db_th_clone: "พันธุ์ยาง",
    db_th_area: "เนื้อที่ (ไร่)",
    db_th_tapping: "สถานะการกรีด",
    db_th_eudr: "สถานะความสอดคล้อง",
    db_th_updated: "อัปเดตล่าสุด",
    db_th_actions: "การจัดการ",
    db_th_map: "แผนที่ GIS",

    // ================= MAP.PHP =================
    map_hero_tag: "🌱 WEB-GIS RUBBER PLOT REGISTRY & EUDR VERIFICATION",
    map_hero_title: "ระบบทะเบียนแปลงปลูกและพิกัดภูมิสารสนเทศ (GIS)",
    map_hero_sub: "วาดขอบเขตแปลงปลูก (Polygon), ตรวจสอบพิกัด GPS, วิเคราะห์การทับซ้อนแนวเขตป่าสงวน 26 แห่ง จ.สุราษฎร์ธานี แบบ Real-Time พร้อมออกหนังสือรับรอง EUDR Passport",
    
    map_toolbar_title: "แผนที่พิกัดแปลงปลูก & แนวเขตป่าสงวน",
    map_btn_reset: "กลับจุดเริ่มต้น",
    map_btn_draw_new: "✏️ วาดแปลงใหม่",
    map_btn_cancel_draw: "❌ ยกเลิกการวาด",
    map_btn_save_plot: "💾 บันทึกแปลงปลูก",
    map_btn_gps_me: "📍 GPS ของฉัน",
    map_btn_layer_toggle: "🗺️ ชั้นข้อมูล",
    map_search_ph: "🔍 ค้นหาชื่อแปลง, รหัส, เกษตรกร...",
    map_sidebar_title: "รายการแปลงปลูกในระบบ",
    map_stat_total: "แปลงทั้งหมด",
    map_stat_compliant: "ผ่านเกณฑ์ EUDR",
    map_stat_review: "เฝ้าระวัง Buffer",
    map_stat_non_compliant: "ทับซ้อนป่า",
    
    map_layer_panel_title: "แผงควบคุมแผนที่",
    map_layer_panel_sub: "Layer Control & Tools",
    map_lbl_basemap: "แผนที่ฐาน (Basemap)",
    map_opt_satellite: "🛰️ ภาพถ่ายดาวเทียม (Satellite)",
    map_opt_osm: "🗺️ แผนที่ถนน (OpenStreetMap)",
    map_opt_topo: "⛰️ ภูมิประเทศ (Topographic)",
    map_lbl_layers: "เปิด/ปิดชั้นข้อมูล (Layers):",
    map_layer_forest: "แนวเขตป่าสงวนแห่งชาติ",
    map_layer_forest_sub: "(26 ผืนป่า จ.สุราษฎร์ฯ)",
    map_layer_plots: "แปลงปลูกยางพารา",
    map_layer_plots_sub: "(แปลงทะเบียน EUDR)",
    map_tools_title: "เครื่องมือจัดการแปลง:",
    map_legend_title: "สถานะความสอดคล้อง EUDR",
    map_legend_compliant: "ผ่านเกณฑ์ EUDR (ปลอดตัดไม้)",
    map_legend_overlap: "ทับซ้อนป่าสงวน (ไม่ผ่านเกณฑ์)",
    map_legend_buffer: "โซนเฝ้าระวัง (Buffer < 500 ม.)",
    map_legend_forest: "แนวเขตป่าสงวน (Zone C เขตหวงห้าม)",

    map_registry_title: "แปลงปลูกยางพารา",
    map_registry_sub: "ทะเบียนแปลงปลูกยางพาราและระบบตรวจสอบย้อนกลับมาตรฐาน EUDR (Digital Passport Registry)",
    map_btn_add_plot: "เพิ่มแปลง",
    map_filter_all: "ทั้งหมด",
    map_filter_compliant: "🟢 ผ่านเกณฑ์",
    map_filter_review: "🟡 เฝ้าระวัง",
    map_filter_non_compliant: "🔴 ซ้อนทับป่า",

    map_form_title_new: "ลงทะเบียนแปลงปลูกใหม่",
    map_form_title_edit: "แก้ไขข้อมูลแปลงปลูก",
    map_lbl_farmer_select: "เลือกเกษตรกรเจ้าของแปลง:",
    map_lbl_plot_name: "ชื่อแปลงปลูก:",
    map_lbl_doc_type: "ประเภทเอกสารสิทธิ์:",
    map_lbl_doc_no: "เลขที่เอกสารสิทธิ์:",
    map_lbl_rubber_clone: "พันธุ์ยางพารา:",
    map_lbl_plant_year: "ปีที่เริ่มปลูก (พ.ศ.):",
    map_lbl_tree_count: "จำนวนต้นยาง (ต้น):",
    map_lbl_tapping_status: "สถานะการเปิดกรีด:",
    map_lbl_calculated_area: "เนื้อที่คำนวณจากแผนที่:",
    map_lbl_forest_distance: "ระยะห่างจากแนวป่าสงวน:",
    map_lbl_overlap_pct: "เปอร์เซ็นต์การทับซ้อนป่า:",
    map_qr_modal_title: "QR Code หนังสือรับรอง EUDR Passport",
    map_qr_modal_sub: "สแกนด้วยสมาร์ทโฟนเพื่อตรวจสอบความถูกต้องของแปลงปลูก",
    map_qr_hint: "สแกนผ่านกล้องโทรศัพท์มือถือ หรือแอปพลิเคชัน Line เพื่อดูรายละเอียดแปลงปลูกและหนังสือรับรอง",

    // ================= YIELDS.PHP =================
    yd_hero_tag: "🌱 LATEX PRODUCTION & YIELD TRACKING SYSTEM",
    yd_hero_title: "ระบบบันทึกผลผลิตน้ำยางสด",
    yd_hero_sub: "บันทึกปริมาณน้ำยางสด ราคารับซื้อ เชื่อมโยงข้อมูลแปลงปลูกและเกษตรกรสู่ระบบตรวจสอบย้อนกลับ (Traceability) ตามมาตรฐาน EUDR",
    
    yd_form_card_title: "บันทึกข้อมูลการรับซื้อน้ำยางสดประจำวัน",
    yd_form_card_sub: "คำนวณเนื้อยางแห้ง (% DRC) และออกรหัส Digital Batch Token อัตโนมัติ",
    yd_lbl_select_plot: "เลือกแปลงปลูก:",
    yd_opt_select_plot: "-- กรุณาเลือกแปลงปลูก --",
    yd_lbl_harvest_date: "วันที่บันทึกผลผลิต:",
    yd_lbl_latex_weight: "น้ำหนักน้ำยางสด (กก.):",
    yd_lbl_drc_pct: "เปอร์เซ็นต์เนื้อยางแห้ง (% DRC):",
    yd_lbl_dry_weight: "เนื้อยางแห้งคำนวณได้ (กก.):",
    yd_lbl_price_per_kg: "ราคารับซื้อ (บาท/กก.):",
    yd_lbl_total_amount: "ยอดเงินรวม (บาท):",
    yd_lbl_collector: "ผู้รับซื้อ / จุดรับซื้อ:",
    yd_calc_hint: "💡 ระบบจะคำนวณเนื้อยางแห้งและยอดเงินรวมให้โดยอัตโนมัติ",
    yd_btn_save_yield: "💾 บันทึกผลผลิตน้ำยาง",

    yd_stat_month_weight: "น้ำหนักรวมเดือนนี้ (กก.)",
    yd_stat_month_income: "รายได้รวมเดือนนี้ (บาท)",
    yd_stat_avg_drc: "ค่าเฉลี่ยเนื้อยางแห้ง (% DRC)",
    yd_stat_avg_price: "ราคารับซื้อเฉลี่ย",
    yd_stat_total_batches: "จำนวนครั้งที่ส่งน้ำยาง",
    yd_table_title: "ประวัติการบันทึกผลผลิตและส่งมอบน้ำยางสด",
    yd_table_sub: "รายการบันทึกส่งมอบผลผลิตพร้อมรหัส EUDR Traceability Token ประจำล็อต",
    yd_search_ph: "🔍 ค้นหาแปลงปลูก, รหัสล็อต หรือผู้รับซื้อ...",
    yd_th_date: "วันที่บันทึก",
    yd_th_plot: "แปลงปลูก",
    yd_th_weight: "น้ำหนักสด (กก.)",
    yd_th_drc: "% DRC",
    yd_th_dry: "เนื้อยางแห้ง (กก.)",
    yd_th_price: "ราคา (บาท)",
    yd_th_total: "รวมเงิน (บาท)",
    yd_th_token: "รหัส EUDR Batch",
    yd_no_data: "ยังไม่มีรายการบันทึกผลผลิตในระบบ",
    yd_receipt_title: "ใบเสร็จรับเงินค่ายางพารา",
    yd_receipt_sub: "EUDR Compliant Fresh Latex Delivery Receipt",
    yd_btn_print_receipt: "🖨️ พิมพ์ใบเสร็จ",

    // ================= CONTACT.PHP =================
    ct_hero_tag: "COMMUNICATION & SUPPORT CENTER",
    ct_hero_title: "ศูนย์บริการข้อมูลและติดต่อสอบถาม",
    ct_hero_sub: "ศูนย์กลางการประสานงานและบริการข้อมูลภูมิสารสนเทศอัจฉริยะ GeoRubber Watch มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี เพื่อยกระดับสวนยางพาราสู่มาตรฐานความยั่งยืน EUDR",
    ct_card_info_title: "Contact Information",
    ct_card_info_sub: "สอบถามข้อมูลการใช้งานระบบ การวิเคราะห์พิกัดแปลง หรือความสอดคล้องตามมาตรฐาน EUDR ได้ตลอดเวลา",
    ct_card_lbl_phone: "โทรศัพท์ติดต่อ",
    ct_card_lbl_email: "อีเมลสำหรับติดต่อ",
    ct_card_lbl_loc: "สถานที่ตั้งสถาบัน",
    ct_card_loc_name: "มหาวิทยาลัยสงขลานครินทร์ วิทยาเขตสุราษฎร์ธานี",
    ct_card_loc_addr: "31 หมู่ 6 ต.มะขามเตี้ย อ.เมือง จ.สุราษฎร์ธานี 84000",
    ct_working_hours: "🕘 จันทร์ - ศุกร์: 08:30 - 16:30 น.",
    ct_lbl_name: "Your Name (ชื่อ-นามสกุล)",
    ct_lbl_email: "Your Email (อีเมลติดต่อกลับ)",
    ct_lbl_subject: "Your Subject (หัวข้อเรื่อง)",
    ct_lbl_message: "Message (ข้อความของคุณ)",
    ct_ph_name: "เช่น สมชาย ใจดี",
    ct_ph_email: "name@example.com",
    ct_ph_subject: "ระบุหัวข้อเรื่อง เช่น สอบถามการวาดแปลง, การตรวจสอบความสอดคล้อง EUDR...",
    ct_ph_message: "Write here your message...",
    ct_btn_send: "Send Message (ส่งข้อความ)",
    ct_msg_sent_success: "ส่งข้อความเรียบร้อยแล้ว!",
    ct_msg_sent_sub: "ขอบคุณสำหรับข้อความ ทีมงานจะติดต่อกลับไปยังอีเมลของคุณโดยเร็วที่สุดครับ",
  },

  en: {
    // ================= COMMON / NAVBAR / FOOTER =================
    nav_brand: "GeoRubber Watch",
    nav_home: "Home",
    nav_gis: "GIS Map",
    nav_dashboard: "Dashboard",
    nav_plots: "Plantations",
    nav_yields: "Yields",
    nav_contact: "Contact Us",
    nav_login_title: "Sign In",
    nav_logout: "Logout",
    nav_logout_confirm: "Are you sure you want to log out?",
    nav_menu_label: "Open Navigation Menu",
    lang_toggle_title: "Click to switch language TH / EN",
    
    // User Roles
    role_farmer: "Rubber Farmer",
    role_super_admin: "Super Admin",
    role_forestry_admin: "Forestry Dept.",
    role_land_admin: "Lands Dept.",
    role_raot_admin: "RAOT Thailand",
    role_coop_admin: "Rubber Cooperative",
    role_rabber_admin: "System Admin",
    role_admin: "Administrator",

    // Common Buttons & Statuses
    btn_search: "Search",
    btn_filter: "Filter",
    btn_export: "Export",
    btn_save: "Save Data",
    btn_cancel: "Cancel",
    btn_close: "Close",
    btn_confirm: "Confirm",
    btn_edit: "Edit",
    btn_delete: "Delete",
    btn_view: "View Details",
    btn_copy: "Copy",
    btn_copied: "Copied successfully",
    btn_print: "Print Document",
    btn_download_dds: "Download Certificate (DDS)",
    lbl_loading: "Loading...",
    lbl_view_plot: "View Plot",
    lbl_total_area_colon: "Total Area:",
    lbl_average: "Avg",

    // Units
    unit_plots: "Plots",
    unit_rai: "Rai",
    unit_kg: "kg",
    unit_baht: "THB",
    unit_trees: "Trees",
    unit_percent: "%",
    unit_ha: "ha",

    // Status Badges
    status_compliant: "EUDR Compliant",
    status_under_review: "Buffer Zone Review",
    status_non_compliant: "Forest Overlap",
    status_tapping: "Tapping Active",
    status_growing: "Immature / Growing",
    status_safe: "Safe / Compliant",
    status_outside_forest: "Outside Forest Reserve",
    status_buffer_500m: "Buffer Zone 500m",
    status_overlap_zone_c: "Zone C Overlap",

    // Footer
    foot_res_proj: "GIS Research Project",
    foot_proj_name: "GeoRubber Watch • Surat Thani",
    foot_title: "Geospatial information service for traceability and compliance assessment under the EU Deforestation Regulation (EUDR).",
    foot_title_en: "GeoRubber Watch: Intelligent Monitoring Platform for Sustainable Rubber Plantations",
    foot_dept: "Division of Science and Technology, Faculty of Liberal Arts and Management Science<br>Prince of Songkla University, Surat Thani Campus<br><span class=\"text-white/75 text-[14px]\">31 Moo 6 Makham Tia, Mueang Surat Thani, 84000 Thailand</span>",
    foot_dev_header: "Developer Info & Contact",
    foot_dev_sub: "24/7 Online Geospatial Information Service",
    foot_authors: "👩‍💻 <strong>Authors:</strong> Miss Matinee Roynarin & Miss Manatsanan Anantanarong",
    foot_advisor: "🎓 <strong>Advisor:</strong> Assoc. Prof. Dr. Supattra Puttinaovarat",
    foot_email: "✉️ <strong>Email:</strong> <a href=\"mailto:6640011044@psu.ac.th\" class=\"hover:text-mezenc-mint underline\">6640011044@psu.ac.th</a>, <a href=\"mailto:6640011066@psu.ac.th\" class=\"hover:text-mezenc-mint underline\">6640011066@psu.ac.th</a>",
    foot_card_hdr: "SURAT THANI FOREST COVERAGE",
    foot_card_stat: "26 Forest Reserves (Zone C) • 784,618 Rai",
    foot_card_source: "Royal Forest Department Conservation Database",
    foot_copy: "© 2026 GeoRubber Watch • Prince of Songkla University, Surat Thani Campus",
    foot_eudr_cert: "EU Regulation (EU) 2023/1115 Zero Deforestation Compliant (EUDR)",

    // ================= INDEX.PHP =================
    idx_hero_tag: "THE FUTURE OF",
    idx_hero_title: "SUSTAINABLE RUBBER",
    idx_hero_sub: "Elevate rubber plantation management with GIS and satellite technology for sustainability and comprehensive EUDR compliance.",
    idx_search_ph: "Enter Title Deed No., N.S.3K or Plot Code...",
    idx_search_btn: "Verify",
    idx_search_empty: "Please enter a valid Title Deed No. or Plot Code",
    idx_search_loading: "Verifying with Cloud DB...",

    idx_c1_title: "Farmer & Plantation Management",
    idx_c1_desc: "Store and manage farmer profiles and rubber plantation records securely in digital format on Cloud database.",
    idx_c2_title: "Plot Boundary Mapping",
    idx_c2_desc: "Define and calculate real plantation polygon boundaries on interactive digital geospatial maps.",
    idx_c3_title: "Forest Overlap Verification",
    idx_c3_desc: "Perform real-time spatial analysis to verify plot boundaries against 26 National Forest Reserves.",
    idx_c4_title: "Yield Tracking & DSS",
    idx_c4_desc: "Log daily fresh latex yields with integrated Decision Support System dashboards for farm analytics.",
    idx_c5_title: "EUDR Digital Traceability",
    idx_c5_desc: "Provide farm-to-factory traceability for rubber commodities with scannable QR Code EUDR Passports.",
    idx_readmore: "Read More",

    idx_sec3_tag: "SPATIAL RISK ASSESSMENT & FOREST BOUNDARIES",
    idx_sec3_heading: "Protected Conservation Forests & Land Use<br>Surat Thani Province",
    idx_sec3_sub: "Centralized spatial information hub for monitoring 26 National Forest Reserves in Surat Thani.",
    idx_sec3_p1: "A centralized spatial data hub designed to monitor 26 National Forest Reserves across Surat Thani Province, integrating real GIS datasets to elevate sustainable natural resource management.",
    idx_sec3_p2: "Supports conservation studies and verifies economic crop coordinates against protected reserve boundaries, calculating buffer risk zones to prevent forest encroachment.",
    idx_sec3_stat1_lbl: "Total Protected Area",
    idx_sec3_stat1_val: "784,618 Rai",
    idx_sec3_stat2_lbl: "Forest Reserves",
    idx_sec3_stat2_val: "26 Reserves",
    idx_sec3_stat3_lbl: "Buffer Zone Radius",
    idx_sec3_stat3_val: "500 Meters",
    idx_sec3_btn: "Open Full Web-GIS Map ➔",
    idx_sec3_map_title: "National Forest Reserves Map, Surat Thani Province",
    idx_sec3_map_badge: "🔴 Strict Protection Zone",
    idx_sec3_info_title: "Surat Thani National Forest Reserves (Hover or tap on map for details)",
    idx_sec3_info_desc: "26 National Forest Reserves Database (Zone C) • 100% Zero Deforestation",
    idx_sec3_info_sub: "Covering over 784,618 Rai of protected conservation territory",

    idx_sec4_tag: "SYSTEM PIPELINE & USER GUIDE",
    idx_sec4_heading: "4 Steps to EUDR Compliance Certification",
    idx_sec4_sub: "Step-by-step geospatial guide for rubber farmers and enterprises to register and obtain EUDR compliance certification seamlessly.",
    idx_sec4_btn: "Start Using System Now",
    idx_step1_title: "Step 1: Draw Plot Geolocation Boundary",
    idx_step1_desc: "Pinpoint WGS84 coordinates and draw rubber plantation polygon boundaries with GIS tools, calculating Rai-Ngan-Wah automatically.",
    idx_step2_title: "Step 2: Automated Forest Overlap Analysis",
    idx_step2_desc: "Analyze spatial intersections and calculate 500-meter buffer zone distances against Surat Thani's 26 National Forest Reserves (Zone C).",
    idx_step3_title: "Step 3: Log Latex Harvest Yields",
    idx_step3_desc: "Record daily fresh latex weights, purchase prices, and monthly harvest yields linked to plot codes for trend forecasting.",
    idx_step4_title: "Step 4: EUDR Traceability & QR Certification",
    idx_step4_desc: "Generate digital compliance certificates and QR codes for buyers and customs officials to verify 100% farm-to-factory traceability.",

    idx_sec5_tag: "EUDR KNOWLEDGE BASE",
    idx_sec5_heading: "3 Spatial Risk Classification Levels",
    idx_sec5_sub: "Guidelines for classifying rubber plantations under EUDR Zero Deforestation criteria and National Forest Reserve Act.",
    idx_risk_c1_title: "26 Strict National Forest Reserves",
    idx_risk_c1_desc: "Plantations situated within 26 Surat Thani National Forest Reserves (Zone C) or areas deforested after Dec 31, 2020 (EU Cut-off Date).",
    idx_risk_c1_btn: "Explore 26 Forest Reserves ➔",
    idx_risk_c2_title: "500m Forest Buffer Zone",
    idx_risk_c2_desc: "Titled rubber farms located within 500 meters of national forest reserve borders, requiring strict perimeter monitoring.",
    idx_risk_c2_btn: "Check Buffer Distance ➔",
    idx_risk_c3_title: "EUDR Compliant & Deforestation-Free",
    idx_risk_c3_desc: "Fully titled rubber farms situated outside forest reserves planted prior to Dec 31, 2020, eligible for immediate EUDR digital certification.",
    idx_risk_c3_btn: "Issue EUDR Passport ➔",

    // Deed Modal
    deed_lbl_farmer: "👨‍🌾 Farm Owner / Farmer:",
    deed_lbl_doc: "📄 Title Deed Type:",
    deed_lbl_loc: "🗺️ Plot Location:",
    deed_lbl_area: "📐 Calculated Area:",
    deed_lbl_clone: "🌳 Rubber Clone / Status:",
    deed_lbl_dist: "🌲 Nearest Reserve Distance:",
    deed_lbl_checklist: "EUDR Due Diligence Compliance Checklist:",
    deed_chk1: "Full WGS84 polygon coordinates stored on Supabase Cloud",
    deed_chk2: "Zero deforestation verified after Dec 31, 2020",
    deed_chk3: "Valid land title deed; eligible for instant EUDR Passport",
    deed_notfound_title: "No Record Found in Database",
    deed_notfound_desc: "No matching plot code or title deed number was found in Supabase Cloud database.",
    deed_hint_hdr: "💡 <strong>Search Tips:</strong>",
    deed_hint_1: "Check spelling or hyphens, e.g., <code class=\"bg-amber-100 px-1 rounded\">RB-ST-2026-006</code> or <code class=\"bg-amber-100 px-1 rounded\">1234-5678</code>",
    deed_hint_2: "Try searching by <strong>Plot Name</strong> or <strong>Farmer Name</strong>",
    deed_hint_3: "If not yet registered, you can log in to map and register a new plot immediately.",
    deed_btn_close: "Close Window",
    deed_btn_map: "View on GIS Map ➔",

    // ================= OVERVIEW.PHP =================
    ov_hero_tag: "🌲 WEB-GIS FOREST CONSERVATION & LAND BOUNDARIES",
    ov_hero_title: "Surat Thani 26 National Forest Reserves GIS Map",
    ov_hero_sub: "Geospatial database showing 26 National Forest Reserve boundaries (Zone C), 500m Buffer Zones, and registered rubber plantation coordinates for sustainable forest monitoring.",
    ov_search_ph: "Search forest reserve name, code (e.g. R1.001), or district...",
    ov_btn_zoom_all: "Surat Thani Overview",
    ov_btn_reset: "Reset View",
    ov_btn_check_plot: "Inspect Plots",
    ov_layer_panel: "Map Layer Controls",
    ov_layer_panel_sub: "Layer Control & Tools",
    ov_lbl_search_forest: "Search Forest Reserves",
    ov_opt_select_forest: "Select National Forest Reserve",
    ov_btn_pin_tool: "Pin Location Check",
    ov_btn_gps_locate: "My Location (GPS)",
    ov_lbl_basemap: "Basemap Layers",
    ov_lbl_layers: "Toggle Map Layers",
    ov_lbl_eudr_legend: "EUDR Compliance Status",
    ov_psu_campus: "PSU Surat Thani",
    ov_layer_satellite: "Satellite Imagery",
    ov_layer_osm: "OpenStreetMap Roads",
    ov_layer_forest: "26 Forest Reserves (Zone C)",
    ov_layer_buffer: "500m Buffer Zones",
    ov_layer_plots: "Registered Rubber Plots",
    ov_list_title: "26 National Forest Reserves in Surat Thani",
    ov_total_reserves: "26 Reserves",
    ov_total_area: "784,618.00 Rai",
    ov_total_districts: "19 Districts",
    ov_legend_forest: "National Forest Reserve (Zone C)",
    ov_legend_buffer: "500m Buffer Zone",
    ov_legend_rubber: "Rubber Plantation Plot",

    // ================= DASHBOARD.PHP =================
    db_hero_tag: "DECISION SUPPORT SYSTEM (DSS) • SURAT THANI",
    db_hero_title: "Spatial Plantation Analytics & EUDR Compliance Dashboard",
    db_hero_title_farmer: "My Rubber Plantation & Yield Analytics Summary",
    db_hero_sub: "Monitor macro overview of Surat Thani rubber plantations, classifying EUDR compliance, buffer zones, and national forest reserve overlaps.",
    db_hero_sub_farmer: "Track your rubber plot overview, fresh latex production statistics, total estimated income, and EUDR compliance status.",

    db_card_my_plots: "My Total Plantations",
    db_card_total_plots: "Total Registered Plots",
    db_card_total_area: "Total Plantation Area",
    db_card_total_area_all: "Total Surat Thani Rubber Plantation Area",
    db_card_compliant: "EUDR Compliant Plots",
    db_card_compliant_sub: "Zero Deforestation Compliant",
    db_card_review: "Buffer Review Plots",
    db_card_review_sub: "Buffer Zone 500m",
    db_card_non_compliant: "Forest Overlap Plots",
    db_card_non_compliant_sub: "Zone C Forest Overlap",
    db_card_monthly_yield: "Accumulated Latex Yield",
    db_card_est_income: "Estimated Total Revenue",
    db_card_eudr_status: "EUDR Compliance Status",
    db_card_tapping_rate: "Active Tapping Rate",

    db_farmer_chart_yield: "📈 Latex Yield Trend",
    db_farmer_chart_yield_sub: "Daily fresh latex quantity (kg) & Dry Rubber Content DRC (%) per tapping round",
    db_farmer_chart_revenue: "💵 Price & Revenue Trend",
    db_farmer_chart_revenue_sub: "Latex purchase price (THB/kg) and total revenue per harvest batch (THB)",
    db_badge_30_rounds: "Last 30 Rounds",
    db_badge_revenue_stats: "Revenue Stats",

    db_farmer_table_title: "📋 My Rubber Plantations Registry",
    db_farmer_table_sub: "Summary of registered plantations, clone varieties, calculated area, and EUDR compliance status",
    db_btn_log_yield: "Log Harvest Yield",
    db_btn_add_plot: "Add New Plot",
    db_no_plots_farmer: "No rubber plantations registered yet.",

    db_sec_charts_title: "Geospatial Statistics & Plantation Distribution",
    db_status_ratio_title: "📊 Surat Thani Plantation EUDR Status Classification Ratio",
    db_status_ratio_sub: "Compare spatial area and plot breakdown against 26 National Forest Reserves",
    db_filter_all: "All Plots",
    db_filter_compliant: "🟢 Compliant",
    db_filter_review: "🟡 Buffer Review",
    db_filter_non_compliant: "🔴 Overlap",

    db_progress_green_title: "🟢 EUDR Compliant (100% Safe)",
    db_progress_green_desc: "Situated completely outside forest reserves and buffer zones",
    db_progress_yellow_title: "🟡 Buffer Zone Review (500m Perimeter)",
    db_progress_yellow_desc: "Within 500 meters of forest reserve boundary; perimeter monitoring required",
    db_progress_red_title: "🔴 Forest Reserve Overlap (Non-Compliant)",
    db_progress_red_desc: "Polygon intersects Surat Thani National Forest Reserve (Zone C)",

    db_chart_clone_title: "🧬 Rubber Clone Distribution",
    db_chart_clone_sub: "Distribution of rubber tree clones across Surat Thani Province",
    db_chart_monthly_title: "📅 Monthly Provincial Production & Revenue Trends",
    db_chart_monthly_sub: "Fresh latex volume (kg) and total monthly economic value",
    db_badge_provincial: "Provincial Macro",
    db_badge_monthly: "Monthly",

    db_table_title: "📋 Surat Thani Rubber Plantation Master Registry",
    db_table_sub: "Detailed breakdown of plantations, landholders, area, and spatial EUDR verification results",
    db_search_ph: "Search plot name, deed no., district...",
    db_clear_filter: "Clear Filters",
    db_no_plots_admin: "No matching rubber plantations found.",

    db_th_code: "Plot Code / Name",
    db_th_farmer: "Farmer / Landholder",
    db_th_doc_loc: "Title Deed / Location",
    db_th_clone: "Rubber Clone",
    db_th_area: "Area (Rai)",
    db_th_tapping: "Tapping Status",
    db_th_eudr: "EUDR Compliance",
    db_th_updated: "Last Updated",
    db_th_actions: "Actions",
    db_th_map: "GIS Map",

    // ================= MAP.PHP =================
    map_hero_tag: "🌱 WEB-GIS RUBBER PLOT REGISTRY & EUDR VERIFICATION",
    map_hero_title: "Rubber Plot Registry & GIS Geolocation Platform",
    map_hero_sub: "Draw plot polygons, verify GPS coordinates, analyze real-time spatial overlaps with 26 Surat Thani Forest Reserves, and issue official EUDR Passports.",

    map_toolbar_title: "Rubber Plot Coordinates & Forest Boundaries Map",
    map_btn_reset: "Reset View",
    map_btn_draw_new: "✏️ Draw New Plot",
    map_btn_cancel_draw: "❌ Cancel Drawing",
    map_btn_save_plot: "💾 Save Plot Polygon",
    map_btn_gps_me: "📍 My Location (GPS)",
    map_btn_layer_toggle: "🗺️ Layers",
    map_search_ph: "🔍 Search plot name, code, farmer...",
    map_sidebar_title: "Registered Plantations List",
    map_stat_total: "Total Plots",
    map_stat_compliant: "EUDR Compliant",
    map_stat_review: "Buffer Review",
    map_stat_non_compliant: "Forest Overlap",

    map_layer_panel_title: "Map Layer Controls",
    map_layer_panel_sub: "Layer Control & Tools",
    map_lbl_basemap: "Basemap Layers",
    map_opt_satellite: "🛰️ Satellite Imagery",
    map_opt_osm: "🗺️ OpenStreetMap Roads",
    map_opt_topo: "⛰️ Topographic Map",
    map_lbl_layers: "Toggle Map Layers:",
    map_layer_forest: "26 National Forest Reserves",
    map_layer_forest_sub: "(Zone C Surat Thani)",
    map_layer_plots: "Rubber Plantations",
    map_layer_plots_sub: "(EUDR Master Registry)",
    map_tools_title: "Plantation Management Tools:",
    map_legend_title: "EUDR Compliance Status",
    map_legend_compliant: "EUDR Compliant (Zero Deforestation)",
    map_legend_overlap: "Forest Overlap (Non-Compliant)",
    map_legend_buffer: "Buffer Watch Zone (< 500m)",
    map_legend_forest: "Forest Reserves (Zone C Protected)",

    map_registry_title: "Rubber Plantations",
    map_registry_sub: "Plantation registry and EUDR compliance digital passport system",
    map_btn_add_plot: "Add Plot",
    map_filter_all: "All Plots",
    map_filter_compliant: "🟢 Compliant",
    map_filter_review: "🟡 Buffer Review",
    map_filter_non_compliant: "🔴 Overlap",

    map_form_title_new: "Register New Rubber Plot",
    map_form_title_edit: "Edit Plantation Details",
    map_lbl_farmer_select: "Select Plantation Farmer:",
    map_lbl_plot_name: "Plantation Name:",
    map_lbl_doc_type: "Land Title Deed Type:",
    map_lbl_doc_no: "Title Deed Number:",
    map_lbl_rubber_clone: "Rubber Clone Variety:",
    map_lbl_plant_year: "Planting Year (B.E.):",
    map_lbl_tree_count: "Number of Trees:",
    map_lbl_tapping_status: "Tapping Status:",
    map_lbl_calculated_area: "Calculated Map Area:",
    map_lbl_forest_distance: "Nearest Forest Distance:",
    map_lbl_overlap_pct: "Forest Overlap %:",
    map_qr_modal_title: "EUDR Digital Passport QR Code",
    map_qr_modal_sub: "Scan with smartphone to verify plantation compliance",
    map_qr_hint: "Scan via smartphone camera or Line app to view full plantation certificate details",

    // ================= YIELDS.PHP =================
    yd_hero_tag: "🌱 LATEX PRODUCTION & YIELD TRACKING SYSTEM",
    yd_hero_title: "Fresh Latex Harvest Yield Tracking System",
    yd_hero_sub: "Log daily fresh latex weights, purchase prices, and connect plantation plots to EUDR digital supply chain traceability.",

    yd_form_card_title: "Daily Fresh Latex Yield Recording",
    yd_form_card_sub: "Automatically calculate dry rubber (% DRC) and generate EUDR batch tokens",
    yd_lbl_select_plot: "Select Plantation Plot:",
    yd_opt_select_plot: "-- Please Select a Plot --",
    yd_lbl_harvest_date: "Harvest Date:",
    yd_lbl_latex_weight: "Fresh Latex Weight (kg):",
    yd_lbl_drc_pct: "Dry Rubber Content (% DRC):",
    yd_lbl_dry_weight: "Calculated Dry Rubber (kg):",
    yd_lbl_price_per_kg: "Purchase Price (THB/kg):",
    yd_lbl_total_amount: "Total Amount (THB):",
    yd_lbl_collector: "Buying Station / Collector:",
    yd_calc_hint: "💡 The system automatically calculates dry rubber content and total amount",
    yd_btn_save_yield: "💾 Save Latex Harvest Log",

    yd_stat_month_weight: "Total Weight This Month (kg)",
    yd_stat_month_income: "Total Revenue This Month (THB)",
    yd_stat_avg_drc: "Average Dry Rubber (% DRC)",
    yd_stat_avg_price: "Avg Purchase Price (THB/kg)",
    yd_stat_total_batches: "Harvest Batches Recorded",
    yd_table_title: "Latex Harvest & Delivery History Logs",
    yd_table_sub: "Harvest records with assigned EUDR batch traceability tokens",
    yd_search_ph: "🔍 Search plot, batch token, or buyer...",
    yd_th_date: "Harvest Date",
    yd_th_plot: "Plantation Plot",
    yd_th_weight: "Weight (kg)",
    yd_th_drc: "% DRC",
    yd_th_dry: "Dry Rubber (kg)",
    yd_th_price: "Price (THB)",
    yd_th_total: "Total (THB)",
    yd_th_token: "EUDR Batch Token",
    yd_no_data: "No harvest yield records found in system.",
    yd_receipt_title: "Rubber Latex Purchase Receipt",
    yd_receipt_sub: "EUDR Compliant Fresh Latex Delivery Receipt",
    yd_btn_print_receipt: "🖨️ Print Receipt",

    // ================= CONTACT.PHP =================
    ct_hero_tag: "COMMUNICATION & SUPPORT CENTER",
    ct_hero_title: "Communication & Support Center",
    ct_hero_sub: "Central hub for communication, inquiries, and technical support for GeoRubber Watch Web-GIS at Prince of Songkla University, Surat Thani Campus, ensuring EUDR compliance.",
    ct_card_info_title: "Contact Information",
    ct_card_info_sub: "Reach out for inquiries regarding system operations, plot coordinate analysis, or EUDR compliance standards anytime.",
    ct_card_lbl_phone: "Contact Phone",
    ct_card_lbl_email: "Contact Email",
    ct_card_lbl_loc: "Campus Location",
    ct_card_loc_name: "Prince of Songkla University, Surat Thani Campus",
    ct_card_loc_addr: "31 Moo 6 Makham Tia, Mueang Surat Thani, Surat Thani 84000 Thailand",
    ct_working_hours: "🕘 Mon - Fri: 08:30 - 16:30",
    ct_lbl_name: "Your Name",
    ct_lbl_email: "Your Email",
    ct_lbl_subject: "Subject",
    ct_lbl_message: "Your Message",
    ct_ph_name: "e.g. John Doe",
    ct_ph_email: "name@example.com",
    ct_ph_subject: "e.g. Inquiries regarding plot boundary drawing, EUDR compliance verification...",
    ct_ph_message: "Write here your message...",
    ct_btn_send: "Send Message",
    ct_msg_sent_success: "Message Sent Successfully!",
    ct_msg_sent_sub: "Thank you for reaching out. Our team will get back to your email as soon as possible.",
  }
};

// Aliases mapping for index.php and components
const aliasMap = {
  hero_sub: 'idx_hero_sub',
  search_placeholder: 'idx_search_ph',
  search_btn: 'idx_search_btn',
  search_empty_warn: 'idx_search_empty',
  search_loading: 'idx_search_loading',
  card1_title: 'idx_c1_title',
  card1_desc: 'idx_c1_desc',
  card2_title: 'idx_c2_title',
  card2_desc: 'idx_c2_desc',
  card3_title: 'idx_c3_title',
  card3_desc: 'idx_c3_desc',
  card4_title: 'idx_c4_title',
  card4_desc: 'idx_c4_desc',
  card5_title: 'idx_c5_title',
  card5_desc: 'idx_c5_desc',
  card_readmore: 'idx_readmore',
  modal_badge_tag: 'idx_sec4_tag',
  modal_close_btn: 'btn_close',
  sec3_tag: 'idx_sec3_tag',
  sec3_heading: 'idx_sec3_heading',
  sec3_sub: 'idx_sec3_sub',
  sec3_p1: 'idx_sec3_p1',
  sec3_p2: 'idx_sec3_p2',
  sec3_stat1_lbl: 'idx_sec3_stat1_lbl',
  sec3_stat1_val: 'idx_sec3_stat1_val',
  sec3_stat2_lbl: 'idx_sec3_stat2_lbl',
  sec3_stat2_val: 'idx_sec3_stat2_val',
  sec3_stat3_lbl: 'idx_sec3_stat3_lbl',
  sec3_stat3_val: 'idx_sec3_stat3_val',
  sec3_btn: 'idx_sec3_btn',
  sec3_map_title: 'idx_sec3_map_title',
  sec3_map_badge: 'idx_sec3_map_badge',
  sec3_info_title: 'idx_sec3_info_title',
  sec3_info_desc: 'idx_sec3_info_desc',
  sec3_info_sub: 'idx_sec3_info_sub',
  sec4_tag: 'idx_sec4_tag',
  sec4_heading: 'idx_sec4_heading',
  sec4_sub: 'idx_sec4_sub',
  sec4_btn: 'idx_sec4_btn',
  step1_title: 'idx_step1_title',
  step1_desc: 'idx_step1_desc',
  step2_title: 'idx_step2_title',
  step2_desc: 'idx_step2_desc',
  step3_title: 'idx_step3_title',
  step3_desc: 'idx_step3_desc',
  step4_title: 'idx_step4_title',
  step4_desc: 'idx_step4_desc',
  sec5_tag: 'idx_sec5_tag',
  sec5_heading: 'idx_sec5_heading',
  sec5_sub: 'idx_sec5_sub',
  risk_c1_title: 'idx_risk_c1_title',
  risk_c1_desc: 'idx_risk_c1_desc',
  risk_c1_btn: 'idx_risk_c1_btn',
  risk_c2_title: 'idx_risk_c2_title',
  risk_c2_desc: 'idx_risk_c2_desc',
  risk_c2_btn: 'idx_risk_c2_btn',
  risk_c3_title: 'idx_risk_c3_title',
  risk_c3_desc: 'idx_risk_c3_desc',
  risk_c3_btn: 'idx_risk_c3_btn'
};

['th', 'en'].forEach(lang => {
  Object.entries(aliasMap).forEach(([shortKey, targetKey]) => {
    if (I18N_DICTIONARY[lang][targetKey] && !I18N_DICTIONARY[lang][shortKey]) {
      I18N_DICTIONARY[lang][shortKey] = I18N_DICTIONARY[lang][targetKey];
    }
  });
});

const I18n = {
  currentLang: 'th',

  getLang() {
    return localStorage.getItem('georubber_lang') || 'th';
  },

  setLanguage(lang) {
    if (lang !== 'th' && lang !== 'en') lang = 'th';
    this.currentLang = lang;
    localStorage.setItem('georubber_lang', lang);
    document.cookie = `georubber_lang=${lang}; path=/; max-age=31536000; SameSite=Lax`;
    document.documentElement.lang = lang;

    const dict = I18N_DICTIONARY[lang] || I18N_DICTIONARY.th;

    // 1. Translate all [data-i18n] elements
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (dict[key] !== undefined) {
        if (dict[key].includes('<') && dict[key].includes('>')) {
          el.innerHTML = dict[key];
        } else {
          el.textContent = dict[key];
        }
      }
    });

    // 2. Translate placeholders [data-i18n-placeholder]
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      if (dict[key] !== undefined) {
        el.placeholder = dict[key];
      }
    });

    // 3. Translate tooltips [data-i18n-title]
    document.querySelectorAll('[data-i18n-title]').forEach(el => {
      const key = el.getAttribute('data-i18n-title');
      if (dict[key] !== undefined) {
        el.title = dict[key];
      }
    });

    // 4. Update Header & Mobile Toggle Button Slider Visuals
    const updateToggleSwitch = (thumbId, thId, enId, btnId) => {
      const btn = document.getElementById(btnId);
      const thumb = document.getElementById(thumbId);
      const th = document.getElementById(thId);
      const en = document.getElementById(enId);

      if (btn && dict.lang_toggle_title) {
        btn.title = dict.lang_toggle_title;
      }

      if (thumb && th && en) {
        if (lang === 'th') {
          thumb.style.left = '3px';
          th.className = "relative z-10 w-1/2 text-center text-xs font-bold text-mezenc-deepTeal transition-colors duration-300 pointer-events-none";
          en.className = "relative z-10 w-1/2 text-center text-xs font-semibold text-white/70 transition-colors duration-300 pointer-events-none";
        } else {
          thumb.style.left = '43px';
          th.className = "relative z-10 w-1/2 text-center text-xs font-semibold text-white/70 transition-colors duration-300 pointer-events-none";
          en.className = "relative z-10 w-1/2 text-center text-xs font-bold text-mezenc-deepTeal transition-colors duration-300 pointer-events-none";
        }
      }
    };

    updateToggleSwitch('nav-thumb', 'nav-label-th', 'nav-label-en', 'lang-toggle-btn');
    updateToggleSwitch('nav-thumb-mobile', 'nav-label-th-mobile', 'nav-label-en-mobile', 'lang-toggle-btn-mobile');

    // 5. Dynamic updates on specific pages
    const searchInput = document.getElementById('hero-deed-search');
    if (searchInput && dict.search_placeholder) {
      searchInput.placeholder = dict.search_placeholder;
    }

    const districtTitle = document.getElementById('district-title');
    const districtDesc = document.getElementById('district-desc');
    const districtForest = document.getElementById('district-forest');
    if (districtTitle && districtDesc && districtForest && dict.sec3_info_title) {
      districtTitle.textContent = dict.sec3_info_title;
      districtDesc.textContent = dict.sec3_info_desc;
      districtForest.textContent = dict.sec3_info_sub;
    }

    // 6. Dispatch global event so charts (Chart.js), Leaflet maps, and tables can re-render
    window.dispatchEvent(new CustomEvent('languageChanged', { detail: { lang, dict } }));
  },

  toggleLanguage() {
    const nextLang = this.getLang() === 'th' ? 'en' : 'th';
    this.setLanguage(nextLang);
  },

  t(key, defaultVal = '') {
    const lang = this.getLang();
    const dict = I18N_DICTIONARY[lang] || I18N_DICTIONARY.th;
    return dict[key] !== undefined ? dict[key] : (defaultVal || key);
  },

  init() {
    const savedLang = this.getLang();
    this.setLanguage(savedLang);
  }
};

// Global toggle helper for onclick handlers
function toggleLanguage() {
  I18n.toggleLanguage();
}

// Auto-initialize on load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => I18n.init());
} else {
  I18n.init();
}
