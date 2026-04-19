/* ================================================
   assets/js/main.js — Frontend JS SEKALA
   ================================================ */

// ---- Smooth scroll untuk anchor ----
document.addEventListener('DOMContentLoaded', function () {

  // Smooth scroll ke section
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = this.getAttribute('href').slice(1);
      var el = document.getElementById(id);
      if (el) {
        e.preventDefault();
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // Porto slider pause on hover (sudah dihandle CSS)

  // Auto-hide flash alert setelah 5 detik
  var flash = document.getElementById('flash-alert');
  if (flash) {
    setTimeout(function () {
      flash.style.transition = 'opacity 0.5s ease';
      flash.style.opacity = '0';
      setTimeout(function () { flash.remove(); }, 500);
    }, 5000);
  }

  // Navbar active state berdasarkan scroll
  var sections = ['section-hero','section-how','section-paket','section-portofolio','section-kontak'];
  var navLinks  = document.querySelectorAll('.navbar-nav li');

  if (sections.length && navLinks.length) {
    window.addEventListener('scroll', function () {
      var current = '';
      sections.forEach(function (id) {
        var el = document.getElementById(id);
        if (el && window.scrollY >= el.offsetTop - 100) {
          current = id;
        }
      });
      navLinks.forEach(function (li, idx) {
        li.classList.toggle('active', idx === sections.indexOf(current));
      });
    });
  }

  // Gallery thumb click (untuk halaman detail)
  var thumbs = document.querySelectorAll('.gallery-thumb');
  thumbs.forEach(function (t, i) {
    t.addEventListener('click', function () {
      thumbs.forEach(function (x) { x.classList.remove('active'); });
      t.classList.add('active');
    });
  });
});

// ---- Smooth scroll fungsi (dipanggil dari onclick) ----
function smoothScroll(id) {
  var el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
  return false;
}
