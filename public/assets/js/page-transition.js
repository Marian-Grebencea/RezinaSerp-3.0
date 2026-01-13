(() => {
    const transitionMs = 220;
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const links = Array.from(
        document.querySelectorAll('#cabinetLink, [data-cabinet-link]')
    );

    if (!links.length) {
        return;
    }

    const getTargetUrl = () => {
        const authed = Boolean(localStorage.getItem('auth_token'));
        return authed ? 'cabinet.html' : 'login.html';
    };

    const handleNavigate = (event) => {
        event.preventDefault();

        const targetUrl = getTargetUrl();

        if (prefersReduced.matches) {
            window.location.href = targetUrl;
            return;
        }

        document.body.classList.add('page-leave');

        window.setTimeout(() => {
            window.location.href = targetUrl;
        }, transitionMs);
    };

    links.forEach((link) => {
        link.addEventListener('click', handleNavigate);
        link.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                handleNavigate(event);
            }
        });
    });
})();
