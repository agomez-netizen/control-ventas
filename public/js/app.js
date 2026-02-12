// Sidebar toggle (desktop) + persistencia
(function () {
  const shell = document.getElementById('appShell');
  const btn   = document.getElementById('toggleSidebar');
  const key   = 'sidebar-collapsed';

  if (!shell) return;

  // Restaurar estado
  if (localStorage.getItem(key) === '1') {
    shell.classList.add('collapsed');
  }

  // Toggle
  if (btn) {
    btn.addEventListener('click', () => {
      shell.classList.toggle('collapsed');
      localStorage.setItem(
        key,
        shell.classList.contains('collapsed') ? '1' : '0'
      );
    });
  }
})();

document.addEventListener('DOMContentLoaded', () => {
  const shell = document.getElementById('appShell');
  const btn = document.getElementById('toggleSidebar');

  if (!shell || !btn) return;

  // Restaurar preferencia
  const saved = localStorage.getItem('sidebarCollapsed');
  if (saved === '1') shell.classList.add('collapsed');

  btn.addEventListener('click', () => {
    shell.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', shell.classList.contains('collapsed') ? '1' : '0');
  });
});

document.addEventListener('click', (e) => {
  // Solo cerrar el offcanvas cuando el usuario haga click en un <a> del menú
  const link = e.target.closest('#sidebarMobile a.navitem');
  if (!link) return;

  const el = document.getElementById('sidebarMobile');
  if (!el) return;

  const offcanvas = bootstrap.Offcanvas.getInstance(el);
  if (offcanvas) offcanvas.hide();
});


