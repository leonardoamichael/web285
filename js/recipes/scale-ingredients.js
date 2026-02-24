document.addEventListener("DOMContentLoaded", () => {
  const list = document.querySelector(".ingredients-list");
  if (!list) return;

  const qtyEls = Array.from(list.querySelectorAll(".ingredient-qty"));
  const controls = document.querySelector(".scale-controls");
  if (!controls) return;

  const buttons = Array.from(controls.querySelectorAll('button[data-scale]'));

  const setActive = (btn) => {
    buttons.forEach((b) => b.classList.remove("is-active"));
    btn.classList.add("is-active");
  };

  const formatNumber = (n) => {
    let rounded = n * 100;
    rounded = Math.round(rounded);
    rounded = rounded / 100;

    const asInt = Math.round(rounded);
    const diff = Math.abs(rounded - asInt);

    if (diff < 1e-9) return String(asInt);
    return String(rounded);
  };

  const applyScale = (scale) => {
    qtyEls.forEach((el) => {
      const baseRaw = el.getAttribute("data-base");
      if (!baseRaw) return;

      const base = Number(baseRaw);
      if (!Number.isFinite(base)) return;

      const unit = el.getAttribute("data-unit") || "";

      const scaled = base * scale;
      const nextQty = formatNumber(scaled);

      el.textContent = unit ? `${nextQty} ${unit}` : nextQty;
    });
  };

  controls.addEventListener("click", (e) => {
    const btn = e.target.closest('button[data-scale]');
    if (!btn) return;

    setActive(btn);
    const scale = Number(btn.dataset.scale);
    applyScale(scale);
  });

  const resetBtn = controls.querySelector('button[data-scale="1"]');
  if (resetBtn) setActive(resetBtn);
});