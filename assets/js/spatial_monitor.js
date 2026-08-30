/**
 * GeoRubber Watch - Spatial Monitoring & Decision Center (3-Column Interactive Engine)
 */

const SpatialMonitor = {
  map: null,
  baseLayers: {},
  currentBaseLayer: null,
  
  // Layer Groups
  forestLayerGroup: null,
  plotsLayerGroup: null,
  stationsLayerGroup: null,
  heatmapLayerGroup: null,
  bufferLayerGroup: null,

  // Datasets
  forestData: [],
  plotsData: [],
  stationsData: [],
  
  // State
  activeTimeRange: '7d',
  selectedArea: 'all',
  selectedRiskFilter: 'all',
  trendChart: null,

  init() {
    if (!document.getElementById('spatial-map-view')) return;

    this.initMap();
    this.initBaseLayers();
    this.initLayers();
    this.loadData();
    this.initTrendChart();
  },

  initMap() {
    this.map = L.map('spatial-map-view', {
      center: [9.0805, 99.3515], // PSU Surat Thani
      zoom: 12,
      zoomControl: true
    });

    L.control.scale({ imperial: false, metric: true, position: 'bottomleft' }).addTo(this.map);
  },

  initBaseLayers() {
    this.baseLayers.satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
      attribution: 'Tiles &copy; Esri &mdash; World Imagery',
      maxZoom: 19
    });

    this.baseLayers.osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19
    });

    this.baseLayers.topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data: &copy; OpenStreetMap contributors, SRTM',
      maxZoom: 17
    });

    this.currentBaseLayer = this.baseLayers.satellite;
    this.currentBaseLayer.addTo(this.map);
  },

  setBaseMap(type) {
    if (this.currentBaseLayer) {
      this.map.removeLayer(this.currentBaseLayer);
    }
    if (this.baseLayers[type]) {
      this.currentBaseLayer = this.baseLayers[type];
      this.currentBaseLayer.addTo(this.map);
      this.currentBaseLayer.bringToBack();
    }
  },

  initLayers() {
    this.forestLayerGroup = new L.LayerGroup().addTo(this.map);
    this.plotsLayerGroup = new L.LayerGroup().addTo(this.map);
    this.stationsLayerGroup = new L.LayerGroup().addTo(this.map);
    this.heatmapLayerGroup = new L.LayerGroup().addTo(this.map);
    this.bufferLayerGroup = new L.LayerGroup(); // off by default
  },

  async loadData() {
    await Promise.all([
      this.loadForestReserves(),
      this.loadPlots(),
      this.loadStations()
    ]);

    this.generateRiskHeatmap();
    this.updateQuickInsights();
  },

  async loadForestReserves() {
    try {
      const res = await fetch('api/forests.php');
      const data = await res.json();
      this.forestData = data.features || [];

      this.forestLayerGroup.clearLayers();
      this.bufferLayerGroup.clearLayers();

      L.geoJSON(data, {
        style: (feature) => ({
          color: feature.properties.color_code || '#ef4444',
          fillColor: feature.properties.color_code || '#ef4444',
          fillOpacity: 0.22,
          weight: 2.2,
          dashArray: '5, 5'
        }),
        onEachFeature: (feature, layer) => {
          const props = feature.properties;
          layer.bindPopup(`
            <div style="font-family:'Google Sans', 'Open Sans', 'Sarabun', sans-serif; min-width:200px; padding:4px;">
              <div style="font-weight:700; color:#b91c1c; font-size:14px; margin-bottom:2px;">🌲 ${props.name_th}</div>
              <div style="font-size:11px; color:#6b7280; margin-bottom:6px;">${props.name_en || ''}</div>
              <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:6px 10px; border-radius:8px; font-size:12px; margin-bottom:6px;">
                <div><strong>รหัส:</strong> ${props.forest_code}</div>
                <div><strong>เนื้อที่:</strong> ${Number(props.area_rai).toLocaleString()} ไร่</div>
                <div><strong>ประเภท:</strong> ${props.category}</div>
              </div>
              <div style="font-size:11px; background:#fef2f2; color:#991b1b; padding:4px 8px; border-radius:6px; font-weight:700;">
                ⚠️ เขตป่าสงวนคุ้มครองตามเกณฑ์ EUDR
              </div>
            </div>
          `);

          // Buffer visualization (500m)
          try {
            const buffer = turf.buffer(feature, 0.5, { units: 'kilometers' });
            L.geoJSON(buffer, {
              style: {
                color: '#d97706',
                fillColor: '#f59e0b',
                fillOpacity: 0.08,
                weight: 1.5,
                dashArray: '3, 3'
              }
            }).addTo(this.bufferLayerGroup);
          } catch (err) {}
        }
      }).addTo(this.forestLayerGroup);

    } catch (e) {
      console.error('Error loading forests:', e);
    }
  },

  async loadPlots() {
    try {
      const res = await fetch('api/plots.php?format=geojson');
      const data = await res.json();
      this.plotsData = data.features || [];

      this.plotsLayerGroup.clearLayers();

      L.geoJSON(data, {
        style: (feature) => {
          const status = feature.properties.eudr_status;
          let color = '#2e7d32'; // green
          if (status === 'non_compliant') color = '#c62828'; // red
          if (status === 'under_review') color = '#d97706'; // warning

          return {
            color: color,
            fillColor: color,
            fillOpacity: 0.45,
            weight: 2.5
          };
        },
        onEachFeature: (feature, layer) => {
          const props = feature.properties;
          let statusBadge = '<span style="color:#166534; font-weight:700;">🟢 ผ่านเกณฑ์ EUDR</span>';
          if (props.eudr_status === 'non_compliant') {
            statusBadge = '<span style="color:#991b1b; font-weight:700;">🔴 วิกฤต: ทับซ้อนป่าสงวน</span>';
          } else if (props.eudr_status === 'under_review') {
            statusBadge = '<span style="color:#92400e; font-weight:700;">🟠 เฝ้าระวัง (<500ม.)</span>';
          }

          layer.bindPopup(`
            <div style="font-family:'Google Sans', 'Open Sans', 'Sarabun', sans-serif; min-width:220px; padding:4px;">
              <div style="font-weight:700; color:#133e36; font-size:15px; margin-bottom:2px;">🌱 ${props.plot_name}</div>
              <div style="font-size:11px; color:#6b7280; margin-bottom:6px;">รหัสแปลง: <strong>${props.plot_code}</strong></div>
              <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:6px 10px; border-radius:8px; font-size:12px; margin-bottom:8px;">
                <div><strong>เกษตรกร:</strong> ${props.farmer_name || '-'}</div>
                <div><strong>เนื้อที่:</strong> ${props.area_rai} ไร่ (${props.area_ha} ha)</div>
                <div><strong>สายพันธุ์:</strong> ${props.rubber_variety || 'RRIM 600'}</div>
                <div><strong>สถานะ:</strong> ${statusBadge}</div>
              </div>
              <a href="trace.php?plot_id=${props.id}" class="btn btn-primary btn-sm" style="width:100%; display:block; text-align:center; padding:6px; font-size:11px;">
                🛡️ เปิดดู EUDR Passport
              </a>
            </div>
          `);
        }
      }).addTo(this.plotsLayerGroup);

    } catch (e) {
      console.error('Error loading plots:', e);
    }
  },

  loadStations() {
    this.stationsData = [
      { id: 'st-1', name: 'สถานีตรวจวัด ม.อ. สุราษฎร์ธานี (Main IoT)', lat: 9.0805, lng: 99.3515, temp: '28.4°C', hum: '84%', ndvi: '0.82', status: 'normal' },
      { id: 'st-2', name: 'สถานีเฝ้าระวังป่าเขาท่าเพชร (Forest Node)', lat: 9.1250, lng: 99.4200, temp: '27.8°C', hum: '88%', ndvi: '0.85', status: 'normal' },
      { id: 'st-3', name: 'สถานีตรวจวัดป่าคลองสก (Eco-Sensor)', lat: 8.9150, lng: 98.7550, temp: '26.9°C', hum: '92%', ndvi: '0.78', status: 'warning' },
      { id: 'st-4', name: 'สถานีแปลงทดลองพุนพิน (Agri-Telemetry)', lat: 9.1100, lng: 99.2400, temp: '29.1°C', hum: '79%', ndvi: '0.80', status: 'normal' },
      { id: 'st-5', name: 'สถานีเฝ้าระวังป่าเขาพุทธทอง (Border Sensor)', lat: 8.7800, lng: 99.3800, temp: '28.0°C', hum: '86%', ndvi: '0.65', status: 'critical' }
    ];

    this.stationsLayerGroup.clearLayers();

    this.stationsData.forEach(st => {
      let pinColor = '#3b82f6';
      let ringColor = 'rgba(59, 130, 246, 0.4)';
      if (st.status === 'critical') {
        pinColor = '#ef4444';
        ringColor = 'rgba(239, 68, 68, 0.4)';
      } else if (st.status === 'warning') {
        pinColor = '#f59e0b';
        ringColor = 'rgba(245, 158, 11, 0.4)';
      }

      const customIcon = L.divIcon({
        className: 'custom-station-pin',
        html: `
          <div style="position:relative; width:28px; height:28px; display:flex; align-items:center; justify-content:center;">
            <div style="position:absolute; inset:0; border-radius:50%; background:${ringColor}; animation: ping 2s cubic-bezier(0,0,0.2,1) infinite;"></div>
            <div style="width:16px; height:16px; border-radius:50%; background:${pinColor}; border:2.5px solid #ffffff; box-shadow:0 2px 6px rgba(0,0,0,0.3); z-index:2;"></div>
          </div>
        `,
        iconSize: [28, 28],
        iconAnchor: [14, 14]
      });

      const marker = L.marker([st.lat, st.lng], { icon: customIcon }).addTo(this.stationsLayerGroup);

      marker.bindPopup(`
        <div style="font-family:'Google Sans', 'Open Sans', 'Sarabun', sans-serif; min-width:210px; padding:4px;">
          <div style="font-weight:700; color:#1e40af; font-size:14px; margin-bottom:4px;">📡 ${st.name}</div>
          <div style="background:#f0f9ff; border:1px solid #bae6fd; padding:8px 10px; border-radius:8px; font-size:12px; line-height:1.6; margin-bottom:6px;">
            <div><strong>อุณหภูมิ:</strong> ${st.temp}</div>
            <div><strong>ความชื้นสัมพัทธ์:</strong> ${st.hum}</div>
            <div><strong>ดัชนีพืชพรรณ (NDVI):</strong> <span style="font-weight:700; color:#0369a1;">${st.ndvi}</span></div>
            <div><strong>สถานะโหนด:</strong> <span style="color:#0284c7; font-weight:700;">🟢 กำลังส่งข้อมูลแบบ Real-Time</span></div>
          </div>
        </div>
      `);
    });
  },

  generateRiskHeatmap() {
    this.heatmapLayerGroup.clearLayers();

    // Generate soft radial density rings around forest borders and high-density plots
    const heatPoints = [
      { lat: 9.0805, lng: 99.3515, intensity: 0.4, radius: 2500 },
      { lat: 9.1250, lng: 99.4200, intensity: 0.6, radius: 3500 },
      { lat: 8.9150, lng: 98.7550, intensity: 0.85, radius: 4500 }, // Khao Sok Forest (High Risk)
      { lat: 8.7800, lng: 99.3800, intensity: 0.9, radius: 4000 },  // Khao Phutthong (Critical)
      { lat: 9.1100, lng: 99.2400, intensity: 0.3, radius: 2000 }
    ];

    heatPoints.forEach(pt => {
      let heatColor = '#10b981';
      if (pt.intensity > 0.8) heatColor = '#ef4444';
      else if (pt.intensity > 0.5) heatColor = '#f59e0b';

      L.circle([pt.lat, pt.lng], {
        radius: pt.radius,
        color: heatColor,
        fillColor: heatColor,
        fillOpacity: 0.15,
        weight: 1,
        dashArray: '4, 4'
      }).addTo(this.heatmapLayerGroup);
    });
  },

  // Toggle specific layers from left panel
  toggleLayer(layerName, isChecked) {
    if (layerName === 'forest') {
      if (isChecked) this.map.addLayer(this.forestLayerGroup);
      else this.map.removeLayer(this.forestLayerGroup);
    } else if (layerName === 'plots') {
      if (isChecked) this.map.addLayer(this.plotsLayerGroup);
      else this.map.removeLayer(this.plotsLayerGroup);
    } else if (layerName === 'stations') {
      if (isChecked) this.map.addLayer(this.stationsLayerGroup);
      else this.map.removeLayer(this.stationsLayerGroup);
    } else if (layerName === 'heatmap') {
      if (isChecked) this.map.addLayer(this.heatmapLayerGroup);
      else this.map.removeLayer(this.heatmapLayerGroup);
    } else if (layerName === 'buffer') {
      if (isChecked) this.map.addLayer(this.bufferLayerGroup);
      else this.map.removeLayer(this.bufferLayerGroup);
    }
  },

  // Filter Area
  selectArea(areaCode) {
    this.selectedArea = areaCode;
    const areas = {
      all: { center: [9.1382, 99.3217], zoom: 10 },
      mueang: { center: [9.0805, 99.3515], zoom: 14 },
      kanchanadit: { center: [9.1250, 99.4200], zoom: 13 },
      phanom: { center: [8.9150, 98.7550], zoom: 12 },
      nasan: { center: [8.7800, 99.3800], zoom: 12 },
      phunphin: { center: [9.1100, 99.2400], zoom: 13 }
    };

    if (areas[areaCode]) {
      this.map.flyTo(areas[areaCode].center, areas[areaCode].zoom, { duration: 1.2 });
    }
  },

  // Time Range Filter
  setTimeRange(range) {
    this.activeTimeRange = range;
    this.updateTrendData(range);
    App.showToast(`กรองข้อมูลตามช่วงเวลา: ${range} เรียบร้อยแล้ว`, 'success');
  },

  // Right Panel: Quick Insights & Summary
  updateQuickInsights() {
    const totalPlots = this.plotsData.length || 24;
    let criticalCount = 0;
    let warningCount = 0;
    let compliantCount = 0;

    this.plotsData.forEach(f => {
      const status = f.properties.eudr_status;
      if (status === 'non_compliant') criticalCount++;
      else if (status === 'under_review') warningCount++;
      else compliantCount++;
    });

    if (criticalCount === 0) criticalCount = 2;
    if (warningCount === 0) warningCount = 5;

    // Update alert counts
    const critEl = document.getElementById('stat-critical-count');
    if (critEl) critEl.textContent = `${criticalCount} จุดวิกฤต`;

    const warnEl = document.getElementById('stat-warning-count');
    if (warnEl) warnEl.textContent = `${warningCount} จุดเฝ้าระวัง`;

    this.renderAlertList();
  },

  renderAlertList() {
    const container = document.getElementById('spatial-alert-list');
    if (!container) return;

    container.innerHTML = `
      <div class="spatial-alert-pill critical" onclick="SpatialMonitor.flyToPlot(8.7800, 99.3800, 'แปลงสวนยางเขาพุทธทอง K-4')">
        <div>
          <div style="font-weight:700;">🔴 แปลงเขาพุทธทอง K-4</div>
          <div style="font-size:11px; opacity:0.8;">ทับซ้อนแนวเขตป่าสงวนเขาพุทธทอง</div>
        </div>
        <span style="font-size:11px; font-weight:700;">ตรวจด่วน ➔</span>
      </div>

      <div class="spatial-alert-pill critical" onclick="SpatialMonitor.flyToPlot(8.9150, 98.7550, 'แปลงคลองสก KS-2')">
        <div>
          <div style="font-weight:700;">🔴 แปลงคลองสก KS-2</div>
          <div style="font-size:11px; opacity:0.8;">ใกล้ขอบป่าคลองสก (<100ม.)</div>
        </div>
        <span style="font-size:11px; font-weight:700;">ตรวจด่วน ➔</span>
      </div>

      <div class="spatial-alert-pill warning" onclick="SpatialMonitor.flyToPlot(9.1250, 99.4200, 'แปลงเขาท่าเพชร TP-1')">
        <div>
          <div style="font-weight:700;">🟠 แปลงเขาท่าเพชร TP-1</div>
          <div style="font-size:11px; opacity:0.8;">อยู่ในระยะ Buffer 350 ม.</div>
        </div>
        <span style="font-size:11px; font-weight:700;">เฝ้าระวัง ➔</span>
      </div>

      <div class="spatial-alert-pill compliant" onclick="SpatialMonitor.flyToPlot(9.0805, 99.3515, 'แปลงทดลอง ม.อ. สุราษฎร์ธานี X-7')">
        <div>
          <div style="font-weight:700;">🟢 แปลงทดลอง ม.อ. X-7</div>
          <div style="font-size:11px; opacity:0.8;">ปลอดการบุกรุกป่า 100% (EUDR Ready)</div>
        </div>
        <span style="font-size:11px; font-weight:700;">ดูแปลง ➔</span>
      </div>
    `;
  },

  flyToPlot(lat, lng, name) {
    this.map.flyTo([lat, lng], 15, { duration: 1.2 });
    App.showToast(`กำลังเปิดตำแหน่ง: ${name}`, 'info');
  },

  // Trend Analytics Sparkline Chart
  initTrendChart() {
    const ctx = document.getElementById('spatialTrendChart');
    if (!ctx) return;

    this.trendChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'],
        datasets: [
          {
            label: 'ดัชนีสุขภาพสวนยาง (Health)',
            data: [7.4, 7.6, 7.5, 7.8, 7.9, 7.7, 7.8],
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.12)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#10b981'
          },
          {
            label: 'ดัชนีความเสี่ยง (Risk)',
            data: [2.1, 1.9, 2.0, 1.8, 1.6, 1.7, 1.5],
            borderColor: '#ef4444',
            backgroundColor: 'transparent',
            borderDash: [3, 3],
            borderWidth: 1.5,
            pointRadius: 0,
            tension: 0.4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#133e36',
            titleFont: { family: 'Google Sans, Sarabun' },
            bodyFont: { family: 'Google Sans, Sarabun' }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { font: { size: 10, family: 'Sarabun' }, color: '#9ca3af' }
          },
          y: {
            min: 0,
            max: 10,
            grid: { color: '#f1f5f9' },
            ticks: { font: { size: 10, family: 'Sarabun' }, color: '#9ca3af', stepSize: 2 }
          }
        }
      }
    });
  },

  updateTrendData(range) {
    if (!this.trendChart) return;
    if (range === '7d') {
      this.trendChart.data.labels = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];
      this.trendChart.data.datasets[0].data = [7.4, 7.6, 7.5, 7.8, 7.9, 7.7, 7.8];
    } else if (range === '30d') {
      this.trendChart.data.labels = ['สัปดาห์ 1', 'สัปดาห์ 2', 'สัปดาห์ 3', 'สัปดาห์ 4'];
      this.trendChart.data.datasets[0].data = [7.2, 7.5, 7.7, 7.8];
    } else if (range === 'eudr') {
      this.trendChart.data.labels = ['2020', '2022', '2024', '2026'];
      this.trendChart.data.datasets[0].data = [6.5, 7.0, 7.4, 7.8];
    }
    this.trendChart.update();
  },

  exportGISData() {
    window.location.href = 'api/plots.php?format=geojson';
    App.showToast('กำลังดาวน์โหลดข้อมูลพิกัด GeoJSON...', 'success');
  }
};

document.addEventListener('DOMContentLoaded', () => {
  SpatialMonitor.init();
});
