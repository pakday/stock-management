<?php
/*
Plugin Name: Order Stock Management
Version: 1.0.0
GitHub Plugin URI: https://github.com/pakday/stock-management
Description: Manage stock in a date range
Author: Arif Ali
Author URI: https://www.fiverr.com/arifali30/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: stock-management
*/

// update processing status time
// reset stock in product
// change dates meta data with new plugin
// add hooks when updates happen in data ranges. main program

include 'helper.php';
include 'temp.php';

// global variables
$PLUGIN_ENV = "dev";
$first_rental_date_meta_key = 'eerste_huurdag';
$return_rental_date_meta_key = 'retourdatum';


// Run every time status change
add_action('woocommerce_order_status_changed', 'save_last_order_status_change_time', 10, 1);
function save_last_order_status_change_time($order_id)
{
	update_post_meta($order_id, '_order_status_last_modified', current_time('mysql'));
}

// add_action('admin_init', 'set_available_stock');













// Set default order status as on-hold

add_action('woocommerce_thankyou', 'custom_change_order_status_to_on_hold', 10, 1);

function custom_change_order_status_to_on_hold($order_id)
{
	if (!$order_id) {
		return;
	}

	$order = wc_get_order($order_id);

	// Change order status to 'on-hold'
	$order->update_status('on-hold', 'Order status changed to on-hold by custom function.');
}










/*
 *
 * 
 * 
 * HOOKS
 * 
 *
 * 
 */




// // run on new order
// // add_action('woocommerce_thankyou', 'update_available_unavailable_stock');
// // add_action('woocommerce_order_details_after_order_table', 'update_available_unavailable_stock');
// add_action('woocommerce_new_order', 'update_available_unavailable_stock');

// // run on order items quantity change or on saving order
// add_action('woocommerce_new_order_item', 'update_available_unavailable_stock');

// // run after product is saved
// add_action('save_post', 'run_on_product_save', 10, 2);
// function run_on_product_save($product_id, $product)
// {
// 	if ($product->post_type === 'product') {
// 		$fixed_stock = get_post_meta($product_id, '_fixed_stock', true);

// 		update_available_unavailable_stock();

// 		// error_log("Product Fixed Quantity: $fixed_stock");
// 	}
// }

// // run on orders trash (when we restore order we change order status and again function runs)
// add_action('woocommerce_trash_order', 'run_on_trash');
// function run_on_trash($order_id)
// {
// 	$order = wc_get_order($order_id);
// 	$trash_meta_status = $order->get_meta('_wp_trash_meta_status');

// 	if ($trash_meta_status === 'wc-processing') {
// 		reset_fixed_stock($order_id);

// 		// error_log_console("trash_meta_status: ", $trash_meta_status);
// 	}
// }




add_action('admin_init', 'all_orders');

