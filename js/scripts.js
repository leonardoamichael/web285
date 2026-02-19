/**
 * Mobile navigation toggle
 *
 * Toggles the responsive navigation menu visibility.
 */
const button = document.getElementById("menuToggle");
const nav = document.getElementById("mainNav");

if (button && nav) {
  button.addEventListener("click", function () {
    nav.classList.toggle("open");
  });
}

/**
 * Login modal controls
 */
const loginLink = document.getElementById("loginLink");
const modal = document.getElementById("loginModal");

/**
 * Open the login modal.
 *
 * Adds the modal visibility class and updates accessibility attributes.
 *
 * @returns {void}
 */
function openModal() {
  if (!modal) return;

  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
}

/**
 * Close the login modal.
 *
 * Removes the modal visibility class and restores accessibility attributes.
 *
 * @returns {void}
 */
function closeModal() {
  if (!modal) return;

  modal.classList.remove("open");
  modal.setAttribute("aria-hidden", "true");
}

if (loginLink && modal) {

  loginLink.addEventListener("click", (e) => {
    e.preventDefault();
    openModal();
  });

  modal.addEventListener("click", (e) => {
    if (
      e.target &&
      e.target.dataset &&
      e.target.dataset.close === "true"
    ) {
      closeModal();
    }
  });

  document.addEventListener("keydown", (e) => {
    if (
      e.key === "Escape" &&
      modal.classList.contains("open")
    ) {
      closeModal();
    }
  });
}

/**
 * Progressive reveal logic
 *
 * Controls dynamic expansion of ingredient and step inputs
 * on the recipe submission form.
 */
document.addEventListener("DOMContentLoaded", () => {

  /* Ingredient rows */
  const ingredientRows = Array.from(
    document.querySelectorAll(".ingredient-row")
  );
  const addIngredientBtn = document.getElementById("addIngredientBtn");

  /* Step containers */
  const stepWraps = Array.from(
    document.querySelectorAll(".step-wrap")
  );
  const addStepBtn = document.getElementById("addStepBtn");

  /**
   * Ensure a minimum number of visible elements.
   *
   * If PHP pre-rendered visible rows (POST-back scenario),
   * this function preserves them and only reveals additional
   * hidden elements as needed.
   *
   * @param {HTMLElement[]} nodes Collection of UI elements
   * @param {number} count Minimum number to display
   * @returns {void}
   */
  const showFirstHiddenAware = (nodes, count) => {

    let visible = nodes.filter(
      (el) => !el.classList.contains("is-hidden")
    ).length;

    for (const el of nodes) {
      if (visible >= count) break;

      if (el.classList.contains("is-hidden")) {
        el.classList.remove("is-hidden");
        visible++;
      }
    }
  };

  /**
   * Reveal the next hidden element.
   *
   * Removes the hidden class from the next available element
   * and disables the button when no hidden elements remain.
   *
   * @param {HTMLElement[]} nodes Collection of UI elements
   * @param {HTMLElement} buttonEl Trigger button
   * @returns {void}
   */
  const revealNext = (nodes, buttonEl) => {

    const next = nodes.find(
      (el) => el.classList.contains("is-hidden")
    );

    if (next) {
      next.classList.remove("is-hidden");
    }

    const anyLeft = nodes.some(
      (el) => el.classList.contains("is-hidden")
    );

    if (!anyLeft && buttonEl) {
      buttonEl.disabled = true;
      buttonEl.textContent = "All added";
    }
  };

  /* Ingredient progressive reveal */
  if (ingredientRows.length && addIngredientBtn) {

    showFirstHiddenAware(ingredientRows, 2);

    const anyLeft = ingredientRows.some(
      (el) => el.classList.contains("is-hidden")
    );

    if (!anyLeft) {
      addIngredientBtn.disabled = true;
      addIngredientBtn.textContent = "All added";
    }

    addIngredientBtn.addEventListener("click", () =>
      revealNext(ingredientRows, addIngredientBtn)
    );
  }

  /* Step progressive reveal */
  if (stepWraps.length && addStepBtn) {

    showFirstHiddenAware(stepWraps, 2);

    const anyLeft = stepWraps.some(
      (el) => el.classList.contains("is-hidden")
    );

    if (!anyLeft) {
      addStepBtn.disabled = true;
      addStepBtn.textContent = "All added";
    }

    addStepBtn.addEventListener("click", () =>
      revealNext(stepWraps, addStepBtn)
    );
  }
});