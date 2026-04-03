/* global ROOM_ASSETS */
(function(){
  // Footer year
  const y = document.getElementById("y");
  if (y) y.textContent = new Date().getFullYear();

  // Hero slideshow (only if exists)
  const slides = Array.from(document.querySelectorAll(".hero .slide"));
  if (slides.length >= 2){
    let idx = 0;
    setInterval(() => {
      slides[idx].classList.remove("show");
      idx = (idx + 1) % slides.length;
      slides[idx].classList.add("show");
    }, 6000);
  }

  // Reveal
  const revealTargets = document.querySelectorAll(".reveal");
  if (revealTargets.length){
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting){
          e.target.classList.add("in");
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.18 });
    revealTargets.forEach(el => io.observe(el));
  }

  // Mobile menu
  const overlay = document.getElementById("overlay");
  const openMenu = document.getElementById("openMenu");
  if (overlay && openMenu){
    openMenu.addEventListener("click", () => {
      overlay.classList.add("open");
      overlay.setAttribute("aria-hidden", "false");
    });
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay){
        overlay.classList.remove("open");
        overlay.setAttribute("aria-hidden", "true");
      }
    });
  }

  // Smooth scroll for in-page anchors
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener("click", (e) => {
      const id = a.getAttribute("href").slice(1);
      const el = document.getElementById(id);
      if (el){
        e.preventDefault();
        el.scrollIntoView({ behavior: "smooth", block:"start" });
        if (overlay) overlay.classList.remove("open");
      }
    });
  });

  // Small helper utilities
  window.HOTEL_UTIL = {
    qs: (k) => new URLSearchParams(location.search).get(k),
    qso: () => Object.fromEntries(new URLSearchParams(location.search).entries()),
    setStatus: (el, html) => { if (el) el.innerHTML = html; },
    apiBase: "../hotel-admin/api",
    typeLabel: (code) => ({
      single:"シングル", double:"ダブル", twin:"ツイン", deluxe:"デラックス", suite:"スイート"
    }[code] || code),
    assetFor: (code) => (window.ROOM_ASSETS && window.ROOM_ASSETS[code]) ? window.ROOM_ASSETS[code] : null,
    nights: (checkin, checkout) => {
      const a = new Date(checkin);
      const b = new Date(checkout);
      const diff = (b - a) / 86400000;
      return isFinite(diff) ? Math.max(1, Math.round(diff)) : 1;
    }
  };
})();
