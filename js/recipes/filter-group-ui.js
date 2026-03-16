const groups = document.querySelectorAll(".filter-group");

groups.forEach((group) => {
  const body = group.querySelector(".filter-group-body");
  const search = group.querySelector(".filter-group-search");
  const toggle = group.querySelector(".filter-group-toggle");

  if (!body || !search || !toggle) {
    return;
  }

  let expanded = false;

  const updateExpandedState = () => {
    group.classList.toggle("is-expanded", expanded);
    toggle.textContent = expanded ? "Show Less" : "Show All";
  };

  const applySearch = () => {
    const term = search.value.trim().toLowerCase();
    const chips = Array.from(body.querySelectorAll(".chip"));

    chips.forEach((chip) => {
      const text = chip.textContent.trim().toLowerCase();
      const input = chip.querySelector('input[type="checkbox"]');
      const checked = !!input?.checked;
      const match = !term || text.includes(term);

      chip.hidden = !(match || checked);
    });

    if (term) {
      group.classList.add("is-searching");
    } else {
      group.classList.remove("is-searching");
    }
  };

  toggle.addEventListener("click", () => {
    expanded = !expanded;
    updateExpandedState();
  });

  search.addEventListener("input", () => {
    applySearch();
  });

  body.addEventListener("change", () => {
    applySearch();
  });

  updateExpandedState();
  applySearch();
});
