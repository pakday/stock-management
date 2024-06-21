<?php


// Add custom number field to product
add_action('woocommerce_product_options_general_product_data', 'add_fixed_stock_product_options');
function add_fixed_stock_product_options()
{
    woocommerce_wp_text_input(
        array(
            'id' => '_fixed_stock',
            'label' => __('Fixed Quantity', 'stock-management'),
            'placeholder' => __('Enter fixed quantity', 'stock-management'),
            'desc_tip' => 'true',
            'description' => __('Enter a custom number for this product.', 'stock-management'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0',
            ),
            'value' => get_post_meta(get_the_ID(), '_fixed_stock', true) ? get_post_meta(get_the_ID(), '_fixed_stock', true) : '0',
        )
    );
}

add_action('woocommerce_process_product_meta', 'save_fixed_stock_product_data');
function save_fixed_stock_product_data($product_id)
{
    $custom_number_field_value = isset($_POST['_fixed_stock']) ? wc_clean($_POST['_fixed_stock']) : '';

    if (empty($custom_number_field_value)) {
        $custom_number_field_value = 0;
    }

    update_post_meta($product_id, '_fixed_stock', $custom_number_field_value);
}



// Show custom column in products table
function custom_products_column_header($columns)
{
    $columns['fixed_stock'] = __('Fixed Stock', 'stock-management');

    return $columns;
}
add_filter('manage_product_posts_columns', 'custom_products_column_header');


function custom_products_column_content($column, $post_id)
{
    if ($column == 'fixed_stock') {
        $product_id = $post_id;
        $product_meta_value = get_post_meta($product_id, '_fixed_stock', true);

        if ($product_meta_value !== '') {
            echo esc_html($product_meta_value);
        } else {
            echo __('Set Stock', 'stock-management');
        }
    }
}
add_action('manage_product_posts_custom_column', 'custom_products_column_content', 10, 2);
