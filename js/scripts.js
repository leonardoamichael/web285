const button = document.getElementById("menuToggle");
const nav = document.getElementById("mainNav");

if (button && nav) {
  button.addEventListener("click", function () {
    nav.classList.toggle("open");
  });
}

const loginLink = document.getElementById("loginLink");
const modal = document.getElementById("loginModal");

function openModal() {
  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
}

function closeModal() {
  modal.classList.remove("open");
  modal.setAttribute("aria-hidden", "true");
}

if (loginLink && modal) {
  loginLink.addEventListener("click", (e) => {
    e.preventDefault();
    openModal();
  });

  modal.addEventListener("click", (e) => {
    if (e.target.dataset.close === "true") closeModal();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("open")) closeModal();
  });
}

loginLink.addEventListener("click", () => openModal());