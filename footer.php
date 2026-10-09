<?php
  $u = userLogin();
  $f = ambilFlash();      // pesan dari setFlash(), tampil sebagai toast
?>
<?php if ($u): ?>
  </div><!-- /konten -->
</div><!-- /flex -->
<?php endif; ?>

  <script src="/assets/js/app.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    lucide.createIcons();
  </script>
</body>
</html>
