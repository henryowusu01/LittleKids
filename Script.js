function app() {

}

const logoutLink = document.querySelector('.dashboard a[href="logout.php"]');
const logoutConfirmation = document.getElementById('logout-confirmation');

if (logoutLink && logoutConfirmation) {
    const yesButton = document.getElementById('logout-confirmation-yes');
    const noButton = document.getElementById('logout-confirmation-no');

    logoutLink.addEventListener('click', function (event) {
        event.preventDefault();
        logoutConfirmation.hidden = false;
        noButton.focus();
    });

    yesButton.addEventListener('click', function () {
        window.location.href = 'index.php';
    });

    noButton.addEventListener('click', function () {
        logoutConfirmation.hidden = true;
        logoutLink.focus();
    });

    logoutConfirmation.addEventListener('click', function (event) {
        if (event.target === logoutConfirmation) {
            logoutConfirmation.hidden = true;
            logoutLink.focus();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !logoutConfirmation.hidden) {
            logoutConfirmation.hidden = true;
            logoutLink.focus();
        }
    });
}
