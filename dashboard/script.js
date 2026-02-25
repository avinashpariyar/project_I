// Simple client-side handling for bay selection
// Selected bay id is stored in localStorage for use by "Create Job Card" page later.

document.addEventListener('DOMContentLoaded', () => {
  const bayButtons = document.querySelectorAll('.bay-card');
  const hoverMenus = document.querySelectorAll('[data-hover-menu]');
  const STORAGE_KEY = 'selectedBayId';

  function clearSelection() {
    bayButtons.forEach(btn => btn.classList.remove('bay-selected'));
  }

  function restoreSelection() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) return;
    bayButtons.forEach(btn => {
      if (btn.dataset.bayId === saved) {
        btn.classList.add('bay-selected');
      }
    });
  }

  bayButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const bayId = btn.dataset.bayId;
      if (!bayId) return;

      // Toggle selection
      const isSelected = btn.classList.contains('bay-selected');
      clearSelection();

      if (!isSelected) {
        btn.classList.add('bay-selected');
        localStorage.setItem(STORAGE_KEY, bayId);
      } else {
        localStorage.removeItem(STORAGE_KEY);
      }
    });
  });

  restoreSelection();

  hoverMenus.forEach(menu => {
    menu.addEventListener('mouseenter', () => {
      menu.classList.add('is-open');
    });

    menu.addEventListener('mouseleave', () => {
      menu.classList.remove('is-open');
    });
  });
});

