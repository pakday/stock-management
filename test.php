<?php
add_action('woocommerce_page_wc-orders', 'update_modified_date');

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


add_action('admin_init', 'check_for_plugin_update');
function check_for_plugin_update()
{
    $installed_version = current_plugin_version();

    $latest_version = get_latest_github_release_version();

    // error_log_console("latest_version", $latest_version);

    if (version_compare($installed_version, $latest_version, '<')) {
        add_action('admin_notices', 'display_update_notice');
    }
}


// add_action('admin_notices', 'display_update_notice');
function display_update_notice()
{
    echo '<div class="notice notice-update is-dismissible"><p>A new version of My Plugin is available! <a href="https://github.com/yourusername/your-plugin/releases">Update now</a>.</p></div>';
}
// add_action('admin_init', 'check_for_plugin_update');


function current_plugin_version()
{
    $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . 'stock-management.php');

    $installed_version = $plugin_data['Version'];

    return $installed_version;
}

function get_latest_github_release_version()
{
    $username = 'pakday';
    $repository = 'stock-management';

    $api_url = "https://api.github.com/repos/{$username}/{$repository}/releases/latest";

    // Send GET request to GitHub API
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);

    // Decode JSON response
    $data = json_decode($body, true);

    // Extract latest release version
    $latest_version = isset($data['tag_name']) ? $data['tag_name'] : false;

    return $latest_version;
}
