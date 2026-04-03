/* global HOTEL_UTIL, flatpickr */
(function(){
  const form = document.getElementById("searchForm");
  const resultBox = document.getElementById("result");
  const countBox = document.getElementById("resultCount"); // HTML側に合わせる
  const pills = document.getElementById("pills");

  // APIのパスを構築
  const API = `${HOTEL_UTIL.apiBase}/availability.php`;

  function fillFromQuery(){
    const q = HOTEL_UTIL.qso();
    const inEl = document.getElementById("checkin");
    const outEl = document.getElementById("checkout");
    const gEl = form.querySelector('select[name="guests"]');
    const tEl = form.querySelector('select[name="type"]');

    if (q.checkin && inEl) inEl.value = q.checkin;
    if (q.checkout && outEl) outEl.value = q.checkout;
    if (q.guests && gEl) gEl.value = q.guests;
    if (q.type != null && tEl) tEl.value = q.type;
  }

  function ensurePickers(){
    const inEl = document.getElementById("checkin");
    const outEl = document.getElementById("checkout");
    if (!inEl || !outEl || typeof flatpickr === "undefined") return;

    const today = new Date();
    const tomorrow = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);

    let outPicker;
    const inPicker = flatpickr(inEl, {
      locale: "ja", minDate: today, dateFormat: "Y-m-d", disableMobile: true,
      onChange(selectedDates){
        if (!selectedDates[0] || !outPicker) return;
        const minOut = new Date(selectedDates[0].getTime() + 86400000);
        outPicker.set("minDate", minOut);
        if (!outPicker.input.value) outPicker.open();
      }
    });
    outPicker = flatpickr(outEl, { locale: "ja", minDate: tomorrow, dateFormat: "Y-m-d", disableMobile: true });

    if (!inEl.value) inPicker.setDate(today, true);
    if (!outEl.value) outPicker.setDate(tomorrow, true);
  }

  function render(items, q){
    // itemsが空、またはundefinedの場合の処理
    const list = items || [];
    
    if (countBox) countBox.textContent = `${list.length}件`;

    if (!list.length){
      resultBox.innerHTML = `<div class="card" style="grid-column:1/-1;padding:16px"><p class="lead" style="margin:0">空室がありません。条件を変更して再検索してください。</p></div>`;
      return;
    }

    resultBox.innerHTML = list.map(r => {
      // データベースのカラム名（image_url等）を、表示用の変数にマッピング
      const img = r.image || "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?q=80&w=1600&auto=format&fit=crop";
      const name = r.name || "客室";
      const price = Number(r.price || 0).toLocaleString();
      const cap = r.capacity || 2;

      // 予約ボタン用のURLパラメータを生成
      const bookParams = new URLSearchParams({
        id: r.id,
        checkin: q.checkin,
        checkout: q.checkout,
        guests: q.guests
      }).toString();

      return `
        <article class="card">
          <img src="${img}" alt="${name}" loading="lazy">
          <div class="body">
            <h3>${name.toUpperCase()}</h3>
            <p class="meta">定員 ${cap} 名 ・ ¥${price} / 泊</p>
            <a class="btn gold" href="booking.html?${bookParams}">この部屋を予約</a>
          </div>
        </article>
      `;
    }).join("");
  }

  async function fetchAndRender(){
    const fd = new FormData(form);
    const q = Object.fromEntries(fd.entries());

    // URLを更新して、ブラウザの「戻る」などで検索条件を維持できるようにする
    history.replaceState(null, "", `search.html?${new URLSearchParams(q).toString()}`);

    resultBox.innerHTML = '<div class="box">検索中…</div>';
    if (countBox) countBox.textContent = "…";

    try{
      const res = await fetch(`${API}?${new URLSearchParams(q).toString()}`);
      
      if (!res.ok) {
          const errData = await res.json().catch(() => ({}));
          throw new Error(errData.detail || 'Server Error');
      }

      const data = await res.json();
      // PHP側が 'rooms' というキーでデータを返しているので、それに合わせる
      render(data.rooms || [], q);

    } catch(e) {
      console.error("Fetch error:", e);
      resultBox.innerHTML = `<div class="card" style="grid-column:1/-1;padding:16px"><p style="color:red">検索に失敗しました: ${e.message}</p></div>`;
    }
  }

  if (form){
    fillFromQuery();
    ensurePickers();
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      fetchAndRender();
    });
    // 初回読み込み時に検索を実行
    fetchAndRender();
  }
})();