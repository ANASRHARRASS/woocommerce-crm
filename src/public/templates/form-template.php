<?php
// This file contains the template for rendering forms on the front end.

function render_dynamic_form($form_id) {
    // Fetch form data based on the form ID
    $form_data = get_form_data($form_id);
    
    if (!$form_data) {
        return '<p>Form not found.</p>';
    }

    ob_start(); // Start output buffering
    ?>
    <form id="dynamic-form-<?php echo esc_attr($form_id); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="submit_dynamic_form">
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">

        <?php foreach ($form_data['fields'] as $field): ?>
            <div class="form-field">
                <label for="<?php echo esc_attr($field['name']); ?>"><?php echo esc_html($field['label']); ?></label>
                <?php if ($field['type'] === 'text'): ?>
                    <input type="text" name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field['name']); ?>" required>
                <?php elseif ($field['type'] === 'select'): ?>
                    <select name="<?php echo esc_attr($field['name']); ?>" id="<?php echo esc_attr($field['name']); ?>" required>
                        <?php foreach ($field['options'] as $option): ?>
                            <option value="<?php echo esc_attr($option['value']); ?>"><?php echo esc_html($option['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit">Submit</button>
    </form>
    <?php
    return ob_get_clean(); // Return the buffered content
}

function get_form_data($form_id) {
    // Placeholder function to simulate fetching form data
    // In a real implementation, this would retrieve data from a database or other source
    return [
        'fields' => [
            ['name' => 'name', 'label' => 'Your Name', 'type' => 'text'],
            ['name' => 'email', 'label' => 'Your Email', 'type' => 'text'],
            ['name' => 'interest', 'label' => 'Interest', 'type' => 'select', 'options' => [
                ['value' => 'product1', 'label' => 'Product 1'],
                ['value' => 'product2', 'label' => 'Product 2'],
            ]],
        ],
    ];
}
?>