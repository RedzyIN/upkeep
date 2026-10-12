document.addEventListener("DOMContentLoaded", function () {
    const tabel = document.getElementById("tabelRekap");
    const tombolExcel = document.getElementById("btnExportExcel");
    const tombolPDF = document.getElementById("btnExportPDF");

    const inputMulai = document.getElementById("tanggalMulai");
    const inputAkhir = document.getElementById("tanggalAkhir");

    if (!tabel) {
        console.error("Tabel #tabelRekap tidak ditemukan.");
        return;
    }

    // Membuat nama file berdasarkan periode rekap.
    function namaFile(format) {
        const mulai = inputMulai?.value || "awal";
        const akhir = inputAkhir?.value || "akhir";

        return `rekap-laporan-${mulai}-sampai-${akhir}.${format}`;
    }

    // Memastikan tabel memiliki data laporan.
    function adaData() {
        const baris = tabel.querySelectorAll("tbody tr");

        if (baris.length === 0) {
            return false;
        }

        // Baris pesan kosong pada rekap menggunakan colspan.
        const barisKosong = baris[0].querySelector("td[colspan]");

        if (baris.length === 1 && barisKosong) {
            alert("Tidak ada data laporan untuk diekspor.");
            return false;
        }

        return true;
    }

    // Export Excel menggunakan SheetJS.
    tombolExcel?.addEventListener("click", function () {
        if (!adaData()) return;

        if (typeof XLSX === "undefined") {
            alert("Library Excel belum berhasil dimuat.");
            return;
        }

        try {
            const workbook = XLSX.utils.table_to_book(tabel, {
                sheet: "Rekap Laporan",
                raw: true      // jangan menebak tipe data: tanggal tetap teks seperti di tabel
            });

            XLSX.writeFile(workbook, namaFile("xlsx"));
        } catch (error) {
            console.error("Gagal mengekspor Excel:", error);
            alert("Gagal membuat file Excel.");
        }
    });

    // Export PDF menggunakan jsPDF dan AutoTable.
    tombolPDF?.addEventListener("click", function () {
        if (!adaData()) return;

        if (
            !window.jspdf ||
            typeof window.jspdf.jsPDF !== "function"
        ) {
            alert("Library PDF belum berhasil dimuat.");
            return;
        }

        try {
            const { jsPDF } = window.jspdf;

            // Landscape memberi ruang lebih untuk kolom tabel.
            const pdf = new jsPDF({
                orientation: "landscape",
                unit: "mm",
                format: "a4"
            });

            const mulai = inputMulai?.value || "-";
            const akhir = inputAkhir?.value || "-";

            pdf.setFontSize(16);
            pdf.text("Rekap Laporan Upkeep", 14, 15);

            pdf.setFontSize(10);
            pdf.text(
                `Periode: ${mulai} sampai ${akhir}`,
                14,
                22
            );

            pdf.autoTable({
                html: "#tabelRekap",
                startY: 29,
                theme: "grid",
                showHead: "everyPage",
                styles: {
                    fontSize: 8,
                    cellPadding: 3,
                    overflow: "linebreak"
                },
                headStyles: {
                    fillColor: [185, 28, 28],
                    textColor: 255
                },
                margin: {
                    left: 14,
                    right: 14
                }
            });

            pdf.save(namaFile("pdf"));
        } catch (error) {
            console.error("Gagal mengekspor PDF:", error);
            alert("Gagal membuat file PDF.");
        }
    });
});