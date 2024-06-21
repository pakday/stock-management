<?php


// add_filter('admin_init', 'test_one_order');
function test_one_order()
{
    if (($_GET['post_type'] ?? null) === 'shop_order') {
        $order_id = 907;
        $order = wc_get_order($order_id);

        admin_console($order, "ORDER");

        // $okok = convert_date_format('24 mei 2024');
        // $okok = convert_date_format('18 May 2024');
        // error_log_console("okok", $okok);

        // error_log_console("order", $order);
    }
}



// Convert all dates into required format
// add_action('admin_init', 'update_all_orders_metadata');
function update_all_orders_metadata()
{
    $args = array(
        'limit' => -1,
        'status' => 'any'
    );

    $orders = wc_get_orders($args);

    foreach ($orders as $order) {
        // $order_id = $order->get_id();

        $plugin_FirstRentalDay = $order->get_meta('_billing_date_field');
        $plugin_LastRentalDay = $order->get_meta('_order_date_field_2');

        // $plugin_FirstRentalDay = $order->get_meta('_section_h21t1g21h2_eerste_huurdag');
        // $plugin_LastRentalDay = $order->get_meta('_section_h21t1g21h2_laatste_huurdag');

        $converted_FirstRentalDay = convert_date_format($plugin_FirstRentalDay);
        $converted_LastRentalDay = convert_date_format($plugin_LastRentalDay);

        $order->update_meta_data('_first_rental_day', $converted_FirstRentalDay);
        $order->update_meta_data('_last_rental_day', $converted_LastRentalDay);

        // $order->update_meta_data('_first_rental_day', "2024-06-21");
        // $order->update_meta_data('_last_rental_day', "2024-07-28");

        $order->save_meta_data();
    }
}



// Save string dates in a required format as new meta data
add_filter('woocommerce_before_order_object_save', 'update_order_meta_before_save', 10, 1);
function update_order_meta_before_save($order)
{
    // $plugin_FirstRentalDay = $order->get_meta('_billing_date_field');
    // $plugin_LastRentalDay = $order->get_meta('_order_date_field_2');

    // $plugin_FirstRentalDay = $order->get_meta('_section_h21t1g21h2_eerste_huurdag');
    // $plugin_LastRentalDay = $order->get_meta('_section_h21t1g21h2_laatste_huurdag');

    // $converted_FirstRentalDay = convert_date_format($plugin_FirstRentalDay);
    // $converted_LastRentalDay = convert_date_format($plugin_LastRentalDay);

    // $order->update_meta_data('_first_rental_day', $converted_FirstRentalDay);
    // $order->update_meta_data('_last_rental_day', $converted_LastRentalDay);

    // $order_id = $order->get_id();

    error_log_console("order", $order);

    $order->update_meta_data('_first_rental_day', "2024-08-12");
    $order->update_meta_data('_last_rental_day', "2024-08-13");

    $order->save_meta_data();

    error_log_console("order", $order);

    // update_post_meta($order_id, '_first_rental_day', '2024-08-12');
    // update_post_meta($order_id, '_last_rental_day', '2024-08-15');

    // error_log_console("query", "OLPLO");
    // error_log_console("query", $order);

    // return $order;
}



// Insert new columns after the 'order_total' column
add_filter('manage_edit-shop_order_columns', 'add_new_date_columns');
function add_new_date_columns($columns)
{
    $new_columns = array();

    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;

        if ($key == 'order_total') {
            $new_columns['first_rental_day'] = __('First Rental Day', 'stock-management');
            $new_columns['last_rental_day'] = __('Last Rental Day', 'stock-management');
        }
    }

    return $new_columns;
}



// Populate the column values
add_action('manage_shop_order_posts_custom_column', 'display_wc_order_list_custom_column_content', 10, 2);
function display_wc_order_list_custom_column_content($column, $order_id)
{
    // $order = wc_get_order($order_id);
    $order = wc_get_order(536);
    // error_log_console("order", $order);

    if ($column == 'first_rental_day') {
        $first_rental_day = $order->get_meta('_first_rental_day');


        if (!empty($first_rental_day)) {
            $formatted_date = date('j F Y', strtotime($first_rental_day));
            echo $formatted_date;
        } else {
            echo "";
        }
    }

    if ($column == 'last_rental_day') {
        $last_rental_day = $order->get_meta('_last_rental_day', true);
        if (!empty($last_rental_day)) {
            $formatted_date = date('j F Y', strtotime($last_rental_day));
            echo $formatted_date;
        } else {
            echo "";
        }
    }
}



// make the column filterable
add_filter('manage_edit-shop_order_sortable_columns', 'make_custom_column_sortable', 1);
function make_custom_column_sortable($sortable_columns)
{
    $sortable_columns['first_rental_day'] = 'first_rental_day';
    $sortable_columns['last_rental_day'] = 'last_rental_day';

    return $sortable_columns;
}



// Reorder orders
add_action('pre_get_posts', 'custom_orderby', 10, 1);
function custom_orderby($query)
{
    // if (!is_admin() || !$query->is_main_query()) {
    //     return;
    // }

    if (isset($query->query_vars['post_type']) && $query->query_vars['post_type'] == 'shop_order') {
        if (isset($query->query_vars['orderby'])) {
            switch ($query->query_vars['orderby']) {
                case 'first_rental_day':
                    $query->set('meta_key', '_first_rental_day');
                    $query->set('orderby', 'meta_value');
                    break;
                case 'last_rental_day':
                    $query->set('meta_key', '_last_rental_day');
                    $query->set('orderby', 'meta_value');
                    break;
            }
        }
    }
}






function convert_date_format($date_string)
{
    // Mapping of Dutch month names to English month names
    $dutch_to_english_months = [
        'januari' => 'January',
        'februari' => 'February',
        'maart' => 'March',
        'april' => 'April',
        'mei' => 'May',
        'juni' => 'June',
        'juli' => 'July',
        'augustus' => 'August',
        'september' => 'September',
        'oktober' => 'October',
        'november' => 'November',
        'december' => 'December'
    ];

    // Convert the date string to lowercase and split it into parts
    $parts = explode(' ', strtolower($date_string));

    // Ensure there are at least three parts: day, month, and year
    if (count($parts) < 3) {
        return false; // Invalid date format
    }

    // Extract the month part from the date string
    $month_part = $parts[1];

    // Check if the month is a Dutch month and replace it with the English equivalent
    if (array_key_exists($month_part, $dutch_to_english_months)) {
        $date_string = str_ireplace(array_keys($dutch_to_english_months), array_values($dutch_to_english_months), $date_string);
    } else {
        return false; // Invalid month name
    }

    // Create a DateTime object from the input date string
    $date_object = DateTime::createFromFormat('j F Y', $date_string);

    if ($date_object) {
        // Return the date in YYYY-MM-DD format
        return $date_object->format('Y-m-d');
    } else {
        return false;
    }
}
