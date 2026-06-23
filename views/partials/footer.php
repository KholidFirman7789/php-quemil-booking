<?php
/**
 * Partial: Footer
 */
?>
<footer class="footer bg-dark text-white pt-5 pb-3 mt-5" id="kontak">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="brand-logo">Q</span>
          <span class="brand-name text-white">Quemil <span class="text-rose">Makeup</span></span>
        </div>
        <p class="text-muted small">
          Jasa makeup artist profesional dengan hasil riasan terbaik dan harga terjangkau.
          Melayani wisuda, pernikahan, karnaval, dan berbagai acara spesial Anda.
        </p>
      </div>
      <div class="col-lg-4">
        <h6 class="text-white mb-3">Lokasi Studio</h6>
        <address class="text-muted small mb-0">
          <i class="bi bi-geo-alt-fill text-rose me-1"></i>
          Dusun Sawi RT 08 RW 02,<br>
          Desa Sawiji, Kec. Jogoroto,<br>
          Kabupaten Jombang, Jawa Timur
        </address>
      </div>
      <div class="col-lg-4">
        <h6 class="text-white mb-3">Hubungi Kami</h6>
        <ul class="list-unstyled text-muted small">
          <li class="mb-2">
            <a href="https://wa.me/6281234567890" target="_blank"
               class="text-muted text-decoration-none">
              <i class="bi bi-whatsapp text-success me-1"></i> WhatsApp
            </a>
          </li>
          <li>
            <i class="bi bi-clock text-rose me-1"></i>
            Senin &ndash; Minggu, 06:00 &ndash; 17:00 WIB
          </li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary mt-4">
    <p class="text-center text-muted small mb-0">
      &copy; <?= date('Y') ?> <?= APP_NAME ?>. Sistem Informasi Booking Berbasis Web.
    </p>
  </div>
</footer>

<!-- WhatsApp Floating Button -->
<a href="https://wa.me/6281234567890?text=Halo+Quemil+Makeup,+saya+ingin+bertanya+tentang+layanan+makeup."
   class="whatsapp-float" target="_blank" rel="noopener" title="Chat WhatsApp">
  <i class="bi bi-whatsapp"></i>
</a>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
