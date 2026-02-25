document.addEventListener('DOMContentLoaded', () => {
  const hoverMenus = document.querySelectorAll('[data-hover-menu]');

  hoverMenus.forEach(menu => {
    menu.addEventListener('mouseenter', () => {
      menu.classList.add('is-open');
    });

    menu.addEventListener('mouseleave', () => {
      menu.classList.remove('is-open');
    });
  });
});