function all_orders()
{
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

// function run_all_overlap_orders($order_id)
// {
// 	$current_order = wc_get_order($order_id);
// }

function set_available_unavailable_stock($order_id)
{
	// $order_id = 923;
	$current_order = wc_get_order($order_id);
	$current_order_status = $current_order->get_status();
	$current_order_status_last_modified = $current_order->get_meta('_order_status_last_modified');

	$overlap_orders = find_overlap_orders($current_order);

	// error_log_console("overlap_product_id: ", $overlap_orders);

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
					$available_stock = intval(wc_get_order_item_meta($overlap_item_id, '_available_stock_2', true));

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

		wc_update_order_item_meta($current_item_id, '_available_stock_2', $current_available_stock);

		if ($current_order_status == "processing") {
			wc_update_order_item_meta($current_item_id, '_used_stock', $current_used_stock);
		}

		global $PLUGIN_ENV;
		if ($PLUGIN_ENV == 'dev') {
			$order_data = array(
				'product_fixed_stock' => intval($current_product_fixed_stock),
				'total_used_stock' => $item_total_available_value,
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

	// error_log_console("current_start_date: ", $current_start_date);
	// error_log_console("current_end_date: ", $current_end_date);
	// error_log_console("current_end_timestamp: ", $current_end_timestamp);
	// error_log_console("current_start_timestamp: ", $current_start_timestamp);

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















// JS
function enqueue_custom_script_on_checkout()
{
	if (is_checkout() && !is_wc_endpoint_url()) {
		wp_enqueue_script('check-dates-script', plugin_dir_url(__FILE__) . 'js/check-dates.js', array('jquery'), '1.0', true);
	}
}
add_action('wp_enqueue_scripts', 'enqueue_custom_script_on_checkout');


// CSS
if (is_admin()) {
	wp_enqueue_style('sm_style', plugins_url('styles.css', __FILE__), array(), 'all');
}


// jquery/ajax
wp_enqueue_script('ajax-script', get_template_directory_uri(), array('jquery'));
// load ajax in wordpress
wp_localize_script('ajax-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));


function custom_order_meta_box()
{
	if (isset($_GET['page']) && $_GET['page'] === 'wc-orders') {
		add_meta_box(
			'order_stock_management', // Unique ID
			__('Available Stock Management', 'stock-management'), // Box title
			'order_stock_management_callback', // Content callback function
			'woocommerce_page_wc-orders',
			'normal',
			'high'
		);
	} else {
		add_meta_box(
			'order_stock_management', // Unique ID
			__('Available Stock Management', 'stock-management'), // Box title
			'order_stock_management_callback', // Content callback function
			'shop_order', // screen id
			'normal',
			'high'
		);
	}
}
add_action('add_meta_boxes', 'custom_order_meta_box');

function order_stock_management_callback($post)
{
	$order_id = $post->ID;

	custom_order_page_section($order_id);
}









function custom_order_page_section($order_id)
{
	global $PLUGIN_ENV;

	$order = wc_get_order($order_id);

	// Order date range
	global $first_rental_date_meta_key;
	global $return_rental_date_meta_key;
	$start_date = $order->get_meta($first_rental_date_meta_key);
	$last_date = $order->get_meta($return_rental_date_meta_key);

	// Order status
	$order_status = $order->get_status();
	$order_status_name = wc_get_order_status_name($order_status);
?>
	<div id="stock-management" class="stockbox">
		<table cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th><?= __('Item', 'stock-management'); ?></th>
					<th><?= __('Ordered', 'stock-management'); ?></th>
					<th><?= __('Available', 'stock-management'); ?></th>
					<th><?= __('Unavailable', 'stock-management'); ?></th>
					<?php echo ($PLUGIN_ENV == 'dev') ? '<th>Extra</th>' : ''; ?>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($order->get_items() as $item_id => $item) {
					$product_id = $item->get_product_id();
					$product = wc_get_product($product_id);
					$product_name = $product->get_name();
					$ordered_quantity = $item->get_quantity();

					$available_stock = wc_get_order_item_meta($item_id, '_available_stock_2', true);
					$unavailable_stock = max(0, $ordered_quantity - (int)$available_stock);

					$data_flow = wc_get_order_item_meta($item_id, '_dependent_stock', true);
				?>
					<tr>
						<td width="40%">
							<a href='<?php echo esc_url(get_edit_post_link($product_id)); ?>' target="_blank">
								<?php echo $product_name ?>
							</a>
						</td>
						<td>
							<span><?php echo $ordered_quantity ?></span>
						</td>
						<td>
							<span style="color: green; font-weight: bold;">
								<?php echo $available_stock ?>
							</span>
						</td>
						<td>
							<span style="color: red; font-weight: bold;">
								<?php echo $unavailable_stock ?>
							</span>
						</td>
						<?php
						if ($PLUGIN_ENV == 'dev') {
						?>
							<td>
								<span>
									<?php beauty_console($data_flow); ?>
								</span>
							</td>
						<?php
						}
						?>
					</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	</div>
	<div id="notes">
		<span>
			<span style="font-weight: bold;"><?= __('Range:', 'stock-management'); ?> </span>
			<span><?php echo $start_date ?></span>
			<span>&nbsp;--</span>
			<span><?php echo $last_date ?></span>
		</span>
		<span style="margin-left: 10px">
			<span style="font-weight: bold;"><?= __('Status:', 'stock-management'); ?> </span>
			<span><?php echo $order_status_name ?></span>
		</span>
	</div>
<?php
}




function beauty_console($data)
{
	ob_start();
?>
	<pre style="max-height: 300px; margin: 0; max-width: 400px; overflow: auto; border:1px solid #aaa;">
		<?php
		echo '<style>code { background-color: #fff; }</style>';
		echo '<code style="background-color: #fff;">' . highlight_string("<?php\n" . var_export($data, true) . "", true) . '</code>';
		?>
    </pre>
<?php
	$output = ob_get_clean();

	echo $output;
}


















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











// load i18n translations
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
