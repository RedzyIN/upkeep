
/* 1. Filter otomatis */
const pilihan = document.querySelectorAll("form[data-auto-submit] select");

pilihan.forEach(function(select) {
    select.addEventListener("change", function() {
        select.form.submit();
    });
});


/* 2. Pesan jika pencarian tidak menemukan hasil */
const kolomCari = document.querySelector("input[data-cari]");
const pesanKosong = document.getElementById("kosongCari");

if (kolomCari && pesanKosong) {
    kolomCari.addEventListener("input", function() {
        const tabel = document.querySelector("#tabelLaporan tbody");
        const baris = tabel.querySelectorAll("tr");

        let adaHasil = false;

        baris.forEach(function(tr) {
            if (!tr.classList.contains("hidden")) {
                adaHasil = true;
            }
        });

        if (adaHasil) {
            pesanKosong.classList.add("hidden");
        } else {
            pesanKosong.classList.remove("hidden");
        }
    });
}
