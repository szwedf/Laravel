/* global HOTEL_UTIL, flatpickr */
(async function(){
  const type = HOTEL_UTIL.qs("type") || "double";
  const q = HOTEL_UTIL.qso();
  const API_ROOM = `${HOTEL_UTIL.apiBase}/room.php?${new URLSearchParams({ type }).toString()}`;
  const API_RESERVE = `${HOTEL_UTIL.apiBase}/reserve.php`;

  const titleEl = document.getElementById("roomTitle");
  const dateEl = document.getElementById("dateRange");
  const nightsEl = document.getElementById("nights");
  const priceEl = document.getElementById("price");
  const totalEl = document.getElementById("total");
  const msgEl = document.getElementById("msg");

  const inEl = document.getElementById("checkin");
  const outEl = document.getElementById("checkout");

  function ensurePickers(){
    if (!inEl || !outEl || typeof flatpickr === "undefined") return;

    const today = new Date();
    const tomorrow = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);

    let outPicker;
    const inPicker = flatpickr(inEl, {
      locale: "ja",
      minDate: today,
      dateFormat: "Y-m-d",
      disableMobile: true,
      onChange(selectedDates){
        if (!selectedDates[0] || !outPicker) return;
        const minOut = new Date(selectedDates[0].getTime() + 86400000);
        outPicker.set("minDate", minOut);
        if (!outPicker.input.value) outPicker.open();
      }
    });
    outPicker = flatpickr(outEl, {
      locale: "ja",
      minDate: tomorrow,
      dateFormat: "Y-m-d",
      disableMobile: true
    });

    // Prefill from query
    if (q.checkin) inPicker.setDate(q.checkin, true);
    if (q.checkout) outPicker.setDate(q.checkout, true);

    if (!inEl.value) inPicker.setDate(today, true);
    if (!outEl.value) outPicker.setDate(tomorrow, true);
  }

  function refreshSummary(priceFrom){
    const checkin = inEl?.value;
    const checkout = outEl?.value;
    const guests = (document.querySelector('select[name="guests"]')?.value) || q.guests || "2";
    const n = HOTEL_UTIL.nights(checkin, checkout);

    if (titleEl) titleEl.textContent = HOTEL_UTIL.typeLabel(type);
    if (dateEl) dateEl.textContent = `${checkin} 〜 ${checkout}（${guests}名）`;
    if (nightsEl) nightsEl.textContent = `${n}泊`;

    const p = Number(priceFrom || 0);
    if (priceEl) priceEl.textContent = `¥${p.toLocaleString()} / 泊`;
    if (totalEl) totalEl.textContent = `¥${(p * n).toLocaleString()}`;
  }

  ensurePickers();

  // fetch type details (price_from)
  let priceFrom = 0;
  try{
    const res = await fetch(API_ROOM, { credentials:"include" });
    const data = await res.json();
    if (data.ok && data.item){
      priceFrom = Number(data.item.price_from || 0);
      refreshSummary(priceFrom);
    }
  }catch(e){
    refreshSummary(priceFrom);
  }

  // update summary when date/guests change
  document.getElementById("bookingForm")?.addEventListener("change", () => refreshSummary(priceFrom));

  // submit reservation
  document.getElementById("bookingForm")?.addEventListener("submit", async (e) => {
    e.preventDefault();
    msgEl.textContent = "送信中…";

    const fd = new FormData(e.currentTarget);
    fd.set("type", type);
    // always keep dates from inputs
    fd.set("checkin", inEl.value);
    fd.set("checkout", outEl.value);

    try{
      const res = await fetch(API_RESERVE, {
        method:"POST",
        headers:{ "Content-Type":"application/x-www-form-urlencoded; charset=UTF-8" },
        body: new URLSearchParams(fd)
      });
      const data = await res.json();
      if (!data.ok){
        msgEl.textContent = data.message || "予約に失敗しました。";
        return;
      }
      const code = data.reservation?.code || "OK";
      location.href = `thanks.html?${new URLSearchParams({ code }).toString()}`;
    }catch(err){
      msgEl.textContent = "通信に失敗しました。MAMPとAPIのパスを確認してください。";
    }
  });
})();
