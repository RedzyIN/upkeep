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

    function isiTeks(id, nilai) {
        const elemen = document.getElementById(id);
        if (elemen) elemen.textContent = nilai || "-";
    }
 
    function bukaPopup(tombol) {
        const data = tombol.dataset;
        const mode = data.mode || "detail";

        // Isi informasi laporan
        isiTeks("detailJudul", data.judul);
        isiTeks("detailPelapor", data.pelapor);
        isiTeks("detailTanggal", data.tanggal);
        isiTeks("detailKategori", data.kategori);
        isiTeks("detailPrioritas", data.prioritas);
        isiTeks("detailStatus", data.status);
        isiTeks("detailLokasi", data.lokasi);
        isiTeks("detailDeskripsi", data.deskripsi);

        // Sembunyikan semua bagian terlebih dahulu
        popupDetail.classList.add("hidden");
        formEdit.classList.add("hidden");
        formTangani.classList.add("hidden");
        popupFooter.classList.add("hidden");

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
        }

        popup.classList.remove("hidden");
        popup.classList.add("flex");
    }

    function tutupPopup() {
        popup.classList.add("hidden");
        popup.classList.remove("flex");
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

    // Mode demo: perubahan belum disimpan ke database
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
