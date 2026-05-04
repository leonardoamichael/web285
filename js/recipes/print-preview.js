const printButton = document.querySelector("#printPageButton");

const printOptions = [
  {
    checkbox: document.querySelector("#printShowPhotos"),
    section: document.querySelector('[data-print-section="photos"]'),
  },
  {
    checkbox: document.querySelector("#printShowDescription"),
    section: document.querySelector('[data-print-section="description"]'),
  },
  {
    checkbox: document.querySelector("#printShowCategories"),
    section: document.querySelector('[data-print-section="categories"]'),
  },
  {
    checkbox: document.querySelector("#printShowNotes"),
    section: document.querySelector('[data-print-section="notes"]'),
  },
];

function updatePrintPreview() {
  printOptions.forEach((option) => {
    if (!option.checkbox || !option.section) {
      return;
    }

    option.section.hidden = !option.checkbox.checked;
  });
}

printOptions.forEach((option) => {
  if (!option.checkbox) {
    return;
  }

  option.checkbox.addEventListener("change", updatePrintPreview);
});

if (printButton) {
  printButton.addEventListener("click", () => {
    window.print();
  });
}

updatePrintPreview();