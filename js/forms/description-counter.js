document.addEventListener("DOMContentLoaded", () => {
  const desc = document.getElementById("description");
  const count = document.getElementById("descCount");
  if (!desc || !count) return;

  const update = () => {
    count.textContent = `${250 - desc.value.length} remaining`;
  };

  desc.addEventListener("input", update);
  update();
});