/**
 * Submission form dynamic field scaling.
 *
 * Provides progressive reveal and unlimited cloning for:
 * - Ingredients
 * - Steps
 */
document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById("recipeSubmitForm");
  if (!form) return;

  const hiddenClass = "is-hidden";

  /**
   * Reveal hidden nodes until at least `count` are visible.
   * Does nothing if some rows are already visible (POST-back safe).
   *
   * @param {HTMLElement[]} nodes
   * @param {number} count
   * @return {void}
   */
  function showFirstHiddenAware(nodes, count) {
    const visible = nodes.filter((el) => !el.classList.contains(hiddenClass)).length;

    if (visible > 0) return;

    let shown = 0;

    for (const el of nodes) {
      if (shown >= count) break;
      if (el.classList.contains(hiddenClass)) {
        el.classList.remove(hiddenClass);
        shown++;
      }
    }
  }

  /**
   * Clear values inside an ingredient row.
   *
   * @param {HTMLElement} row
   * @return {void}
   */
  function clearIngredientRow(row) {
    row.querySelectorAll("input, select, textarea").forEach((el) => {
      el.value = "";
    });
  }

  /**
   * Add an ingredient row.
   *
   * Reveals the next hidden row if available, otherwise clones the last row.
   *
   * @return {void}
   */
  function addIngredientRow() {

    const rows = Array.from(form.querySelectorAll("fieldset.ingredient-row"));

    const nextHidden = rows.find((r) => r.classList.contains(hiddenClass));
    if (nextHidden) {
      nextHidden.classList.remove(hiddenClass);
      return;
    }

    const last = rows[rows.length - 1];
    if (!last) return;

    const clone = last.cloneNode(true);
    clone.classList.remove(hiddenClass);

    const newIndex = rows.length;

    clearIngredientRow(clone);

    const qty  = clone.querySelector('input[name="qty[]"]');
    const unit = clone.querySelector('select[name="unit[]"]');
    const ing  = clone.querySelector('input[name="ing[]"]');
    const note = clone.querySelector('input[name="note[]"]');

    const labelQty  = clone.querySelector('label[for^="qty"]');
    const labelUnit = clone.querySelector('label[for^="unit"]');
    const labelIng  = clone.querySelector('label[for^="ing"]');
    const labelNote = clone.querySelector('label[for^="note"]');

    if (labelQty) labelQty.setAttribute("for", `qty${newIndex}`);
    if (qty) qty.id = `qty${newIndex}`;

    if (labelUnit) labelUnit.setAttribute("for", `unit${newIndex}`);
    if (unit) unit.id = `unit${newIndex}`;

    if (labelIng) labelIng.setAttribute("for", `ing${newIndex}`);
    if (ing) ing.id = `ing${newIndex}`;

    if (labelNote) labelNote.setAttribute("for", `note${newIndex}`);
    if (note) note.id = `note${newIndex}`;

    const legend = clone.querySelector("legend");
    if (legend) legend.textContent = `Ingredient ${newIndex + 1}`;

    last.insertAdjacentElement("afterend", clone);
  }

  /**
   * Add a step row.
   *
   * Reveals the next hidden row if available, otherwise clones the last row.
   *
   * @return {void}
   */
  function addStepRow() {

    const rows = Array.from(form.querySelectorAll("div.step-wrap"));

    const nextHidden = rows.find((r) => r.classList.contains(hiddenClass));
    if (nextHidden) {
      nextHidden.classList.remove(hiddenClass);
      return;
    }

    const last = rows[rows.length - 1];
    if (!last) return;

    const clone = last.cloneNode(true);
    clone.classList.remove(hiddenClass);

    const newIndex = rows.length;

    const label = clone.querySelector("label");
    const ta = clone.querySelector("textarea");

    if (label) {
      label.setAttribute("for", `step${newIndex}`);
      label.textContent = `Step ${newIndex + 1}`;
    }

    if (ta) {
      ta.id = `step${newIndex}`;
      ta.value = "";
    }

    last.insertAdjacentElement("afterend", clone);
  }

  const ingredientRows = Array.from(form.querySelectorAll(".ingredient-row"));
  const stepWraps = Array.from(form.querySelectorAll(".step-wrap"));

  showFirstHiddenAware(ingredientRows, 2);
  showFirstHiddenAware(stepWraps, 2);

  const addIngBtn = document.getElementById("addIngredientBtn");
  const addStepBtn = document.getElementById("addStepBtn");

  if (addIngBtn) addIngBtn.addEventListener("click", addIngredientRow);
  if (addStepBtn) addStepBtn.addEventListener("click", addStepRow);

});