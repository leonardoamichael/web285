/**
 * Mobile navigation toggle
 *
 * Toggles the responsive navigation menu visibility.
 */
document.addEventListener("DOMContentLoaded", () => {
  const button = document.getElementById("menuToggle");
  const nav = document.getElementById("mainNav");

  if (!button || !nav) return;

  button.addEventListener("click", () => {
    nav.classList.toggle("open");
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const yearSpan = document.getElementById("copyrightYear");
  if (!yearSpan) return;

  const startYear = 2026;
  const currentYear = new Date().getFullYear();

  yearSpan.textContent =
    currentYear > startYear
      ? `${startYear}–${currentYear}`
      : currentYear;
});