// filepath: universal-lead-capture-plugin/universal-lead-capture-plugin/assets/js/admin.js

document.addEventListener('DOMContentLoaded', function() {
    // Initialize settings form
    const settingsForm = document.getElementById('lead-capture-settings-form');
    
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(event) {
            event.preventDefault();
            // Handle form submission
            const formData = new FormData(settingsForm);
            fetch(ajaxurl, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert('Error saving settings: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }

    // Add dynamic field functionality
    const addFieldButton = document.getElementById('add-field-button');
    const fieldsContainer = document.getElementById('fields-container');

    if (addFieldButton && fieldsContainer) {
        addFieldButton.addEventListener('click', function() {
            const newField = document.createElement('div');
            newField.classList.add('form-field');
            newField.innerHTML = `
                <input type="text" name="dynamic_fields[]" placeholder="Enter field name" required>
                <button type="button" class="remove-field-button">Remove</button>
            `;
            fieldsContainer.appendChild(newField);

            // Add remove functionality
            newField.querySelector('.remove-field-button').addEventListener('click', function() {
                fieldsContainer.removeChild(newField);
            });
        });
    }
});