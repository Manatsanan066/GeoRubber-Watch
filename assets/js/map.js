/**
 * GeoRubber Watch - Interactive Web GIS Engine
 * Powered by Leaflet.js, Leaflet.draw & Turf.js
 */

const GeoMap = {
  map: null,
  drawControl: null,
  drawnItems: null,
  forestLayerGroup: null,
  plotsLayerGroup: null,
  currentDrawnLayer: null,
  plotsData: [],
  forestData: [],

  baseLayers: {},
  currentBaseLayer: null,
  isForestVisible: true,
  isPlotsVisible: true,

  // Center coordinate: Prince of Songkla University, Surat Thani Campus
  defaultCenter: [9.0805, 99.3515],
  defaultZoom: 14,

  init(options = {}) {
    if (!document.getElementById('map-view')) return;
    if (this.map) return; // prevent double initialization

    const isOverviewPage = window.location.pathname.includes('overview.php') || 
                           window.location.href.includes('overview') || 
                           options.enableDraw === false || 
                           options.loadPlots === false || 
                           options.isOverview === true;

    this.initMap(options);
    this.initBaseLayers();
    if (!isOverviewPage) {
      this.initDrawTools();
      this.loadRubberPlots();
    }
    this.loadForestReserves();
  },

  initMap(options = {}) {
    this.map = L.map('map-view', {
      center: this.defaultCenter,
      zoom: this.defaultZoom,
      zoomControl: false
    });

    const isOverviewPage = window.location.pathname.includes('overview.php') || 
                           window.location.href.includes('overview') || 
                           options.isOverview === true;
    const zoomPosition = options.zoomPosition || (isOverviewPage ? 'topright' : 'topleft');

    // Zoom control on the right for overview or specified position
    L.control.zoom({ position: zoomPosition }).addTo(this.map);

    this.drawnItems = new L.FeatureGroup();
    this.map.addLayer(this.drawnItems);

    this.forestLayerGroup = new L.LayerGroup().addTo(this.map);
    this.plotsLayerGroup = new L.LayerGroup().addTo(this.map);

    // Scale control (500m bar at bottom left)
    L.control.scale({ imperial: false, position: 'bottomleft' }).addTo(this.map);

    // Disable Leaflet map click/scroll propagation on floating panel
    const floatingPanel = document.getElementById('floatingLayerPanel');
    if (floatingPanel) {
      L.DomEvent.disableClickPropagation(floatingPanel);
      L.DomEvent.disableScrollPropagation(floatingPanel);
      ['click', 'mousedown', 'dblclick', 'touchstart'].forEach(evt => {
        floatingPanel.addEventListener(evt, e => e.stopPropagation());
      });
    }

    setTimeout(() => {
      if (this.map) this.map.invalidateSize();
    }, 250);
  },

  // Toggle Forest Layer on/off (Button switch)
  toggleForestLayer(explicitState = null) {
    if (explicitState !== null) {
      this.isForestVisible = explicitState;
    } else {
      this.isForestVisible = !this.isForestVisible;
    }

    const btn = document.getElementById('switch-forest');
    if (btn) {
      if (this.isForestVisible) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    }

    if (!this.map || !this.forestLayerGroup) return;

    if (this.isForestVisible) {
      if (!this.map.hasLayer(this.forestLayerGroup)) {
        this.forestLayerGroup.addTo(this.map);
      }
    } else {
      if (this.map.hasLayer(this.forestLayerGroup)) {
        this.map.removeLayer(this.forestLayerGroup);
      }
    }
  },

  // Toggle Rubber Plots Layer on/off (Button switch)
  togglePlotsLayer(explicitState = null) {
    if (explicitState !== null) {
      this.isPlotsVisible = explicitState;
    } else {
      this.isPlotsVisible = !this.isPlotsVisible;
    }

    const btn = document.getElementById('switch-plots');
    if (btn) {
      if (this.isPlotsVisible) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    }

    if (!this.map || !this.plotsLayerGroup) return;

    if (this.isPlotsVisible) {
      if (!this.map.hasLayer(this.plotsLayerGroup)) {
        this.plotsLayerGroup.addTo(this.map);
      }
    } else {
      if (this.map.hasLayer(this.plotsLayerGroup)) {
        this.map.removeLayer(this.plotsLayerGroup);
      }
    }
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

    // Default to Satellite for forestry and agriculture
    this.currentBaseLayer = this.baseLayers.satellite;
    this.currentBaseLayer.addTo(this.map);

    L.control.scale({ imperial: false, metric: true, position: 'bottomleft' }).addTo(this.map);
  },

  setBaseMap(type) {
    if (this.currentBaseLayer) {
      this.map.removeLayer(this.currentBaseLayer);
    }
    if (type === 'satellite') {
      this.currentBaseLayer = this.baseLayers.satellite;
    } else if (type === 'osm') {
      this.currentBaseLayer = this.baseLayers.osm;
    } else if (type === 'topo') {
      this.currentBaseLayer = this.baseLayers.topo;
    }
    if (this.currentBaseLayer) {
      this.currentBaseLayer.addTo(this.map);
      this.currentBaseLayer.bringToBack();
    }
  },

  initDrawTools() {
    this.drawControl = new L.Control.Draw({
      position: 'topleft',
      draw: {
        polygon: {
          allowIntersection: false,
          showArea: true,
          drawError: {
            color: '#e11d48',
            message: '<strong>ข้อผิดพลาด:</strong> เส้นขอบแปลงห้ามตัดกันเอง'
          },
          shapeOptions: {
            color: '#10b981',
            fillColor: '#10b981',
            fillOpacity: 0.35,
            weight: 3
          }
        },
        rectangle: false,
        circle: false,
        circlemarker: false,
        marker: false,
        polyline: false
      },
      edit: {
        featureGroup: this.drawnItems,
        remove: true
      }
    });

    this.map.addControl(this.drawControl);

    // Event on draw completed
    this.map.on(L.Draw.Event.CREATED, (event) => {
      const layer = event.layer;
      this.drawnItems.clearLayers();
      this.drawnItems.addLayer(layer);
      this.currentDrawnLayer = layer;

      const geojson = layer.toGeoJSON();
      this.handleDrawnPolygon(geojson);
    });
  },

  // Load Forest Reserve GeoJSON
  async loadForestReserves() {
    try {
      const res = await fetch('api/forests.php');
      const data = await res.json();
      this.forestData = data.features || [];

      this.forestLayerGroup.clearLayers();

      const forestGeoLayer = L.geoJSON(data, {
        style: (feature) => ({
          color: feature.properties.color_code || '#dc2626',
          fillColor: feature.properties.color_code || '#dc2626',
          fillOpacity: 0.32,
          weight: 2.2,
          dashArray: '3, 3'
        }),
        onEachFeature: (feature, layer) => {
          const props = feature.properties;
          layer.bindPopup(`
            <div style="font-family: 'Google Sans', 'Open Sans', 'Sarabun', sans-serif; color: #1e293b; padding: 4px; min-width: 260px;">
              <div style="font-weight: 800; color: #0e4d4e; font-size: 17px; margin-bottom: 2px;">🌲 ${props.name_th}</div>
              <div style="font-size: 13px; color: #64748b; margin-bottom: 8px;">${props.name_en || ''}</div>
              <div style="background: #f8faf9; border: 1.5px solid #bee6e1; padding: 8px 10px; border-radius: 10px; font-size: 14px; line-height: 1.6; margin-bottom: 8px;">
                <div><strong>รหัสพื้นที่:</strong> <span style="font-family:monospace; color:#0e4d4e; font-weight:700; font-size:14px;">${props.forest_code}</span></div>
                <div><strong>เนื้อที่ประมาณ:</strong> <span style="font-weight:700; color:#0e4d4e;">${parseFloat(props.area_rai).toLocaleString()} ไร่</span></div>
                <div><strong>ประเภท:</strong> ${props.category || 'Zone-C ป่าสงวนแห่งชาติ'}</div>
              </div>
              <div style="font-size: 13px; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 6px 10px; border-radius: 8px; font-weight: 700; line-height: 1.3;">
                ⚠️ พื้นที่คุ้มครองตามเกณฑ์ EUDR ห้ามบุกรุก/ตัดไม้ทำลายป่า
              </div>
            </div>
          `);

          layer.on('click', () => {
            if (typeof GeoOverview !== 'undefined' && GeoOverview.showForestInfoCard) {
              GeoOverview.showForestInfoCard(props);
            }
          });
        }
      }).addTo(this.forestLayerGroup);

      const isOverviewPage = window.location.pathname.includes('overview.php') || 
                             window.location.href.includes('overview');

      if (isOverviewPage && this.forestData.length > 0 && this.map) {
        try {
          const bounds = forestGeoLayer.getBounds();
          if (bounds.isValid()) {
            this.map.fitBounds(bounds, { padding: [25, 25] });
          }
        } catch(e) {}
      }

      // Hide or update loading indicator
      const loadingEl = document.getElementById('forest-loading-status');
      if (loadingEl) {
        loadingEl.innerHTML = '<span class="text-emerald-600">✅</span> <span class="text-emerald-800">แสดงครบ 26 แนวเขตป่าสงวน</span>';
        loadingEl.className = 'absolute top-4 left-16 z-[400] bg-white/95 backdrop-blur-md px-3.5 py-1.5 rounded-2xl border-2 border-emerald-300 shadow-md flex items-center gap-2 text-xs font-bold transition-all duration-500 pointer-events-none opacity-100';
        setTimeout(() => {
          loadingEl.style.opacity = '0';
          setTimeout(() => loadingEl.remove(), 500);
        }, 2000);
      }

    } catch (e) {
      console.error('Error loading forest reserves:', e);
      const loadingEl = document.getElementById('forest-loading-status');
      if (loadingEl) loadingEl.remove();
    }
  },

  // Load Rubber Plots from API
  async loadRubberPlots(farmerId = null, statusFilter = null) {
    try {
      let url = 'api/plots.php?format=geojson';
      if (farmerId) url += `&farmer_id=${farmerId}`;
      if (statusFilter) url += `&status=${statusFilter}`;

      const res = await fetch(url);
      const data = await res.json();
      this.plotsData = data.features || [];

      this.plotsLayerGroup.clearLayers();

      const geoJsonLayer = L.geoJSON(data, {
        style: (feature) => {
          const status = feature.properties.eudr_status;
          let color = '#2e7d32'; // green (compliant)
          if (status === 'non_compliant') color = '#c62828'; // red
          if (status === 'under_review') color = '#d97706'; // amber

          return {
            color: color,
            fillColor: color,
            fillOpacity: 0.45,
            weight: 2.5
          };
        },
        onEachFeature: (feature, layer) => {
          const p = feature.properties;
          let statusBadge = '<span class="badge badge-compliant">✅ สอดคล้อง EUDR</span>';
          if (p.eudr_status === 'non_compliant') statusBadge = '<span class="badge badge-non_compliant">⛔ ไม่ผ่านเกณฑ์ EUDR</span>';
          if (p.eudr_status === 'under_review') statusBadge = '<span class="badge badge-under_review">⚠️ โซนเฝ้าระวัง</span>';

          const popupContent = `
            <div style="min-width: 270px; font-family: 'Open Sans', 'Google Sans', 'Sarabun', sans-serif; color: #1e293b; padding: 6px;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px; gap: 8px;">
                <span style="font-size: 16px; color: #00a699; font-family: monospace; font-weight:700;">${p.plot_code}</span>
                ${statusBadge}
              </div>
              <div style="font-weight: 700; font-size: 18px; color: #0e4d4e; margin-bottom: 6px;">${p.plot_name}</div>
              <div style="font-size: 16px; color: #475569; margin-bottom: 10px;">👨‍🌾 <strong>เจ้าของ:</strong> ${p.farmer_name}</div>
              
              <div style="background: #f8faf9; border: 1.5px solid #bee6e1; padding: 10px 12px; border-radius: 12px; font-size: 16px; margin-bottom: 12px; line-height: 1.6;">
                <div>📐 <strong>เนื้อที่:</strong> <span style="color:#059669; font-weight:700;">${p.formatted_area}</span> (${p.area_hectare} ha)</div>
                <div>🌱 <strong>พันธุ์ยาง:</strong> <span style="color:#0e4d4e; font-weight:700;">${p.rubber_clone}</span> | <strong>ปี:</strong> ${p.planting_year}</div>
                <div>🌳 <strong>จำนวนต้น:</strong> ${p.tree_count.toLocaleString()} ต้น</div>
                <div>📍 <strong>พิกัด:</strong> ${p.centroid.lat.toFixed(5)}, ${p.centroid.lng.toFixed(5)}</div>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 6px;">
                <button onclick="App.showQRCodeModal('${p.traceability_token}', '${p.plot_name}', '${p.plot_code}')" class="btn btn-outline btn-sm" style="font-size: 13px; padding: 5px 8px;">
                  📱 QR Code
                </button>
                <a href="trace.php?token=${p.traceability_token}" target="_blank" class="btn btn-primary btn-sm" style="font-size: 13px; padding: 5px 8px; background-color: #00a699; color: white; text-align: center;">
                  🛡️ Passport
                </a>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                <button onclick="GeoMap.openEditPlotModal(${p.id})" class="btn btn-outline btn-sm" style="font-size: 13px; padding: 5px 8px; color: #0284c7; border-color: #bae6fd; background-color: #f0f9ff;">
                  ✏️ แก้ไข
                </button>
                <button onclick="GeoMap.deletePlot(${p.id}, '${p.plot_name}')" class="btn btn-outline btn-sm" style="font-size: 13px; padding: 5px 8px; color: #e11d48; border-color: #fecdd3; background-color: #fff1f2;">
                  🗑️ ลบแปลง
                </button>
              </div>
            </div>
          `;

          layer.bindPopup(popupContent);

          // Add tooltip
          layer.bindTooltip(`<strong>${p.plot_name}</strong><br>${p.formatted_area}`, {
            direction: 'top',
            sticky: true
          });

          // Click handler to highlight in list
          layer.on('click', () => {
            GeoMap.highlightPlotInList(p.id);
          });
        }
      }).addTo(this.plotsLayerGroup);

      // Render sidebar plots list
      this.renderSidebarPlotsList(this.plotsData);

    } catch (e) {
      console.error('Error loading rubber plots:', e);
    }
  },

  // Render plots in the bottom grid container
  renderSidebarPlotsList(features) {
    const listContainer = document.getElementById('plots-list-container');
    if (!listContainer) return;

    if (features.length === 0) {
      listContainer.innerHTML = '<div class="col-span-full text-center text-gray-400 py-12 text-[16px]">ไม่พบข้อมูลแปลงปลูกที่ตรงกับเงื่อนไขการค้นหา</div>';
      return;
    }

    let html = '';
    features.forEach(f => {
      const p = f.properties;
      let badge = '<span class="badge badge-compliant">🟢 EUDR ผ่าน</span>';
      if (p.eudr_status === 'non_compliant') badge = '<span class="badge badge-non_compliant">🔴 ทับซ้อนป่า</span>';
      if (p.eudr_status === 'under_review') badge = '<span class="badge badge-under_review">🟠 โซนเฝ้าระวัง</span>';

      html += `
        <div class="plot-card" id="plot-card-${p.id}" onclick="GeoMap.zoomToPlot(${p.centroid.lat}, ${p.centroid.lng}, ${p.id})">
          <div class="plot-header">
            <div>
              <div class="plot-title">${p.plot_name}</div>
              <div class="plot-code">${p.plot_code}</div>
            </div>
            ${badge}
          </div>
          <div class="plot-meta">
            <div>📐 <strong>เนื้อที่:</strong> ${p.formatted_area}</div>
            <div>👨‍🌾 <strong>เจ้าของ:</strong> ${p.farmer_name}</div>
            <div>🌱 <strong>พันธุ์:</strong> ${p.rubber_clone} (${p.planting_year})</div>
            <div>🌳 <strong>จำนวนต้น:</strong> ${p.tree_count ? p.tree_count.toLocaleString() : 0} ต้น</div>
          </div>
          <div class="plot-actions" style="display: flex; flex-direction: column; gap: 6px;">
            <div style="display: flex; gap: 6px; width: 100%;">
              <button type="button" onclick="event.stopPropagation(); App.showQRCodeModal('${p.traceability_token}', '${p.plot_name}', '${p.plot_code}')" class="btn btn-outline btn-sm" style="flex:1; font-size: 13px;">
                📱 QR Code
              </button>
              <a href="trace.php?token=${p.traceability_token}" target="_blank" onclick="event.stopPropagation();" class="btn btn-outline btn-sm" style="flex:1; font-size: 13px;">
                🛡️ Passport
              </a>
            </div>
            <div style="display: flex; gap: 6px; width: 100%;">
              <button type="button" onclick="event.stopPropagation(); GeoMap.openEditPlotModal(${p.id})" class="btn btn-sm" style="flex:1; font-size: 13px; background-color: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd;">
                ✏️ แก้ไข
              </button>
              <button type="button" onclick="event.stopPropagation(); GeoMap.deletePlot(${p.id}, '${p.plot_name}')" class="btn btn-sm" style="flex:1; font-size: 13px; background-color: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;">
                🗑️ ลบแปลง
              </button>
            </div>
          </div>
        </div>
      `;
    });

    listContainer.innerHTML = html;
    
    // Update count indicator
    const countElem = document.getElementById('total-plots-count-badge');
    if (countElem) countElem.textContent = `${features.length} แปลง`;
  },

  // Delete Plot from Database
  async deletePlot(plotId, plotName) {
    if (!confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบแปลงปลูก "${plotName}" ออกจากฐานข้อมูลจริง?`)) return;
    
    try {
      if (window.App && typeof window.App.showToast === 'function') {
        App.showToast('กำลังลบข้อมูลแปลงปลูกจากฐานข้อมูล...', 'info');
      }
      const res = await fetch(`api/plots.php?id=${plotId}`, { method: 'DELETE' });
      const data = await res.json();
      
      if (data.success || res.ok) {
        if (window.App && typeof window.App.showToast === 'function') {
          App.showToast(`🗑️ ลบแปลง "${plotName}" เรียบร้อยแล้ว`, 'success');
        } else {
          alert(`ลบแปลง "${plotName}" เรียบร้อยแล้ว`);
        }
        this.loadRubberPlots();
      } else {
        alert(data.message || 'ไม่สามารถลบข้อมูลได้');
      }
    } catch (e) {
      alert('เกิดข้อผิดพลาดในการลบข้อมูล');
    }
  },

  // Open Edit Modal for Plot
  async openEditPlotModal(plotId) {
    try {
      const res = await fetch(`api/plots.php?id=${plotId}`);
      const data = await res.json();
      if (!data.success || !data.plot) {
        alert('ไม่พบข้อมูลแปลงปลูก');
        return;
      }
      const p = data.plot;

      // Populate edit fields
      let editModal = document.getElementById('editPlotModal');
      if (!editModal) {
        this.injectEditPlotModal();
      }

      document.getElementById('edit-plot-id').value = p.id;
      document.getElementById('edit-plot-name').value = p.plot_name;
      document.getElementById('edit-deed-type').value = p.title_deed_type || 'โฉนดที่ดิน (น.ส. 4 จ)';
      document.getElementById('edit-deed-no').value = p.title_deed_no || '';
      document.getElementById('edit-rubber-clone').value = p.rubber_clone || 'RRIM 600';
      document.getElementById('edit-planting-year').value = p.planting_year || 2018;
      document.getElementById('edit-tree-count').value = p.tree_count || 300;
      document.getElementById('edit-tapping-status').value = p.tapping_status || 'tapping';
      document.getElementById('edit-notes').value = p.notes || '';

      if (window.App && typeof window.App.openModal === 'function') {
        App.openModal('editPlotModal');
      } else {
        document.getElementById('editPlotModal')?.classList.remove('hidden');
      }
    } catch (e) {
      console.error('Error opening edit plot modal:', e);
    }
  },

  // Inject Edit Plot Modal into DOM if missing
  injectEditPlotModal() {
    if (document.getElementById('editPlotModal')) return;
    const modalHtml = `
      <div id="editPlotModal" class="modal-overlay hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl border border-gray-100 space-y-4 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <h3 class="font-extrabold text-lg text-mezenc-teal">✏️ แก้ไขข้อมูลแปลงปลูก</h3>
            <button onclick="App.closeModal('editPlotModal')" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center">✕</button>
          </div>
          <form onsubmit="GeoMap.handleSaveEditPlot(event)" class="space-y-4 text-xs sm:text-sm">
            <input type="hidden" id="edit-plot-id">
            <div>
              <label class="block font-bold text-gray-700 mb-1">ชื่อแปลงปลูก *</label>
              <input type="text" id="edit-plot-name" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-mezenc-teal" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-gray-700 mb-1">ประเภทเอกสารสิทธิ์</label>
                <select id="edit-deed-type" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-mezenc-teal">
                  <option value="โฉนดที่ดิน (น.ส. 4 จ)">โฉนดที่ดิน (น.ส. 4 จ)</option>
                  <option value="น.ส. 3 ก">น.ส. 3 ก</option>
                  <option value="ส.ป.ก. 4-01">ส.ป.ก. 4-01</option>
                  <option value="ภ.บ.ท. 5">ภ.บ.ท. 5</option>
                </select>
              </div>
              <div>
                <label class="block font-bold text-gray-700 mb-1">เลขที่เอกสารสิทธิ์</label>
                <input type="text" id="edit-deed-no" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-mezenc-teal">
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-gray-700 mb-1">พันธุ์ยางพารา</label>
                <input type="text" id="edit-rubber-clone" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-mezenc-teal">
              </div>
              <div>
                <label class="block font-bold text-gray-700 mb-1">ปีที่เริ่มปลูก (พ.ศ./ค.ศ.)</label>
                <input type="number" id="edit-planting-year" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-mezenc-teal">
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-bold text-gray-700 mb-1">จำนวนต้นยาง (ต้น)</label>
                <input type="number" id="edit-tree-count" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-mezenc-teal">
              </div>
              <div>
                <label class="block font-bold text-gray-700 mb-1">สถานะการเปิดกรีด</label>
                <select id="edit-tapping-status" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:border-mezenc-teal">
                  <option value="tapping">เปิดกรีดแล้ว (Tapping)</option>
                  <option value="not_tapping">ยังไม่เปิดกรีด (Immature)</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block font-bold text-gray-700 mb-1">หมายเหตุเพิ่มเติม</label>
              <textarea id="edit-notes" rows="2" class="w-full px-3.5 py-2 rounded-xl border border-gray-200 focus:outline-none focus:border-mezenc-teal"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
              <button type="button" onclick="App.closeModal('editPlotModal')" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 font-bold hover:bg-gray-50">
                ยกเลิก
              </button>
              <button type="submit" class="px-6 py-2.5 rounded-xl bg-mezenc-teal hover:bg-mezenc-deepTeal text-white font-bold shadow">
                บันทึกการแก้ไข
              </button>
            </div>
          </form>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
  },

  // Save Plot Edits
  async handleSaveEditPlot(e) {
    e.preventDefault();
    const plotId = parseInt(document.getElementById('edit-plot-id').value);
    const payload = {
      id: plotId,
      action: 'update',
      plot_name: document.getElementById('edit-plot-name').value,
      title_deed_type: document.getElementById('edit-deed-type').value,
      title_deed_no: document.getElementById('edit-deed-no').value,
      rubber_clone: document.getElementById('edit-rubber-clone').value,
      planting_year: parseInt(document.getElementById('edit-planting-year').value),
      tree_count: parseInt(document.getElementById('edit-tree-count').value),
      tapping_status: document.getElementById('edit-tapping-status').value,
      notes: document.getElementById('edit-notes').value
    };

    try {
      App.showToast('กำลังบันทึกการแก้ไข...', 'info');
      const res = await fetch('api/plots.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success || res.ok) {
        App.showToast('🎉 บันทึกการแก้ไขข้อมูลแปลงปลูกเรียบร้อยแล้ว!', 'success');
        App.closeModal('editPlotModal');
        this.loadRubberPlots();
      } else {
        alert(data.message || 'ไม่สามารถบันทึกได้');
      }
    } catch (err) {
      alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
  },

  // Zoom into specific plot on map
  zoomToPlot(lat, lng, plotId) {
    this.map.flyTo([lat, lng], 17, { duration: 1.2 });
    this.highlightPlotInList(plotId);
    // Smoothly scroll up to the map viewport if clicked from below
    const mapContainer = document.getElementById('map-view');
    if (mapContainer) {
      mapContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  },

  highlightPlotInList(plotId) {
    document.querySelectorAll('.plot-card').forEach(card => card.classList.remove('active'));
    const activeCard = document.getElementById(`plot-card-${plotId}`);
    if (activeCard) {
      activeCard.classList.add('active');
    }
  },

  // Handle newly drawn polygon
  async handleDrawnPolygon(geojson) {
    App.showToast('กำลังวิเคราะห์พิกัดและการทับซ้อนแนวเขตป่าสงวน...', 'info');

    try {
      const res = await fetch('api/spatial_check.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          geojson: geojson.geometry,
          planting_year: document.getElementById('new-planting-year')?.value || 2018
        })
      });
      const checkResult = await res.json();

      // Open Plot Creation Modal & Populate fields
      this.populateNewPlotModal(geojson.geometry, checkResult);
      App.openModal('addPlotModal');

    } catch (e) {
      console.error('Spatial check error:', e);
      App.showToast('ไม่สามารถวิเคราะห์เชิงพื้นที่ได้ กรุณาลองใหม่อีกครั้ง', 'error');
    }
  },

  // Populate Add Plot Modal with calculated spatial properties
  populateNewPlotModal(geometry, check) {
    const geoInput = document.getElementById('form-geojson-geometry');
    if (geoInput) geoInput.value = JSON.stringify(geometry);
    
    // Coordinates & Centroid
    const latInput = document.getElementById('form-centroid-lat');
    const lngInput = document.getElementById('form-centroid-lng');
    const dispInput = document.getElementById('form-centroid-display');
    const dispLat = document.getElementById('modal-disp-lat');
    const dispLng = document.getElementById('modal-disp-lng');
    const dispArea = document.getElementById('modal-disp-area');
    const dispPoints = document.getElementById('modal-disp-points');

    const latVal = check.centroid ? check.centroid.lat : 9.138240;
    const lngVal = check.centroid ? check.centroid.lng : 99.321850;

    if (latInput) latInput.value = latVal;
    if (lngInput) lngInput.value = lngVal;
    if (dispInput) dispInput.value = `${latVal}, ${lngVal}`;
    if (dispLat) dispLat.innerText = typeof latVal === 'number' ? latVal.toFixed(6) : latVal;
    if (dispLng) dispLng.innerText = typeof lngVal === 'number' ? lngVal.toFixed(6) : lngVal;

    // Calculate Points Count
    let pointsCount = check.points_count || 0;
    if (!pointsCount && geometry && geometry.coordinates && geometry.coordinates[0]) {
      const coords = geometry.coordinates[0];
      if (coords.length > 1 && coords[0][0] === coords[coords.length - 1][0] && coords[0][1] === coords[coords.length - 1][1]) {
        pointsCount = coords.length - 1;
      } else {
        pointsCount = coords.length;
      }
    }
    if (dispPoints) {
      dispPoints.innerText = `Polygon ${pointsCount || 6} จุด`;
    }

    // Format Area in Rai
    if (dispArea && check.area_thai) {
      let areaText = `${check.area_thai.rai}`;
      if (check.area_thai.ngan > 0) {
        areaText += `.${Math.round(check.area_thai.ngan * 2.5)}`;
      } else if (check.area_thai.sqwah > 0 && check.area_thai.rai === 0) {
        areaText = (check.area_thai.sqwah / 400).toFixed(2);
      }
      dispArea.innerText = areaText;
    }

    // Populate Step 2 Real Calculations Banner
    const step2AreaText = document.getElementById('step2-area-text');
    const step2AreaHa = document.getElementById('step2-area-ha');
    const step2AreaSqm = document.getElementById('step2-area-sqm');
    const step2PointsBadge = document.getElementById('step2-points-badge');
    const step2EudrBadge = document.getElementById('step2-eudr-badge');
    const plotNameInput = document.getElementById('form-plot-name');

    if (check.area_thai) {
      if (step2AreaText) step2AreaText.innerText = check.area_thai.formatted || `${check.area_thai.rai} ไร่ ${check.area_thai.ngan} งาน`;
      if (step2AreaHa) step2AreaHa.innerText = check.area_thai.hectare ? check.area_thai.hectare.toFixed(4) : '0.0000';
      if (step2AreaSqm) step2AreaSqm.innerText = check.area_thai.sqm ? parseFloat(check.area_thai.sqm).toLocaleString() : '0';
    }
    if (step2PointsBadge) {
      step2PointsBadge.innerText = `Polygon ${pointsCount || 6} จุด`;
    }
    if (step2EudrBadge) {
      if (check.eudr_status === 'compliant') {
        step2EudrBadge.className = 'bg-emerald-100 text-emerald-800 font-bold text-[14px] px-3 py-1 rounded-full border border-emerald-300';
        step2EudrBadge.innerText = '🟢 ปลอดการตัดไม้ (EUDR ผ่าน)';
      } else {
        step2EudrBadge.className = 'bg-rose-100 text-rose-800 font-bold text-[14px] px-3 py-1 rounded-full border border-rose-300';
        step2EudrBadge.innerText = '🔴 เสี่ยงทับซ้อนป่าสงวน';
      }
    }

    // Smart default plot name with real area
    if (plotNameInput && (!plotNameInput.value || plotNameInput.value.includes('เขาท่าเพชร 1') || plotNameInput.value.includes('แปลงยางพารา'))) {
      const raiStr = check.area_thai ? `${check.area_thai.rai} ไร่ ${check.area_thai.ngan} งาน` : '';
      plotNameInput.value = `แปลงยางพารา ม.อ. (${raiStr})`;
    }

    // Direct jump to Step 2 (ข้อมูลแปลงปลูกและเกษตรกร) with real coordinates as requested
    if (typeof goToModalStep === 'function') {
      goToModalStep(2);
    }
    if (typeof setModalPresetMode === 'function') {
      setModalPresetMode(check.eudr_status === 'compliant' ? 'compliant' : 'overlap');
    }
  }
};

document.addEventListener('DOMContentLoaded', () => {
  GeoMap.init();
});
