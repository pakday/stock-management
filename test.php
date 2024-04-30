<?php

// add_action('woocommerce_page_wc-orders', 'update_modified_date');
function update_modified_date()
{
    $product_id = 478;
    $product = wc_get_product($product_id);
    $product2 = new WC_Product($product_id);

    // error_log_console("product", $product);

    if ($product) {
        $current_time = current_time('mysql');

        $product->set_date_modified($current_time);

        // $product->save();

        echo "Product timestamp updated successfully.";
    } else {
        echo "Product not found.";
    }
}
