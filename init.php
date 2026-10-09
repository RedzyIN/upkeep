<?php
  // File pusat: cukup `require "init.php";` di baris pertama setiap halaman.
  session_start();
  ob_start();   // tahan output supaya header("Location: ...") aman walau HTML sudah tercetak

  require_once __DIR__ . "/koneksi.php";

  // Muat semua file di folder includes/ otomatis (tinggal taruh file baru di sana)
  foreach (glob(__DIR__ . "/includes/*.php") as $file) {
    require_once $file;
  }
