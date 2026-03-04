(function () {
  const shell = document.getElementById('appShell');
  const btn   = document.getElementById('toggleSidebar');
  const key   = 'sidebar-collapsed';

  if (!shell) return;

  const initTooltips = () => {
    if (!window.bootstrap) return;

    // destruir tooltips existentes
    document.querySelectorAll('[data-sidebar-tip]').forEach(el => {
      const inst = bootstrap.Tooltip.getInstance(el);
      if (inst) inst.dispose();
    });

    // solo activar cuando está colapsado
    if (!shell.classList.contains('collapsed')) return;

    document.querySelectorAll('[data-sidebar-tip]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  };

  // 1) Estado inicial (evita saltos)
  try {
    if (localStorage.getItem(key) === '1') {
      shell.classList.add('collapsed');
      document.documentElement.classList.add('sidebar-precollapsed');
    } else {
      shell.classList.remove('collapsed');
      document.documentElement.classList.remove('sidebar-precollapsed');
    }
  } catch (e) {}

  // tooltips al iniciar
  document.addEventListener('DOMContentLoaded', initTooltips);

  // 2) Toggle
  if (btn) {
    btn.addEventListener('click', () => {
      shell.classList.toggle('collapsed');

      const isCollapsed = shell.classList.contains('collapsed');
      try {
        localStorage.setItem(key, isCollapsed ? '1' : '0');
      } catch (e) {}

      // mantenemos sincronizado html class (para que no haya flash)
      document.documentElement.classList.toggle('sidebar-precollapsed', isCollapsed);

      initTooltips();
    });
  }

  // 3) Móvil: cerrar offcanvas al hacer click en un link del menú
  document.addEventListener('click', (e) => {
    const link = e.target.closest('#sidebarMobile a.navitem');
    if (!link) return;

    const el = document.getElementById('sidebarMobile');
    if (!el || !window.bootstrap) return;

    const offcanvas = bootstrap.Offcanvas.getInstance(el);
    if (offcanvas) offcanvas.hide();
  });
})();
