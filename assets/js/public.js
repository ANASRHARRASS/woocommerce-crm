// filepath: universal-lead-capture-plugin/assets/js/public.js

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.dynamic-form');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(form);
            const actionUrl = form.getAttribute('data-action-url');

            fetch(actionUrl, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Lead captured successfully!');
                    form.reset();
                } else {
                    alert('Error capturing lead: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while capturing the lead.');
            });
        });
    }
});