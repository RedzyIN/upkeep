// Menggambar grafik dashboard dengan ApexCharts.
// Data dikirim dari dashboard.php lewat window.DASHBOARD.
(function () {
  const D = window.DASHBOARD;
  if (!D || !window.ApexCharts) return;

  const admin = D.role === "admin";
  const animasi = !window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const WARNA = {
    dilaporkan: "#f59e0b",   // kuning (sama dengan badge status)
    diproses:   "#0ea5e9",   // biru
    selesai:    "#10b981",   // hijau
    utama:      "#dc2626"    // merah (sama dengan tombol utama)
  };

  // Pengaturan dasar yang sama untuk semua grafik
  function dasar(jenis) {
    return {
      type: jenis,
      height: 260,
      fontFamily: '"Instrument Sans", system-ui, sans-serif',
      toolbar: { show: false },
      animations: { enabled: animasi }
    };
  }

  // Sumbu Y hanya menampilkan bilangan bulat (jumlah laporan tidak mungkin 0,5)
  const yBulat = { min: 0, labels: { formatter: v => (Number.isInteger(v) ? v : "") } };

  function gambar(idElemen, opsi) {
    const el = document.getElementById(idElemen);
    if (el) new ApexCharts(el, opsi).render();
  }

  // 1) Donut status (user & admin)
  gambar("chartStatus", {
    chart: dasar("donut"),
    series: D.status.data,
    labels: D.status.labels,
    colors: [WARNA.dilaporkan, WARNA.diproses, WARNA.selesai],
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    legend: { position: "bottom", markers: { size: 6 } },
    plotOptions: {
      pie: { donut: { size: "70%", labels: {
        show: true,
        total: { show: true, label: "Total", formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0) }
      } } }
    }
  });

  // 2) Laporan per bulan: admin = area (garis), user = batang
  gambar("chartBulanan", {
    chart: dasar(admin ? "area" : "bar"),
    series: [{ name: "Laporan", data: D.bulanan.data }],
    colors: [WARNA.utama],
    xaxis: { categories: D.bulanan.labels, axisBorder: { show: false }, axisTicks: { show: false } },
    yaxis: yBulat,
    stroke: admin ? { curve: "smooth", width: 3 } : { width: 0 },
    fill: admin
      ? { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] } }
      : { opacity: 1 },
    markers: { size: admin ? 4 : 0 },
    plotOptions: { bar: { borderRadius: 6, columnWidth: "45%" } },
    dataLabels: { enabled: false },
    grid: { borderColor: "#e2e8f0", strokeDashArray: 4 },
    tooltip: { y: { formatter: v => v + " laporan" } }
  });

  // 3) Laporan per kategori (khusus admin): batang horizontal
  gambar("chartKategori", {
    chart: dasar("bar"),
    series: [{ name: "Laporan", data: D.kategori ? D.kategori.data : [] }],
    colors: [WARNA.utama],
    xaxis: { categories: D.kategori ? D.kategori.labels : [] },
    plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: "55%" } },
    dataLabels: { enabled: false },
    grid: { borderColor: "#e2e8f0", strokeDashArray: 4 },
    tooltip: { y: { formatter: v => v + " laporan" } }
  });
})();