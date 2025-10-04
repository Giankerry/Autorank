document.addEventListener('DOMContentLoaded', () => {
    /*
    |--------------------------------------------------------------------------
    | FOR TOGGLING HIDDEN MENU
    |--------------------------------------------------------------------------
    */
    const nav = document.getElementById('hidden-menu');
    const menuToggleButton = document.getElementById('menu-toggle-button');
    const toggleMenuIcon = document.querySelector('.fa-bars');

    if (nav && menuToggleButton) {
        nav.classList.remove('is-active');
    }

    function toggleMenu() {
        if (!nav || !menuToggleButton || !toggleMenuIcon) {
            console.error('Menu or toggle icon element not found for toggling.');
            return;
        }

        const isActive = nav.classList.toggle('is-active');

        if (isActive) {
            toggleMenuIcon.classList.remove('fa-bars');
            toggleMenuIcon.classList.add('fa-times');
        } else {
            toggleMenuIcon.classList.remove('fa-times');
            toggleMenuIcon.classList.add('fa-bars');
        }

    }

    if (menuToggleButton) {
        menuToggleButton.addEventListener('click', toggleMenu);
    }

    // Closes the menu when a cursor leaves the menu container
    if (nav && menuToggleButton) {
        nav.addEventListener('mouseleave', function() {
            if (nav.classList.contains('is-active')) {
                toggleMenu();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FOR GOING BACK TO THE PREVIOUS PAGE
    |--------------------------------------------------------------------------
    */
    function goBack() {
        history.back();
    }

    window.goBack = goBack;
});
