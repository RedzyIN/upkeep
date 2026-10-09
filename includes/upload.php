<?php
// Mengembalikan [true, namaFile|null] kalau sukses, atau [false, pesanError].
// Pemakaian:  [$ok, $hasil] = uploadFoto($_FILES["foto"], "laporan");
function uploadFoto(array $file, string $prefix = "foto"): array {
  if ($file["error"] === UPLOAD_ERR_NO_FILE) return [true, null];      // upload bersifat opsional
  if ($file["error"] !== UPLOAD_ERR_OK)      return [false, "Upload gagal (kode {$file['error']})."];
  if ($file["size"] > 2 * 1024 * 1024)       return [false, "Ukuran foto maksimal 2 MB."];

  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file["tmp_name"]);   // cek isi file, bukan ekstensi
  $ext  = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"][$mime] ?? null;
  if (!$ext) return [false, "Format foto harus JPG, PNG, atau WebP."];

  $nama = $prefix . "_" . uniqid() . "." . $ext;                      // rename agar aman & unik
  if (!move_uploaded_file($file["tmp_name"], __DIR__ . "/../uploads/" . $nama)) {
    return [false, "Foto gagal disimpan. Periksa izin folder uploads/."];
  }
  return [true, $nama];
}

function hapusFoto(?string $nama) {
  if ($nama && is_file(__DIR__ . "/../uploads/" . $nama)) unlink(__DIR__ . "/../uploads/" . $nama);
}
