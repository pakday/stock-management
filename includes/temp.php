<?php

// Local site keys
// $first_rental_day_meta_key_old = '_section_hh2c1aj2q4_first_rental_day';
// $last_rental_day_meta_key_old = '_section_hh2c1aj2q4_last_rental_day';
// Client site keys
$first_rental_day_meta_key_old = '_section_h21t1g21h2_eerste_huurdag';
$last_rental_day_meta_key_old = '_section_h21t1g21h2_laatste_huurdag';

add_action('admin_init', 'all_orders_exchange_dates');

function all_orders_exchange_dates()
{
    $args = array(
        'limit' => -1,
        'status' => 'any',
        'order' => 'ASC'
    );
    $orders = wc_get_orders($args);

    foreach ($orders as $order) {
        exchange_dates_meta_data($order);
    }
}

function exchange_dates_meta_data($order)
{
    global $first_rental_day_meta_key_old;
    global $last_rental_day_meta_key_old;
    global $first_rental_date_meta_key;
    global $return_rental_date_meta_key;

    // Retrieve existing metadata using old keys
    $overlap_start_date_old = $order->get_meta($first_rental_day_meta_key_old);
    $overlap_end_date_old = $order->get_meta($last_rental_day_meta_key_old);

    // Update the metadata with new keys only if the old values are not empty
    if (!empty($overlap_start_date_old)) {
        $order->update_meta_data($first_rental_date_meta_key, $overlap_start_date_old);
    }

    if (!empty($overlap_end_date_old)) {
        $order->update_meta_data($return_rental_date_meta_key, $overlap_end_date_old);
    }

    $order->save();
}














add_action('admin_init', 'all_orders_reset_available');

function all_orders_reset_available()
{
    $args = array(
        'limit' => -1,
        'status' => 'any',
        'order' => 'ASC'
    );
    $orders = wc_get_orders($args);

    $total_available_stock = [];

    foreach ($orders as $order) {
        $order_status = $order->get_status();

        if ($order_status === 'processing' && $order) {
            foreach ($order->get_items() as $item_id => $item) {
                $product_id = $item->get_product_id();
                $fixed_stock = get_post_meta($product_id, '_fixed_stock', true);
                $ordered_quantity = $item->get_quantity();

                // add available stock back to 
                $available_stock = wc_get_order_item_meta($item_id, '_available_stock', true);
                // error_log_console('order_id', $order->get_id());
                // error_log_console('item_id', $item_id);
                // error_log_console('product_id', $product_id);
                // error_log_console('available_stock', $available_stock);
                // error_log_console('available_stock', "");

                if (!isset($total_available_stock[$product_id])) {
                    $total_available_stock[$product_id] = 0;
                }

                $total_available_stock[$product_id] += (int)$available_stock;

                // wc_update_order_item_meta($item_id, '_available_stock', 0);
                if (!empty($item_id)) {
                    wc_delete_order_item_meta($item_id, '_available_stock');
                    wc_delete_order_item_meta($item_id, '_available_stock_old');
                    wc_delete_order_item_meta($item_id, '_unavailable_stock');
                    wc_delete_order_item_meta($item_id, '_total_available_stock');
                    wc_delete_order_item_meta($item_id, '_total_unavailable_stock');
                }
            }
        }
    }

    foreach ($orders as $order) {
        foreach ($order->get_items() as $item_id => $item) {
            if (!empty($item_id)) {
                wc_delete_order_item_meta($item_id, '_available_stock');
                wc_delete_order_item_meta($item_id, '_available_stock_old');
                wc_delete_order_item_meta($item_id, '_unavailable_stock');
                wc_delete_order_item_meta($item_id, '_total_available_stock');
                wc_delete_order_item_meta($item_id, '_total_unavailable_stock');
            }
        }
    }

    foreach ($total_available_stock as $product_id => $product_stock) {

        $fixed_stock = get_post_meta($product_id, '_fixed_stock', true);

        $total_available_fixed_stock = (int)$product_stock + (int)$fixed_stock;

        // error_log_console('product_id', $product_id);
        // error_log_console('product_stock', $product_stock);
        // error_log_console('total_available_fixed_stock', $total_available_fixed_stock);

        update_post_meta($product_id, '_fixed_stock', $total_available_fixed_stock);
    }
}










add_action('admin_init', 'all_orders_last_status_date');

function all_orders_last_status_date()
{
    $args = array(
        'limit' => -1,
        'status' => 'any',
        'order' => 'ASC'
    );
    $orders = wc_get_orders($args);

    $total_available_stock = [];

    foreach ($orders as $order) {
        $order_id = $order->get_id();

        $order_notes = wc_get_order_notes(array(
            'order_id' => $order_id,
            'orderby'  => 'date_created_gmt',
            'order'    => 'DESC',
        ));

        $last_status_change_date = null;

        foreach ($order_notes as $note) {
            if (
                strpos($note->content, 'Order status changed from') !== false ||
                strpos($note->content, 'Bestellingsstatus gewijzigd van') !== false
            ) {
                if ($last_status_change_date === null || strtotime($note->date_created) > strtotime($last_status_change_date)) {
                    $last_status_change_date = $note->date_created->format('Y-m-d H:i:s');
                }
            }
        }

        $order->update_meta_data("_order_status_last_modified", $last_status_change_date);

        $order->save();
    }
}
