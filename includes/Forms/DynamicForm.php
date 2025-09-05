<?php

namespace Anas\WCCRM\Forms;

use Anas\WCCRM\Utils\Helpers;

class DynamicForm
{
    private $productAttributes;

    public function __construct($productId)
    {
        $this->productAttributes = $this->getProductAttributes($productId);
    }

    private function getProductAttributes($productId)
    {
        $product = wc_get_product($productId);
        return $product ? $product->get_attributes() : [];
    }

    public function generateForm()
    {
        $formHtml = '<form method="post" action="">';

        foreach ($this->productAttributes as $attribute) {
            $formHtml .= $this->generateField($attribute);
        }

        $formHtml .= '<input type="submit" value="Submit">';
        $formHtml .= '</form>';

        return $formHtml;
    }

    private function generateField($attribute)
    {
        $fieldHtml = '<div class="form-group">';
        $fieldHtml .= '<label for="' . esc_attr($attribute['name']) . '">' . esc_html($attribute['name']) . '</label>';
        $fieldHtml .= '<input type="text" name="' . esc_attr($attribute['name']) . '" id="' . esc_attr($attribute['name']) . '" class="form-control">';
        $fieldHtml .= '</div>';

        return $fieldHtml;
    }
}
