# WooCommerce CRM Form Builder - Complete Guide

## ✅ **YES! You Can Build Dynamic Forms**

Your WooCommerce CRM plugin now includes **enterprise-level form building capabilities** that rival premium plugins! Here's what you can do:

---

## 🚀 **Current Form Building Features**

### **1. Dynamic Product Forms**
✅ **Auto-generates forms based on WooCommerce products**  
✅ **Pulls product attributes, variations, and categories**  
✅ **Morocco-specific validation and localization**  
✅ **Real-time field generation based on product data**

### **2. Morocco Market Optimization**
✅ **Phone validation for all Morocco formats**  
✅ **City/region dropdowns with postal codes**  
✅ **Shipping integration with local carriers**  
✅ **COD (Cash on Delivery) support**

### **3. Premium-Level Features**
✅ **Category-specific field templates**  
✅ **Product attribute mapping to form fields**  
✅ **Real-time validation (client & server-side)**  
✅ **CRM integration with lead capture**

---

## 📋 **How to Use Form Builder**

### **Basic Usage**

```php
// Initialize the form generator
$formGenerator = new Anas\\WCCRM\\Forms\\ProductFormFieldGenerator();

// Generate form for a specific product
$product_id = 123; // Your WooCommerce product ID
$form_fields = $formGenerator->generate_product_fields($product_id);

// Display the form
foreach ($form_fields as $section => $fields) {
    echo "<h3>" . ucfirst(str_replace('_', ' ', $section)) . "</h3>";
    foreach ($fields as $field) {
        echo $this->render_field($field);
    }
}
```

### **Advanced Product Form Generation**

```php
// Get product-specific form with all enhancements
$enhanced_form = $formGenerator->generate_enhanced_product_form($product_id, [
    'include_shipping' => true,
    'include_morocco_fields' => true,
    'include_product_attributes' => true,
    'include_category_fields' => true
]);
```

---

## 🛍️ **Product Form Examples**

### **Electronics Product Form**

```php
// For electronics products, automatically generates:
[
    'warranty_required' => [
        'type' => 'select',
        'label' => 'Warranty Required?',
        'options' => ['1_year' => '1 Year', '2_years' => '2 Years', 'extended' => 'Extended']
    ],
    'technical_support' => [
        'type' => 'checkbox',
        'label' => 'Include Technical Support'
    ],
    'installation_needed' => [
        'type' => 'radio',
        'label' => 'Installation Required?',
        'options' => ['yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not Sure']
    ]
]
```

### **Clothing Product Form**

```php
// For clothing products, automatically generates:
[
    'size_preference' => [
        'type' => 'select',
        'label' => 'Size Preference',
        'options' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']
    ],
    'color_preference' => [
        'type' => 'color_picker',
        'label' => 'Preferred Color'
    ],
    'fit_preference' => [
        'type' => 'radio',
        'label' => 'Fit Preference',
        'options' => ['slim' => 'Slim Fit', 'regular' => 'Regular', 'loose' => 'Loose Fit']
    ]
]
```

### **Food Products Form**

```php
// For food products, automatically generates:
[
    'dietary_restrictions' => [
        'type' => 'checkbox_group',
        'label' => 'Dietary Restrictions',
        'options' => ['halal' => 'Halal', 'vegetarian' => 'Vegetarian', 'gluten_free' => 'Gluten Free']
    ],
    'delivery_temperature' => [
        'type' => 'radio',
        'label' => 'Delivery Temperature',
        'options' => ['frozen' => 'Frozen', 'chilled' => 'Chilled', 'ambient' => 'Room Temperature']
    ]
]
```

---

## 🇲🇦 **Morocco-Specific Features**

### **Contact Form with Morocco Validation**

```php
[
    'phone' => [
        'type' => 'tel',
        'label' => 'Téléphone / Phone',
        'placeholder' => '06 12 34 56 78 ou +212 6 12 34 56 78',
        'validation' => 'morocco_phone',
        'formats_accepted' => ['+212 6XX XXX XXX', '06XX XXX XXX', '05XX XXX XXX', '07XX XXX XXX']
    ],
    'city' => [
        'type' => 'select',
        'label' => 'Ville / City',
        'options' => 'MoroccoValidator::get_moroccan_cities()', // All Morocco cities
        'dependent_field' => 'postal_code'
    ],
    'region' => [
        'type' => 'select', 
        'label' => 'Région / Region',
        'options' => 'MoroccoValidator::get_moroccan_regions()' // All Morocco regions
    ]
]
```

### **Shipping Form with Local Carriers**

```php
[
    'shipping_carrier' => [
        'type' => 'radio',
        'label' => 'Carrier Preference',
        'options' => [
            'ctm' => 'CTM (Compagnie de Transports au Maroc)',
            'amana' => 'Amana Express', 
            'dhl' => 'DHL Express Morocco',
            'local' => 'Local Delivery'
        ]
    ],
    'cod_enabled' => [
        'type' => 'checkbox',
        'label' => 'Cash on Delivery (COD)',
        'description' => 'Pay when you receive your order'
    ]
]
```

---

## 🎨 **Enhanced Form Builder Methods**

Let me create additional methods to make your form builder even more powerful:
