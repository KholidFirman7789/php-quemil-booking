<?php
/**
 * Partial: Footer + Global JS (dark mode + sidebar)
 */
?>
<footer class="footer pt-5 pb-3 mt-5" id="kontak">
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
            <a href="https://wa.me/6281234567890" target="_blank" rel="noopener"
               class="text-muted text-decoration-none">
              <i class="bi bi-whatsapp text-success me-1"></i>WhatsApp
            </a>
          </li>
          <li><i class="bi bi-clock text-rose me-1"></i>Senin &ndash; Minggu, 06:00 &ndash; 17:00 WIB</li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary mt-4">
    <p class="text-center text-muted small mb-0">
      &copy; <?= date('Y') ?> <?= APP_NAME ?>. Sistem Informasi Booking Berbasis Web.
    </p>
  </div>
</footer>

<!-- WhatsApp Float -->
<a href="https://wa.me/6281234567890?text=Halo+Quemil+Makeup,+saya+ingin+bertanya."
   class="whatsapp-float" target="_blank" rel="noopener" title="Chat WhatsApp">
  <i class="bi bi-whatsapp"></i>
</a>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- AOS - Animate On Scroll -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 700,
    easing: 'ease-out-cubic',
    once: true,
    offset: 60
  });
</script>

<script>
(function(){
  'use strict';

  /* =======================================================
     DARK MODE
     ======================================================= */
  var THEME_KEY = 'qm_theme';

  function getTheme(){
    return localStorage.getItem(THEME_KEY) || 'light';
  }

  function applyTheme(theme){
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(THEME_KEY, theme);
    var isDark = theme === 'dark';
    document.querySelectorAll('[id^="themeIcon"], #sidebarThemeIcon').forEach(function(el){
      el.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
    });
    document.querySelectorAll('#sidebarThemeLabel').forEach(function(el){
      el.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    });
  }

  function toggleTheme(){ applyTheme(getTheme() === 'dark' ? 'light' : 'dark'); }

  applyTheme(getTheme());

  document.getElementById('themeToggleBtn') && document.getElementById('themeToggleBtn').addEventListener('click', toggleTheme);
  document.getElementById('sidebarThemeBtn') && document.getElementById('sidebarThemeBtn').addEventListener('click', toggleTheme);

  /* =======================================================
     SIDEBAR (admin pages only)
     ======================================================= */
  var sidebar     = document.getElementById('adminSidebar');
  var backdrop    = document.getElementById('sidebarBackdrop');
  var closeBtn    = document.getElementById('sidebarCloseBtn');
  var collapseBtn = document.getElementById('sidebarCollapseBtn');
  var collapseIcon= document.getElementById('collapseIcon');

  if(sidebar){

    /* --- Mobile open/close --- */
    function openMobile(){
      sidebar.classList.add('mobile-open');
      if(backdrop) backdrop.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
    function closeMobile(){
      sidebar.classList.remove('mobile-open');
      if(backdrop) backdrop.classList.remove('show');
      document.body.style.overflow = '';
    }

    if(closeBtn) closeBtn.addEventListener('click', closeMobile);
    if(backdrop) backdrop.addEventListener('click', closeMobile);

    // Hamburger buttons that open sidebar
    document.querySelectorAll('.btn-sidebar-open').forEach(function(btn){
      btn.addEventListener('click', openMobile);
    });

    // Close on nav link click (mobile)
    sidebar.querySelectorAll('a.nav-link').forEach(function(link){
      link.addEventListener('click', function(){
        if(window.innerWidth < 992) closeMobile();
      });
    });

    /* --- Desktop collapse --- */
    var COLLAPSED_KEY = 'qm_sidebar_collapsed';

    function applyCollapse(collapsed){
      sidebar.classList.toggle('collapsed', collapsed);
      if(collapseIcon) collapseIcon.className = collapsed ? 'bi bi-layout-sidebar-reverse' : 'bi bi-layout-sidebar';
      localStorage.setItem(COLLAPSED_KEY, collapsed ? '1' : '0');
    }

    if(window.innerWidth >= 992){
      applyCollapse(localStorage.getItem(COLLAPSED_KEY) === '1');
    }

    if(collapseBtn){
      collapseBtn.addEventListener('click', function(){
        applyCollapse(!sidebar.classList.contains('collapsed'));
      });
    }

    window.addEventListener('resize', function(){
      if(window.innerWidth >= 992) closeMobile();
    });
  }

})();
</script>

<?php if(isset($extraJs)) echo $extraJs; ?>

<script>
(function(){
  'use strict';

  /* =======================================================
     NAVBAR SCROLL EFFECT
     ======================================================= */
  var navbar = document.getElementById('mainNavbar');
  if(navbar){
    function onScroll(){
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* =======================================================
     COUNTER ANGKA (stats banner)
     ======================================================= */
  var counters = document.querySelectorAll('[data-counter]');
  if(counters.length){
    var counted = false;
    function runCounters(){
      if(counted) return;
      var first = counters[0].getBoundingClientRect();
      if(first.top > window.innerHeight) return;
      counted = true;
      counters.forEach(function(el){
        var target  = parseInt(el.getAttribute('data-counter'), 10);
        var suffix  = el.getAttribute('data-suffix') || '';
        var duration = 1400;
        var start    = performance.now();
        function step(now){
          var progress = Math.min((now - start) / duration, 1);
          var ease     = 1 - Math.pow(1 - progress, 3); /* ease-out-cubic */
          el.textContent = Math.floor(ease * target) + suffix;
          if(progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
      });
    }
    window.addEventListener('scroll', runCounters, { passive: true });
    runCounters();
  }

})();
</script>
</body>
</html>
