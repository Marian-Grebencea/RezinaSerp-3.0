// Mobile burger menu
const burger = document.getElementById('headerBurger');
const menu = document.getElementById('headerMobileMenu');

if (burger && menu) {
  burger.addEventListener('click', () => {
    menu.classList.toggle('open');
    burger.classList.toggle('open');
  });

  // Close menu after clicking a link
  menu.querySelectorAll('a').forEach((a) => {
    a.addEventListener('click', () => menu.classList.remove('open'));
  });
}
