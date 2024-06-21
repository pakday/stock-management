<?php

/*
 * HOOKS
 */


// Run on new checkout order
add_action('woocommerce_thankyou', 'change_status_timestamp_on_new_order');
// Run on new manual order
add_action('woocommerce_new_order', 'change_status_timestamp_on_new_order');
function change_status_timestamp_on_new_order($order_id)
{
    $order = wc_get_order($order_id);

    $order->update_status('on-hold');

    $order->save();

    update_available_unavailable_stock();
}


// Set current time when order status change
add_action('woocommerce_order_status_changed', 'save_last_order_status_change_time', 10, 1);
function save_last_order_status_change_time($order_id)
{
    update_post_meta($order_id, '_order_status_last_modified', current_time('mysql'));
    // update_available_unavailable_stock();
}


// Run on product save
add_action('save_post', 'run_on_product_save', 10, 2);
function run_on_product_save($product_id, $product)
{
    if ($product->post_type === 'product') {
        update_available_unavailable_stock();
    }
}


// Run on order save
add_action('save_post_shop_order', 'trigger_update_available_unavailable_stock', 10, 3);
function trigger_update_available_unavailable_stock($post_id, $post, $update)
{
    if ($update) {
        $order = wc_get_order($post_id);
        if ($order) {
            update_available_unavailable_stock();
        }
    }
}


// Run on order trash
add_action('woocommerce_trash_order', 'update_available_unavailable_stock');


// Run on order items change
// add_action('woocommerce_order_before_calculate_totals', 'update_available_unavailable_stock');


// Run for testing
// add_action('admin_init', 'update_available_unavailable_stock');




function update_available_unavailable_stock()
{
    // Every order must have _order_status_last_modified key, when new order placed

    $args = array(
        'limit' => -1,
        'status' => 'any',
        'meta_key' => '_order_status_last_modified',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    );
    $orders = wc_get_orders($args);

    foreach ($orders as $order) {
        $order_id = $order->get_id();

        set_available_unavailable_stock($order_id);
    }
}



function set_available_unavailable_stock($order_id)
{
    $current_order = wc_get_order($order_id);
    $current_order_status = $current_order->get_status();

    $overlap_orders = find_overlap_orders($current_order);

    // error_log_console("current_order: ", $current_order);

    foreach ($current_order->get_items() as $current_item_id => $current_item) {
        $current_product_id = $current_item->get_product_id();
        $current_product_fixed_stock = get_post_meta($current_product_id, '_fixed_stock', true);
        $current_ordered_quantity = $current_item->get_quantity();

        $item_total_available = [];
        $overlapped_items = [];

        foreach ($overlap_orders as $overlap_order) {
            $overlap_order_status = $overlap_order->get_status();
            $overlap_order_id = $overlap_order->get_id();
            $order_status_last_modified = $overlap_order->get_meta('_order_status_last_modified');

            global $first_rental_date_meta_key;
            global $return_rental_date_meta_key;
            $overlap_start_date = $overlap_order->get_meta($first_rental_date_meta_key);
            $overlap_end_date = $overlap_order->get_meta($return_rental_date_meta_key);

            if ($overlap_order_status == "processing") {
                foreach ($overlap_order->get_items() as $overlap_item_id => $overlap_item) {
                    $overlap_product_id = $overlap_item->get_product_id();
                    $overlap_used_stock = intval(wc_get_order_item_meta($overlap_item_id, '_used_stock', true));
                    $available_stock = intval(wc_get_order_item_meta($overlap_item_id, '_available_stock', true));

                    if (!isset($item_total_available[$overlap_product_id])) {
                        $item_total_available[$overlap_product_id] = 0;
                    }

                    $item_total_available[$overlap_product_id] += $overlap_used_stock;

                    // testing
                    if ($current_product_id == $overlap_product_id) {
                        $overlapped_items[] = array(
                            "order_id" => $overlap_order_id,
                            "order_status" => $overlap_order_status,
                            "used_stock" => $overlap_used_stock,
                            "available_stock" => $available_stock,
                            "start_date" => $overlap_start_date,
                            "end_date" => $overlap_end_date,
                            "order_status_last_modified" => $order_status_last_modified,
                        );
                    }
                }
            }
        }


        $item_total_available_value = isset($item_total_available[$current_product_id]) ? $item_total_available[$current_product_id] : 0;

        $current_available_stock = max($current_product_fixed_stock - $item_total_available_value, 0);
        $current_used_stock = $current_available_stock >= $current_ordered_quantity ? $current_ordered_quantity : $current_available_stock;

        wc_update_order_item_meta($current_item_id, '_available_stock', $current_available_stock);

        if ($current_order_status == "processing") {
            wc_update_order_item_meta($current_item_id, '_used_stock', $current_used_stock);
        }

        if (defined('PLUGIN_DEV') && PLUGIN_DEV) {
            $current_order_status_last_modified = $current_order->get_meta('_order_status_last_modified');

            $order_data = array(
                'product_fixed_stock' => intval($current_product_fixed_stock),
                'used_in_other_stocks' => $item_total_available_value,
                'available_stock' => $current_available_stock . ' (' . $current_product_fixed_stock . '-' . $item_total_available_value . ')',
                'used_stock' => $current_used_stock,
                "order_status_last_modified" => $current_order_status_last_modified,
                'processing_overlapped_items' => (is_array($overlapped_items) && !empty($overlapped_items)) ? $overlapped_items : 'none',
            );

            wc_update_order_item_meta($current_item_id, '_dependent_stock', $order_data);
        }
    }
}



function find_overlap_orders($current_order)
{
    global $first_rental_date_meta_key;
    global $return_rental_date_meta_key;

    $current_start_date = $current_order->get_meta($first_rental_date_meta_key);
    $current_end_date = $current_order->get_meta($return_rental_date_meta_key);

    $current_start_timestamp = strtotime($current_start_date);
    $current_end_timestamp = strtotime($current_end_date);

    $args = array(
        'limit' => -1,
        'status' => 'any',
        'meta_key' => '_order_status_last_modified',
        'orderby' => 'meta_value',
        'order' => 'ASC'
    );
    $orders = wc_get_orders($args);

    // Initialize an array to store overlapping orders
    $overlapping_orders = array();

    foreach ($orders as $overlap_order) {
        // Exclude the order used for checking overlap
        if ($overlap_order->get_id() == $current_order->get_id()) {
            continue;
        }

        $rental_start_date = $overlap_order->get_meta($first_rental_date_meta_key);
        $rental_end_date = $overlap_order->get_meta($return_rental_date_meta_key);

        $rental_start_timestamp = strtotime($rental_start_date);
        $rental_end_timestamp = strtotime($rental_end_date);

        // Check if the rental period overlaps with the check period
        if (($current_start_timestamp >= $rental_start_timestamp && $current_start_timestamp <= $rental_end_timestamp) ||
            ($current_end_timestamp >= $rental_start_timestamp && $current_end_timestamp <= $rental_end_timestamp) ||
            ($current_start_timestamp <= $rental_start_timestamp && $current_end_timestamp >= $rental_end_timestamp)
        ) {
            $overlapping_orders[] = $overlap_order;
        }
    }

    return $overlapping_orders;
}
