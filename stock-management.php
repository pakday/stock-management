<?php
/*
Plugin Name: Order Stock Management
Version: 1.1.0
GitHub Plugin URI: https://github.com/pakday/stock-management
Description: Manage stock in a date range
Author: Arif Ali
Author URI: https://www.fiverr.com/arifali30/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: stock-management
*/


include 'globals.php';
include 'includes/helper.php';


// JS
function enqueue_custom_script_on_checkout()
{
	if (is_checkout() && !is_wc_endpoint_url()) {
		wp_enqueue_script('check-dates-script', plugin_dir_url(__FILE__) . 'assets/js/check-dates.js', array('jquery'), '1.0', true);
	}
}
add_action('wp_enqueue_scripts', 'enqueue_custom_script_on_checkout');


// CSS
function enqueue_sm_admin_style()
{
	wp_enqueue_style('sm_style', plugins_url('assets/css/styles.css', __FILE__), array(), 'all');
}
add_action('admin_enqueue_scripts', 'enqueue_sm_admin_style');


// Load i18n translations
add_action('plugins_loaded', 'wpdocs_load_textdomain');
function wpdocs_load_textdomain()
{
	load_plugin_textdomain('stock-management', false, dirname(plugin_basename(__FILE__)) . '/languages');
	load_default_nl_textdomain();
}
function load_default_nl_textdomain()
{
	$plugin_path = dirname(plugin_basename(__FILE__));
	$locale = 'nl_NL';
	$mo_file = $plugin_path . '/languages/stock-management-' . $locale . '.mo';

	load_textdomain('stock-management', $mo_file);
}

// include 'includes/temp.php';

include 'includes/calculate-available-items.php';
include 'includes/custom-box-on-order.php';
include 'includes/product-custom-field.php';
