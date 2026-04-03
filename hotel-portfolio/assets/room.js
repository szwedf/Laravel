/* global HOTEL_UTIL */
(async function(){
  const type = HOTEL_UTIL.qs("type") || "double";
  const API = `${HOTEL_UTIL.apiBase}/room.php?${new URLSearchParams({ type }).toString()}`;

  const heroImg = document.getElementById("heroImg");
  const titleEl = document.getElementById("roomTitle");
  const descEl = document.getElementById("roomDesc");
  const priceEl = document.getElementById("priceFrom");
  const capEl = document.getElementById("cap");
  const badgesEl = document.getElementById("badges");
  const thumbsEl = document.getElementById("thumbs");
  const bookBtn = document.getElementById("bookBtn");

  const q = HOTEL_UTIL.qso();
  const asset = HOTEL_UTIL.assetFor(type);

  // Visuals first (even if API fails)
  if (heroImg) heroImg.src = asset?.hero || asset?.thumb || "";
  if (titleEl) titleEl.textContent = HOTEL_UTIL.typeLabel(type);
  if (descEl) descEl.textContent = "上質な設えと静けさ。滞在の時間そのものを贅沢に。";
  if (thumbsEl && asset?.gallery){
    thumbsEl.innerHTML = asset.gallery.map(u => `<img src="${u}" alt="" loading="lazy">`).join("");
  }
  if (bookBtn){
    const qp = { ...q, type };
    bookBtn.href = `booking.html?${new URLSearchParams(qp).toString()}`;
  }

  try{
    const res = await fetch(API, { credentials:"include" });
    const data = await res.json();
    if (!data.ok) return;

    const item = data.item;
    if (titleEl && item.name_jp) titleEl.textContent = item.name_jp;
    if (descEl && item.description) descEl.textContent = item.description;
    if (priceEl && item.price_from != null) priceEl.textContent = `¥${Number(item.price_from).toLocaleString()}〜 / 泊`;
    if (capEl && item.max_capacity) capEl.textContent = `定員 ${item.max_capacity}`;

    if (badgesEl){
      const tags = [
        item.default_bed ? `ベッド: ${item.default_bed}` : null,
        "Wi‑Fi無料",
        "禁煙ルーム",
        "バス・シャワー別（タイプにより）"
      ].filter(Boolean);
      badgesEl.innerHTML = tags.map(t => `<span class="badge">${t}</span>`).join("");
    }

  }catch(e){
    // ignore (page still usable)
  }

  // Lightbox
  const lb = document.getElementById("lightbox");
  const lbImg = document.getElementById("lightboxImg");
  if (lb && lbImg && thumbsEl){
    thumbsEl.addEventListener("click", (e) => {
      const img = e.target.closest("img");
      if (!img) return;
      lbImg.src = img.src;
      lb.classList.add("open");
    });
    lb.addEventListener("click", () => lb.classList.remove("open"));
  }
})();
