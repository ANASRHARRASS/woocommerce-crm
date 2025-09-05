jQuery(document).ready(function ($) {
    $('#wccrm-app-button').on('click', function (e) {
        e.preventDefault();

        // Open the CRM app in a new tab or iframe
        const appUrl = $(this).data('app-url');
        if (appUrl) {
            window.open(appUrl, '_blank'); // Open in a new tab
        } else {
            console.error('App URL is not defined.');
        }
    });
});
