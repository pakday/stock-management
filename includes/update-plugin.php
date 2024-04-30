<?php
add_action('wp_ajax_update_plugin', 'update_plugin');
function update_plugin()
{
    $plugin_availability = check_for_plugin_update();

    $ajax_response = array();

    if ($plugin_availability['available']) {
        $ajax_response = array(
            'success' => true,
            'data' => $plugin_availability
        );
    } else {
        $ajax_response = array(
            'success' => false,
            'data' => $plugin_availability
        );
    }

    ob_end_clean();

    wp_send_json($ajax_response);
}


add_action('wp_ajax_apply_update', 'apply_update');
function apply_update()
{
    // $plugin_availability = check_for_plugin_update();

    $apply_update = update_plugin_from_github();

    $ajax_response = array();

    if ($apply_update['success']) {
        $ajax_response = array(
            'success' => true,
            'data' => $apply_update
        );
    } else {
        $ajax_response = array(
            'success' => false,
            'data' => $apply_update
        );
    }

    ob_end_clean();

    wp_send_json($ajax_response);
}


// add_action('admin_init', 'display_update_notice');
function display_update_notice()
{
?>
    <div id="update_notice" class="notice update-message notice-warning notice-alt">
        <p id="update_message">
            <span id="update_text">Click here to Check for Updates.</span>
            <span class="link" id="check__updates">Check updates</span>.
        </p>
    </div>
<?php
}


function check_for_plugin_update()
{
    $installed_version = current_plugin_version();

    $latest_version = get_latest_github_release_version();

    if ($installed_version !== false && $latest_version !== false) {
        return array(
            'available' => true,
            'message' => 'There is a new version of Order Stock Management ('  . $latest_version . ') is available (old v' . $installed_version . '). '
        );
    } else {
        return array(
            'available' => false,
            'message' => 'Failed to retrieve version information.'
        );
    }
}



function update_plugin_from_github()
{
    WP_Filesystem();

    $plugin_slug = 'stock-management/stock-management.php';

    $destination_zip = download_plugin();

    if (!$destination_zip['success']) {
        return $destination_zip;
    }

    // deactivate_plugins($plugin_slug);

    // replace_new_plugin($destination_zip);

    // activate_plugin($plugin_slug);

    // error_log_console("temp_zip", $temp_zip);
}


function download_plugin()
{
    // Download the latest release ZIP file from GitHub to /tmp/
    $download_url = 'https://github.com/pakday/stock-management/archive/refs/heads/main.zip';
    $temp_zip = download_url($download_url);

    if (is_wp_error($temp_zip)) {
        $error_message = 'Error downloading ZIP file: ' . $temp_zip->get_error_message();

        return array(
            'success' => false,
            'message' => $error_message
        );
    }

    // wp-content/upgrade/stock-management-main.zip
    $destination_zip = WP_CONTENT_DIR . '/upgrade/' . basename($temp_zip);

    // Copy zip file from /tmp to /wp-content/upgrade with new name
    copy($temp_zip, $destination_zip);
    @unlink($temp_zip);

    return $destination_zip;
}


function replace_new_plugin($destination_zip)
{
    $unzip_result = unzip_file($destination_zip, WP_PLUGIN_DIR);
    @unlink($destination_zip);

    if (is_wp_error($unzip_result)) {
        // Error occurred during unzip
        error_log('Error unzipping file: ' . $unzip_result->get_error_message());
        return;
    }

    $filename = pathinfo($destination_zip, PATHINFO_FILENAME);

    // Get the name of the extracted folder
    $extracted_folder = trailingslashit(WP_PLUGIN_DIR) . $filename;
    $new_folder_name = trailingslashit(WP_PLUGIN_DIR) . 'stock-managementzz';

    if (file_exists($new_folder_name)) {
        error_log('Folder already exist.');

        return false;
    }

    if (!rename($extracted_folder, $new_folder_name)) {
        // Error occurred during renaming
        error_log('Error renaming folder.');
    }
}


function current_plugin_version()
{
    $plugin_data = get_plugin_data(plugin_dir_path(__FILE__) . 'stock-management.php');

    $installed_version = !empty($plugin_data['Version']) ? $plugin_data['Version'] : false;

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

    if (!isset($data['tag_name'])) {
        error_log('Error: ' . $data['message']);
    }

    // Extract latest release version
    $latest_version = isset($data['tag_name']) ? $data['tag_name'] : false;

    return $latest_version;
}


add_filter('in_plugin_update_message-stock-management/stock-management.php', 'update_message', 10, 2);
function update_message($data, $response)
{
    printf("There is a new version of this plugin available.");
}
