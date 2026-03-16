const toggleButton = document.getElementById('toggleFilters');
const filters = document.getElementById('recipeControls');

if (toggleButton && filters) {
  toggleButton.addEventListener('click', () => {
    const isHidden = filters.hasAttribute('hidden');

    if (isHidden) {
      filters.removeAttribute('hidden');
      toggleButton.textContent = 'Hide Filters';
    } else {
      filters.setAttribute('hidden', '');
      toggleButton.textContent = 'Show Filters';
    }
  });
}