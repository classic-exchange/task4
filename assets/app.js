import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

function initializePasswordToggle() {
    const togglePassword = document.querySelector('[data-password-toggle]');
    if (togglePassword) {
        const passwordInput = document.getElementById(togglePassword.dataset.passwordToggle);
        const passwordIcon = togglePassword.querySelector('i');
        togglePassword.addEventListener('click', function () {
            const hidden = passwordInput.type === 'password';
            passwordInput.type = hidden ? 'text' : 'password';
            passwordIcon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }
}

function initializeUserSelection() {
    const selectAll = document.getElementById('select-all');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const blockButton = document.getElementById('block-users');
    const unblockButton = document.getElementById('unblock-users');
    const deleteButton = document.getElementById('delete-users');
    if (!selectAll || userCheckboxes.length === 0) {
        return;
    }

    function updateToolbar() {
        const selectedCount = document.querySelectorAll('.user-checkbox:checked').length;
        const hasSelection = selectedCount > 0;
        blockButton.disabled = !hasSelection;
        unblockButton.disabled = !hasSelection;
        deleteButton.disabled = !hasSelection;
        selectAll.checked = selectedCount === userCheckboxes.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < userCheckboxes.length;
    }

    selectAll.addEventListener('change', function () {
        userCheckboxes.forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        updateToolbar();
    });
    userCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateToolbar);
    });
    updateToolbar();
}

document.addEventListener('turbo:load', () => {
    initializePasswordToggle();
    initializeUserSelection();
});