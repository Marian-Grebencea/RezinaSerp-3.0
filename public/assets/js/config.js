(() => {
  const apiUrl = new URL('../api/', window.location.href);
  const apiBase = apiUrl.pathname.replace(/\/$/, '');
  window.API_BASE = apiBase;
})();
