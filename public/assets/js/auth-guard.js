const API_BASE = '/api';
const GUARDED_PAGES = ['cabinet.html', 'orders.html', 'booking.html'];

async function requestJson(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {}),
    },
    ...options,
  });
  const text = await response.text();
  let payload = {};
  if (text) {
    try {
      payload = JSON.parse(text);
    } catch (error) {
      payload = {};
    }
  }
  return { response, payload };
}

function showFormError(form, message) {
  const error = form?.querySelector('[data-form-error]');
  if (!error) {
    return;
  }
  if (message) {
    error.textContent = message;
    error.classList.add('is-visible');
  } else {
    error.textContent = '';
    error.classList.remove('is-visible');
  }
}

async function fetchProfile() {
  try {
    const { response, payload } = await requestJson('/profile/me', {
      method: 'GET',
    });
    if (!response.ok || !payload.ok) {
      return null;
    }
    return payload.data.user || null;
  } catch (error) {
    return null;
  }
}

function resolveIdentityPayload(identity) {
  if (identity.includes('@')) {
    return { email: identity };
  }
  return { phone: identity };
}

function handleCabinetLinks() {
  document.querySelectorAll('.js-cabinet-link').forEach((link) => {
    link.addEventListener('click', async (event) => {
      event.preventDefault();
      const profile = await fetchProfile();
      window.location.href = profile ? 'cabinet.html' : 'login.html';
    });
  });
}

function handleLoginForm() {
  const form = document.querySelector('.js-login-form');
  if (!form) {
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    showFormError(form, '');

    const identity = form.querySelector('[name="identity"]')?.value.trim() || '';
    const password = form.querySelector('[name="password"]')?.value || '';

    if (!identity || !password) {
      showFormError(form, 'Заполните телефон/почту и пароль.');
      return;
    }

    const payload = {
      password,
      ...resolveIdentityPayload(identity),
    };

    try {
      const { response, payload: result } = await requestJson('/auth/login', {
        method: 'POST',
        body: JSON.stringify(payload),
      });

      if (!response.ok || !result.ok) {
        showFormError(form, result?.error?.message || 'Не удалось выполнить вход.');
        return;
      }

      window.location.href = 'cabinet.html';
    } catch (error) {
      showFormError(form, 'Сервис временно недоступен. Попробуйте позже.');
    }
  });
}

function handleRegisterForm() {
  const form = document.querySelector('.js-register-form');
  if (!form) {
    return;
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    showFormError(form, '');

    const fullName = form.querySelector('[name="name"]')?.value.trim() || '';
    const phone = form.querySelector('[name="phone"]')?.value.trim() || '';
    const email = form.querySelector('[name="email"]')?.value.trim() || '';
    const password = form.querySelector('[name="password"]')?.value || '';

    if (!fullName || !phone || !email || !password) {
      showFormError(form, 'Заполните все поля регистрации.');
      return;
    }

    try {
      const { response, payload } = await requestJson('/auth/register', {
        method: 'POST',
        body: JSON.stringify({
          full_name: fullName,
          phone,
          email,
          password,
        }),
      });

      if (!response.ok || !payload.ok) {
        showFormError(form, payload?.error?.message || 'Не удалось зарегистрироваться.');
        return;
      }

      window.location.href = 'login.html';
    } catch (error) {
      showFormError(form, 'Сервис временно недоступен. Попробуйте позже.');
    }
  });
}

function handleLogoutButtons() {
  document.querySelectorAll('.js-logout').forEach((button) => {
    button.addEventListener('click', async () => {
      try {
        await requestJson('/auth/logout', { method: 'POST' });
      } catch (error) {
        // ignore
      } finally {
        window.location.href = 'index.html';
      }
    });
  });
}

async function guardCabinet() {
  const isGuardedPage = GUARDED_PAGES.some((page) =>
    window.location.pathname.endsWith(page)
  );

  const isAuthPage = ['login.html', 'register.html'].some((page) =>
    window.location.pathname.endsWith(page)
  );

  if (!isGuardedPage && !isAuthPage) {
    return;
  }

  const profile = await fetchProfile();

  if (isGuardedPage && !profile) {
    window.location.href = 'login.html';
    return;
  }

  if (isAuthPage && profile) {
    window.location.href = 'cabinet.html';
    return;
  }

  if (profile && window.location.pathname.endsWith('cabinet.html')) {
    const fields = document.querySelectorAll('[data-profile]');
    fields.forEach((field) => {
      const key = field.dataset.profile;
      const value = profile?.[key] || '—';
      field.textContent = value;
    });
  }
}

handleCabinetLinks();
handleLoginForm();
handleRegisterForm();
handleLogoutButtons();
guardCabinet();
