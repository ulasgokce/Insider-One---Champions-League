import Swal from 'sweetalert2';

function themeColors() {
    const dark = document.documentElement.classList.contains('dark');

    return {
        background: dark ? '#0f172a' : '#ffffff',
        color: dark ? '#f1f5f9' : '#1e293b',
    };
}

/**
 * @param {import('sweetalert2').SweetAlertOptions} options
 * @returns {Promise<boolean>}
 */
export async function confirmDialog(options) {
    const { background, color } = themeColors();

    const result = await Swal.fire({
        background,
        color,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        ...options,
    });

    return result.isConfirmed;
}

export function confirmResetSeason() {
    return confirmDialog({
        title: 'Reset season?',
        text: 'Start again from Week 1 with your current team selection.',
        icon: 'warning',
        confirmButtonText: 'Reset season',
        cancelButtonText: 'Cancel',
    });
}

export function confirmApplyTeams() {
    return confirmDialog({
        title: 'Apply new teams?',
        text: 'The season will reset with your selected clubs.',
        icon: 'question',
        confirmButtonText: 'Apply & reset',
        cancelButtonText: 'Cancel',
    });
}

/**
 * @param {string} message
 * @param {string} [title]
 */
export function showError(message, title = 'Something went wrong') {
    const { background, color } = themeColors();

    return Swal.fire({
        title,
        text: message,
        icon: 'error',
        background,
        color,
        confirmButtonColor: '#059669',
    });
}

/**
 * @param {string} message
 * @param {string} [title]
 */
export function showSuccess(message, title = 'Done') {
    const { background, color } = themeColors();

    return Swal.fire({
        title,
        text: message,
        icon: 'success',
        background,
        color,
        confirmButtonColor: '#059669',
        timer: 2200,
        showConfirmButton: false,
    });
}

export default Swal;
