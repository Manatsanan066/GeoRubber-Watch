/**
 * GeoRubber Watch - Decision Support System (DSS) Charts Engine
 * Styled for Clean Eco-Minimalist Scandinavian Nature Theme
 * Typography: Open Sans, Google Sans, Sarabun
 */

// Global Chart.js Clean Defaults
if (typeof Chart !== 'undefined') {
  Chart.defaults.color = '#4a5568';
  Chart.defaults.borderColor = '#e6ede6';
  Chart.defaults.font.family = "'Open Sans', 'Google Sans', 'Sarabun', sans-serif";
}

const DSSCharts = {
  async init() {
    if (!document.getElementById('yieldTrendChart')) return;
    await this.loadDashboardData();
  },

  async loadDashboardData() {
    try {
      const res = await fetch('api/dashboard_stats.php');
      const data = await res.json();
      if (!data.success) return;

      this.renderKPIs(data.kpis);
      this.renderYieldTrendChart(data.charts.monthly_yields);
      this.renderCloneDistributionChart(data.charts.clone_distribution);
      this.renderEUDRComplianceChart(data.charts.eudr_status_breakdown);
      this.renderAgeDistributionChart(data.charts.age_distribution);
      this.renderAtRiskTable(data.at_risk_plots);

    } catch (e) {
      console.error('Error loading DSS dashboard stats:', e);
    }
  },

  renderKPIs(kpis) {
    const setVal = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.textContent = val;
    };

    setVal('kpi-total-plots', kpis.total_plots.toLocaleString());
    setVal('kpi-total-farmers', kpis.total_farmers.toLocaleString());
    setVal('kpi-total-area', `${kpis.total_area_rai.toLocaleString()} ไร่`);
    setVal('kpi-total-area-ha', `(${kpis.total_area_ha} เฮกตาร์)`);
    setVal('kpi-total-trees', `${kpis.total_trees.toLocaleString()} ต้น`);
    setVal('kpi-compliance-rate', `${kpis.eudr_compliance_rate}%`);
    setVal('kpi-latex-yield', `${kpis.total_fresh_latex_kg.toLocaleString()} กก.`);
    setVal('kpi-dry-rubber', `${kpis.total_dry_rubber_kg.toLocaleString()} กก.`);
    setVal('kpi-total-revenue', `฿${kpis.total_revenue_thb.toLocaleString()}`);
  },

  renderYieldTrendChart(monthlyYields) {
    const ctx = document.getElementById('yieldTrendChart')?.getContext('2d');
    if (!ctx) return;

    const labels = monthlyYields.length > 0 ? monthlyYields.map(m => m.harvest_month) : ['2026-05', '2026-06', '2026-07', '2026-08'];
    const freshLatex = monthlyYields.length > 0 ? monthlyYields.map(m => m.monthly_fresh_kg) : [1200, 1850, 2100, 2480];
    const dryRubber = monthlyYields.length > 0 ? monthlyYields.map(m => m.monthly_dry_kg) : [408, 629, 714, 843];

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'น้ำยางสด (Fresh Latex kg)',
            data: freshLatex,
            borderColor: '#245840',
            backgroundColor: 'rgba(36, 88, 64, 0.08)',
            fill: true,
            tension: 0.35,
            borderWidth: 3,
            pointBackgroundColor: '#245840',
            pointRadius: 4
          },
          {
            label: 'เนื้อยางแห้ง (Dry Rubber kg)',
            data: dryRubber,
            borderColor: '#3b7a57',
            backgroundColor: 'transparent',
            borderDash: [5, 5],
            tension: 0.35,
            borderWidth: 2.5,
            pointBackgroundColor: '#3b7a57',
            pointRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
            labels: { color: '#153224', font: { size: 12, weight: '700' } }
          }
        },
        scales: {
          x: {
            grid: { color: '#f1f5ee' },
            ticks: { color: '#6b7f73' }
          },
          y: {
            beginAtZero: true,
            title: { display: true, text: 'กิโลกรัม (kg)', color: '#6b7f73' },
            grid: { color: '#f1f5ee' },
            ticks: { color: '#6b7f73' }
          }
        }
      }
    });
  },

  renderCloneDistributionChart(clones) {
    const ctx = document.getElementById('cloneDistributionChart')?.getContext('2d');
    if (!ctx) return;

    const labels = clones.map(c => c.rubber_clone);
    const counts = clones.map(c => c.count);

    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: labels,
        datasets: [{
          data: counts,
          backgroundColor: ['#153224', '#3b7a57', '#8bbfa3', '#d97706', '#0284c7'],
          borderColor: '#ffffff',
          borderWidth: 3
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#153224', boxWidth: 12, font: { weight: '600' } }
          }
        },
        cutout: '68%'
      }
    });
  },

  renderEUDRComplianceChart(eudrBreakdown) {
    const ctx = document.getElementById('eudrComplianceChart')?.getContext('2d');
    if (!ctx) return;

    const labels = eudrBreakdown.map(e => e.status);
    const counts = eudrBreakdown.map(e => e.count);
    const colors = eudrBreakdown.map(e => e.color);

    new Chart(ctx, {
      type: 'pie',
      data: {
        labels: labels,
        datasets: [{
          data: counts,
          backgroundColor: colors,
          borderColor: '#ffffff',
          borderWidth: 3
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#153224', boxWidth: 12, font: { weight: '600' } }
          }
        }
      }
    });
  },

  renderAgeDistributionChart(ageData) {
    const ctx = document.getElementById('ageDistributionChart')?.getContext('2d');
    if (!ctx) return;

    const labels = ageData.map(a => a.label);
    const plotCounts = ageData.map(a => a.count);
    const raiCounts = ageData.map(a => a.rai);

    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'จำนวนแปลง (แปลง)',
            data: plotCounts,
            backgroundColor: '#153224',
            borderRadius: 8
          },
          {
            label: 'เนื้อที่รวม (ไร่)',
            data: raiCounts,
            backgroundColor: '#8bbfa3',
            borderRadius: 8
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top',
            labels: { color: '#153224', font: { weight: '700' } }
          }
        },
        scales: {
          x: {
            grid: { color: '#f1f5ee' },
            ticks: { color: '#6b7f73' }
          },
          y: {
            beginAtZero: true,
            grid: { color: '#f1f5ee' },
            ticks: { color: '#6b7f73' }
          }
        }
      }
    });
  },

  renderAtRiskTable(atRiskPlots) {
    const tbody = document.getElementById('at-risk-plots-tbody');
    if (!tbody) return;

    if (atRiskPlots.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; color:var(--success); padding:1.5rem; font-weight:700;">✅ ไม่พบแปลงที่เสี่ยงต่อการบุกรุกป่าสงวน ทุกแปลงสอดคล้องตามมาตรฐาน EUDR</td></tr>';
      return;
    }

    let html = '';
    atRiskPlots.forEach(p => {
      let badge = '<span class="badge badge-non_compliant">⛔ บุกรุก/ทับซ้อน</span>';
      if (p.eudr_status === 'under_review') badge = '<span class="badge badge-under_review">⚠️ โซนเฝ้าระวัง</span>';

      html += `
        <tr style="border-bottom: 1px solid var(--border-subtle); transition: background 0.2s;" onmouseover="this.style.background='var(--sage-50)'" onmouseout="this.style.background='transparent'">
          <td style="padding: 12px 16px;"><strong style="color: var(--pine-900); font-family: monospace;">${p.plot_code}</strong></td>
          <td style="padding: 12px 16px; font-weight: 700; color: var(--pine-900);">${p.plot_name}</td>
          <td style="padding: 12px 16px; color: var(--text-body);">${p.prefix}${p.first_name} ${p.last_name}</td>
          <td style="padding: 12px 16px; color: var(--text-body);">${p.area_rai} ไร่ ${p.area_ngan} งาน</td>
          <td style="padding: 12px 16px;"><span style="color:var(--danger); font-weight:700;">${p.eudr_overlap_pct}%</span></td>
          <td style="padding: 12px 16px;">${badge}</td>
          <td style="padding: 12px 16px; text-align: center;">
            <a href="trace.php?token=${p.traceability_token}" target="_blank" class="btn btn-outline btn-sm">
              🛡️ ตรวจสอบ
            </a>
          </td>
        </tr>
      `;
    });

    tbody.innerHTML = html;
  }
};

document.addEventListener('DOMContentLoaded', () => {
  DSSCharts.init();
});
