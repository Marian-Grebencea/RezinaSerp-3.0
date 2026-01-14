const AUTH_KEY = 'auth_token';

function isAuthed() {
  return Boolean(localStorage.getItem(AUTH_KEY));
}

function goCabinet() {
  window.location.href = isAuthed() ? 'cabinet.html' : 'login.html';
}

function handleCabinetLinks() {
  document.querySelectorAll('.js-cabinet-link').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      goCabinet();
    });
  });
}

function handleLoginForm() {
  const form = document.querySelector('.js-login-form');
  if (!form) {
    return;
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    localStorage.setItem(AUTH_KEY, 'demo');
    window.location.href = 'cabinet.html';
  });
}

function handleLogoutButtons() {
  document.querySelectorAll('.js-logout').forEach((button) => {
    button.addEventListener('click', () => {
      localStorage.removeItem(AUTH_KEY);
      window.location.href = 'index.html';
    });
  });
}

function guardCabinet() {
  const guardedPages = ['cabinet.html', 'orders.html', 'booking.html'];
  const isCabinetPage = guardedPages.some((page) =>
    window.location.pathname.endsWith(page)
  );
  if (isCabinetPage && !isAuthed()) {
    window.location.href = 'login.html';
  }
}

handleCabinetLinks();
handleLoginForm();
handleLogoutButtons();
guardCabinet();
