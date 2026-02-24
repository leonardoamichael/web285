/**
 * Converts a timestamp into a relative time label
 * (hours, days, weeks, months, years).
 */
document.addEventListener("DOMContentLoaded", () => {
  const el = document.getElementById("createDate");
  if (!el) return;

  const rawDate = el.textContent.trim();
  const created = new Date(rawDate);
  if (isNaN(created)) return;

  const now = new Date();
  const diffMs = now - created;

  const diffHours = diffMs / (1000 * 60 * 60);
  const diffDays  = diffMs / (1000 * 60 * 60 * 24);
  const diffWeeks = diffDays / 7;
  const diffMonths = diffDays / 30.44;
  const diffYears = diffDays / 365.25;

  let label;

  if (diffHours < 24) {
    const n = Math.floor(diffHours);
    label = `${n} hour${n !== 1 ? "s" : ""} ago`;
  } else if (diffDays < 7) {
    const n = Math.floor(diffDays);
    label = `${n} day${n !== 1 ? "s" : ""} ago`;
  } else if (diffWeeks < 4.3) {
    const n = Math.floor(diffWeeks);
    label = `${n} week${n !== 1 ? "s" : ""} ago`;
  } else if (diffMonths < 12) {
    const n = Math.floor(diffMonths);
    label = `${n} month${n !== 1 ? "s" : ""} ago`;
  } else {
    const n = Math.floor(diffYears);
    label = `${n} year${n !== 1 ? "s" : ""} ago`;
  }

  el.textContent = label;
});