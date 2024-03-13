<?php

add_action('wp_ajax_my_get_settings_data', 'get_settings_data');
add_action('wp_ajax_nopriv_my_get_settings_data', 'get_settings_data');

function get_settings_data()
{
    $the_query = "AJAX RESPONSE WORKING";

    $ajax_response = array(
        'success' => true,
        'data' => $the_query
    );

    if (defined('DOING_AJAX') && DOING_AJAX) {
        echo json_encode($ajax_response);
        die;
    }
}
