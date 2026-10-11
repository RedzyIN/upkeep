document.addEventListener("DOMContentLoaded", function () {
    const popup = document.getElementById("popupLaporan");

    if (!popup) {
        console.error("Elemen #popupLaporan tidak ditemukan.");
        return;
    }

    const popupJudul = document.getElementById("popupJudul");
    const popupSubjudul = document.getElementById("popupSubjudul");
    const popupDetail = document.getElementById("popupDetail");
    const formEdit = document.getElementById("formEdit");
    const formTangani = document.getElementById("formTangani");
    const popupFooter = document.getElementById("popupFooter");

    const timelineMemuat = document.getElementById("timelineMemuat");
    const timelineDemo = document.getElementById("timelineDemo");
    const timelineStatus = document.getElementById("timelineStatus");
    const timelineKosong = document.getElementById("timelineKosong");

    let permintaanTimeline = null;

    function isiTeks(id, nilai) {
        const elemen = document.getElementById(id);
        if (elemen) elemen.textContent = nilai || "-";
    }

    function resetTimeline() {
        if (permintaanTimeline) {
            permintaanTimeline.abort();
            permintaanTimeline = null;
        }

        timelineMemuat.classList.remove("hidden");
        timelineMemuat.textContent = "Memuat riwayat status...";
        timelineDemo.classList.add("hidden");
        timelineStatus.classList.add("hidden");
        timelineStatus.replaceChildren();
        timelineKosong.classList.add("hidden");
    }

    function formatTanggal(nilai) {
        if (!nilai) return "Waktu tidak tersedia";

        const tanggal = new Date(nilai.replace(" ", "T"));

        if (Number.isNaN(tanggal.getTime())) return nilai;

        return new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            hourCycle: "h23"
        }).format(tanggal);
    }

    function labelStatus(status) {
        const label = {
            dilaporkan: "Dilaporkan",
            diproses: "Diproses",
            selesai: "Selesai"
        };

        return label[status] || status || "Status tidak diketahui";
    }

    function warnaStatus(status) {
        const warna = {
            dilaporkan: "bg-amber-500",
            diproses: "bg-sky-500",
            selesai: "bg-emerald-500"
        };

        return warna[status] || "bg-slate-400";
    }

    function tampilkanTimeline(riwayat, demo = false) {
        timelineMemuat.classList.add("hidden");
        timelineStatus.replaceChildren();
        timelineDemo.classList.toggle("hidden", !demo);

        if (!Array.isArray(riwayat) || riwayat.length === 0) {
            timelineStatus.classList.add("hidden");
            timelineKosong.classList.remove("hidden");
            return;
        }

        timelineKosong.classList.add("hidden");
        timelineStatus.classList.remove("hidden");

        riwayat.forEach(function (item) {
            const li = document.createElement("li");
            li.className = "relative";

            const titik = document.createElement("span");
            titik.className =
                "absolute -left-[25px] top-1 h-3 w-3 rounded-full border-2 border-white " +
                warnaStatus(item.status);

            const judul = document.createElement("p");
            judul.className = "text-sm font-semibold text-slate-900";
            judul.textContent = labelStatus(item.status);

            const waktu = document.createElement("time");
            waktu.className = "mt-1 block text-xs text-slate-500";
            waktu.textContent = formatTanggal(item.created_at);

            if (item.created_at) {
                waktu.dateTime = item.created_at.replace(" ", "T");
            }

            li.append(titik, judul, waktu);

            if (item.catatan) {
                const catatan = document.createElement("p");
                catatan.className =
                    "mt-2 whitespace-pre-line text-sm leading-6 text-slate-600";
                catatan.textContent = item.catatan;
                li.appendChild(catatan);
            }

            timelineStatus.appendChild(li);
        });
    }

    async function muatTimeline(id, statusSaatIni) {
        resetTimeline();

        // ID harus berupa bilangan bulat positif.
        if (!/^[1-9]\d*$/.test(String(id || ""))) {
            tampilkanTimeline([]);
            return;
        }

        const controller = new AbortController();
        permintaanTimeline = controller;

        try {
            const response = await fetch(
                "riwayat_status_api.php?id=" + encodeURIComponent(id),
                {
                    headers: { Accept: "application/json" },
                    signal: controller.signal
                }
            );

            if (!response.ok) {
                const error = new Error("Gagal mengambil riwayat status.");
                error.status = response.status;
                throw error;
            }

            const hasil = await response.json();

            if (controller.signal.aborted) return;

            if (!hasil.ok) {
                throw new Error(hasil.pesan || "Riwayat tidak tersedia.");
            }

            tampilkanTimeline(hasil.riwayat, hasil.demo === true);
        } catch (error) {
        if (error.name === "AbortError") return;

        console.error("Gagal memuat timeline:", error);

        // ID laporan demo belum tentu ada di database.
        if (error.status === 404 || error.status === 503) {
            tampilkanTimeline(
                [{
                    status: statusSaatIni,
                    created_at: "",
                    catatan: "Riwayat asli belum tersedia pada data demo."
                }],
                true
            );
            return;
        }

        timelineMemuat.textContent =
            "Riwayat gagal dimuat. Silakan coba lagi.";
        timelineDemo.classList.add("hidden");
        } finally {
            if (permintaanTimeline === controller) {
                permintaanTimeline = null;
            }
        }
    }

    function bukaPopup(tombol) {
        const data = tombol.dataset;
        const mode = data.mode || "detail";

        isiTeks("detailJudul", data.judul);
        isiTeks("detailPelapor", data.pelapor);
        isiTeks("detailTanggal", data.tanggal);
        isiTeks("detailKategori", data.kategori);
        isiTeks("detailPrioritas", data.prioritas);
        isiTeks("detailStatus", labelStatus(data.status));
        isiTeks("detailLokasi", data.lokasi);
        isiTeks("detailDeskripsi", data.deskripsi);

        popupDetail.classList.add("hidden");
        formEdit.classList.add("hidden");
        formTangani.classList.add("hidden");
        popupFooter.classList.add("hidden");

        resetTimeline();

        if (mode === "edit") {
            popupJudul.textContent = "Edit Laporan";
            popupSubjudul.textContent = "Ubah informasi laporan";

            document.getElementById("editId").value = data.id || "";
            document.getElementById("editJudul").value = data.judul || "";
            document.getElementById("editLokasi").value = data.lokasi || "";
            document.getElementById("editKategori").value = data.kategori || "";
            document.getElementById("editPrioritas").value =
                data.prioritas || "sedang";
            document.getElementById("editDeskripsi").value =
                data.deskripsi || "";

            formEdit.classList.remove("hidden");

        } else if (mode === "tangani") {
            popupJudul.textContent = "Tangani Laporan";
            popupSubjudul.textContent = "Perbarui status laporan";

            isiTeks("tanganiJudul", data.judul);
            document.getElementById("tanganiId").value = data.id || "";
            document.getElementById("tanganiStatus").value =
                data.status || "dilaporkan";

            formTangani.classList.remove("hidden");

        } else {
            popupJudul.textContent = "Detail Laporan";
            popupSubjudul.textContent = "Informasi laporan kerusakan";

            popupDetail.classList.remove("hidden");
            popupFooter.classList.remove("hidden");

            muatTimeline(data.id, data.status);
        }

        popup.classList.remove("hidden");
        popup.classList.add("flex");
    }

    function tutupPopup() {
        popup.classList.add("hidden");
        popup.classList.remove("flex");

        if (permintaanTimeline) {
            permintaanTimeline.abort();
            permintaanTimeline = null;
        }
    }

    document.querySelectorAll("[data-popup]").forEach(function (tombol) {
        tombol.addEventListener("click", function () {
            bukaPopup(tombol);
        });
    });

    document.getElementById("popupTutup")
        ?.addEventListener("click", tutupPopup);

    document.querySelectorAll("[data-popup-batal]").forEach(function (tombol) {
        tombol.addEventListener("click", tutupPopup);
    });

    popup.addEventListener("click", function (event) {
        if (event.target === popup) tutupPopup();
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") tutupPopup();
    });

    // Mode demo: perubahan belum disimpan ke database.
    formTangani.addEventListener("submit", function (event) {
        event.preventDefault();
        alert("Demo berhasil: status laporan sudah diuji. Perubahan belum tersimpan.");
        tutupPopup();
    });

    formEdit.addEventListener("submit", function (event) {
        event.preventDefault();

        const judul = document.getElementById("editJudul").value.trim();
        const lokasi = document.getElementById("editLokasi").value.trim();

        if (!judul || !lokasi) {
            alert("Judul dan lokasi wajib diisi.");
            return;
        }

        alert(
            "Demo Edit berhasil diuji.\n" +
            "Judul: " + judul + "\n" +
            "Lokasi: " + lokasi + "\n\n" +
            "Perubahan belum tersimpan ke database."
        );

        tutupPopup();
    });
});