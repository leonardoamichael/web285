/**
 * Login modal controls
 */
document.addEventListener("DOMContentLoaded", () => {
  const loginLink = document.getElementById("loginLink");
  const modal = document.getElementById("loginModal");
  if (!loginLink || !modal) return;

  function openModal() {
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
  }

  function closeModal() {
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
  }

  loginLink.addEventListener("click", (e) => {
    e.preventDefault();
    openModal();
  });

  modal.addEventListener("click", (e) => {
    if (e.target?.dataset?.close === "true") {
      closeModal();
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("open")) {
      closeModal();
    }
  });
});