<?php
  $u = ($paksaTamu ?? false) ? null : userLogin();
  $f = ambilFlash();      // pesan dari setFlash(), dikirim ke JavaScript lewat atribut data-*
?>
    </main>
<?php if ($u): ?>
  </div><!-- /konten -->
</div><!-- /flex -->
<?php endif; ?>

<?php if ($f): ?>
  <!-- Pesan flash: dibaca oleh app.js lalu ditampilkan sebagai toast -->
  <div id="flash" data-tipe="<?= e($f[0]) ?>" data-pesan="<?= e($f[1]) ?>" hidden></div>
  <?php endif; ?>

  <script src="<?= BASE_URL ?>assets/js/app.js"></script>
  <script src="<?= BASE_URL ?>assets/js/transisi.js"></script>
  <?php foreach ($jsHalaman ?? [] as $js): ?>
  <script src="<?= BASE_URL . e($js) ?>"></script>
  <?php endforeach; ?>
</body>
</html>