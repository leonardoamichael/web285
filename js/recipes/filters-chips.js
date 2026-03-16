document.addEventListener("DOMContentLoaded", () => {
  const grid = document.querySelector(".recipe-grid");
  const recipeControls = document.getElementById("recipeControls");

  if (!grid || !recipeControls) return;

  const cards = Array.from(grid.querySelectorAll(".recipe-tile"));
  if (!cards.length) return;

  const searchInput = document.getElementById("search");
  const sortSelect = document.getElementById("sortBy");
  const clearBtn = document.getElementById("clearFilters");

  const typeWrap = document.getElementById("typeChips");
  const styleWrap = document.getElementById("styleChips");
  const dietWrap = document.getElementById("dietChips");

  const norm = (s) => (s || "").toString().trim().toLowerCase();

  const parseCsvSet = (csv) => {
    const raw = (csv || "").toString().trim();
    if (!raw) return new Set();
    return new Set(raw.split(",").map((x) => norm(x)).filter(Boolean));
  };

  const pretty = (token) =>
    (token || "")
      .toString()
      .trim()
      .toLowerCase()
      .replace(/[_-]+/g, " ")
      .split(/\s+/)
      .filter(Boolean)
      .map((w) => w[0].toUpperCase() + w.slice(1))
      .join(" ");

  const collectTokens = (datasetKey) => {
    const out = new Set();
    cards.forEach((c) => {
      parseCsvSet(c.dataset[datasetKey]).forEach((t) => out.add(t));
    });
    return Array.from(out).sort();
  };

  const renderChips = (wrap, tokens, group) => {
    if (!wrap) return;

    const body = wrap.querySelector(".filter-group-body");
    if (!body) return;

    Array.from(body.querySelectorAll(".chip")).forEach((n) => n.remove());

    tokens.forEach((t) => {
      const id = `${group}_${t.replace(/[^a-z0-9]+/g, "_")}`;

      const label = document.createElement("label");
      label.className = "chip";

      const input = document.createElement("input");
      input.className = "chip-input";
      input.id = id;
      input.type = "checkbox";
      input.value = t;

      const span = document.createElement("span");
      span.className = "chip-text";
      span.textContent = pretty(t);

      label.appendChild(input);
      label.appendChild(span);
      body.appendChild(label);
    });
  };

  renderChips(typeWrap, collectTokens("type"), "type");
  renderChips(styleWrap, collectTokens("style"), "style");
  renderChips(dietWrap, collectTokens("diet"), "diet");

  const state = {
    q: norm(searchInput?.value || ""),
    types: new Set(),
    styles: new Set(),
    diets: new Set(),
    sort: "newest",
  };

  const groupMatch = (selectedSet, cardSet) => {
    if (!selectedSet.size) return true;
    for (const s of selectedSet) {
      if (cardSet.has(s)) return true;
    }
    return false;
  };

  const matches = (card) => {
    const title = norm(card.dataset.title);
    const qOk = !state.q || title.includes(state.q);

    const cardTypes = parseCsvSet(card.dataset.type);
    const cardStyles = parseCsvSet(card.dataset.style);
    const cardDiets = parseCsvSet(card.dataset.diet);

    return (
      qOk &&
      groupMatch(state.types, cardTypes) &&
      groupMatch(state.styles, cardStyles) &&
      groupMatch(state.diets, cardDiets)
    );
  };

  const sortVisible = (list) => {
    const byNewest = (a, b) => {
      const ta = Date.parse(a.dataset.created || "") || 0;
      const tb = Date.parse(b.dataset.created || "") || 0;
      return tb - ta;
    };

    const byRating = (a, b) => {
      const ra = parseFloat(a.dataset.rating || "0") || 0;
      const rb = parseFloat(b.dataset.rating || "0") || 0;
      if (rb !== ra) return rb - ra;
      return byNewest(a, b);
    };

    const fn = state.sort === "rating" ? byRating : byNewest;
    return list.sort(fn);
  };

  const apply = () => {
    const visible = [];

    cards.forEach((card) => {
      const ok = matches(card);
      card.hidden = !ok;
      if (ok) visible.push(card);
    });

    sortVisible(visible).forEach((card) => grid.appendChild(card));
  };

  const readChecked = (wrap) => {
    if (!wrap) return new Set();
    const checked = Array.from(
      wrap.querySelectorAll('input[type="checkbox"]:checked')
    ).map((cb) => norm(cb.value));
    return new Set(checked);
  };

  if (searchInput) {
    searchInput.addEventListener("input", () => {
      state.q = norm(searchInput.value);
      apply();
    });
  }

  if (typeWrap) {
    typeWrap.addEventListener("change", () => {
      state.types = readChecked(typeWrap);
      apply();
    });
  }

  if (styleWrap) {
    styleWrap.addEventListener("change", () => {
      state.styles = readChecked(styleWrap);
      apply();
    });
  }

  if (dietWrap) {
    dietWrap.addEventListener("change", () => {
      state.diets = readChecked(dietWrap);
      apply();
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener("change", () => {
      state.sort = norm(sortSelect.value) || "newest";
      apply();
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener("click", () => {
      state.q = "";
      state.types = new Set();
      state.styles = new Set();
      state.diets = new Set();
      state.sort = "newest";

      if (searchInput) searchInput.value = "";
      if (sortSelect) sortSelect.value = "newest";

      [typeWrap, styleWrap, dietWrap].forEach((wrap) => {
        if (!wrap) return;
        wrap.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
          cb.checked = false;
        });
      });

      apply();
    });
  }

  apply();
});