// Shared by every page: the mobile menu.
(function () {
    const button = document.querySelector('.hamburger');
    const menu = document.getElementById('navMenu');
    if (!button || !menu) return;

    function setOpen(open) {
        menu.classList.toggle('active', open);
        button.classList.toggle('active', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        document.body.classList.toggle('menu-open', open);
    }

    button.addEventListener('click', function () {
        setOpen(!menu.classList.contains('active'));
    });

    // Close after a link is chosen
    menu.addEventListener('click', function (event) {
        if (event.target.closest('a')) setOpen(false);
    });

    // Close with the Escape key and return focus to the button
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && menu.classList.contains('active')) {
            setOpen(false);
            button.focus();
        }
    });

    // If the screen grows past the phone layout, make sure the menu is closed
    const wide = window.matchMedia('(min-width: 768px)');
    const onWide = function (event) { if (event.matches) setOpen(false); };
    if (wide.addEventListener) wide.addEventListener('change', onWide);
})();
