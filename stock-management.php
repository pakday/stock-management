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


include 'helper.php';
include 'test.php';
include '/includes/update-plugin.php';


// JS
function enqueue_custom_script_on_checkout()
{
	if (is_checkout() && !is_wc_endpoint_url()) {
		wp_enqueue_script('check-dates-script', plugin_dir_url(__FILE__) . 'js/check-dates.js', array('jquery'), '1.0', true);
	}
}
add_action('wp_enqueue_scripts', 'enqueue_custom_script_on_checkout');


function hide_payment_option()
{
	wp_enqueue_script('update-plugin-script', plugin_dir_url(__FILE__) . 'js/update-plugin.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'hide_payment_option', 5);


// CSS
if (is_admin()) {
	wp_enqueue_style('sm_style', plugins_url('styles.css', __FILE__), array(), 'all');
}


// jquery/ajax
wp_enqueue_script('ajax-script', get_template_directory_uri(), array('jquery'));
// load ajax in wordpress
wp_localize_script('ajax-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

// include files
include(plugin_dir_path(__FILE__) . '/includes/update-plugin.php');


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

	$order_data = get_order_items_data($order_id);

	custom_order_page_section($order_data, $order_id);
}







function get_order_items_data($order_id)
{
	$order = wc_get_order($order_id);

	// admin_console($order, "order");

	$start_date = $order->get_meta('_section_h21t1g21h2_eerste_huurdag');
	$last_date = $order->get_meta('_section_h21t1g21h2_laatste_huurdag');

	// admin_console($start_date, "start_date");
	// admin_console($last_date, "last_date");

	$order_data = [];

	foreach ($order->get_items() as $item_id => $item) {
		$product_id = $item->get_product_id();
		$product = wc_get_product($product_id);

		// admin_console($item, "item");
		// admin_console($product, "product");

		$custom_number_field_value = get_post_meta($product_id, '_fixed_stock', true);
		// admin_console($custom_number_field_value, "_fixed_stock");

		$fixed_stock = get_post_meta($product_id, '_fixed_stock', true);

		$ordered_quantity = $item->get_quantity();
		$product_name = $product->get_name();
		$stock_status = $product->get_stock_status();
		$available_stock = wc_get_order_item_meta($item_id, '_available_stock', true);
		$unavailable_stock = wc_get_order_item_meta($item_id, '_unavailable_stock', true);
		$total_available_stock = wc_get_order_item_meta($item_id, '_total_available_stock', true);

		$stock_quantity = $product->get_stock_quantity();

		$order_data[] = array(
			'product_id' => $product_id,
			'product_name' => $product_name,
			'stock_status' => $stock_status,
			'ordered_quantity' => $ordered_quantity,
			'available_stock' => $available_stock,
			'unavailable_stock' => $unavailable_stock,
			'stock_quantity' => $stock_quantity,
			'fixed_stock' => $fixed_stock,
			'start_date' => $start_date,
			'last_date' => $last_date,
			'total_available_stock' => $total_available_stock
		);
	}

	return $order_data;
}










function custom_order_page_section($order_data, $order_id)
{
	$order = wc_get_order($order_id);

	$order_status = $order->get_status();
	$order_status_name = wc_get_order_status_name($order_status);

	$start_date = $order->get_meta('_section_h21t1g21h2_eerste_huurdag');
	$last_date = $order->get_meta('_section_h21t1g21h2_laatste_huurdag');

?>
	<div id="stock-management" class="stockbox">
		<table cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th><?= __('Item', 'stock-management'); ?></th>
					<th><?= __('Ordered', 'stock-management'); ?></th>
					<th><?= __('Available', 'stock-management'); ?></th>
					<th><?= __('Unavailable', 'stock-management'); ?></th>
					<?php echo ($order_status == 'processing') ? '<th>' . __('Reduced', 'stock-management') . '</th>' : ''; ?>
					<th>Extra</th>
				</tr>
			</thead>
			<tbody>
				<?php
				// admin_console($order_data);

				foreach ($order_data as $product_data) {
					$stock_quantity = $product_data['stock_quantity'];
					$fixed_stock = $product_data['fixed_stock'];
					$product_id = $product_data['product_id'];
					$product_name = $product_data['product_name'];
					$ordered_quantity = $product_data['ordered_quantity'];
					$total_available_stock = $product_data['total_available_stock'];
					$available_stock = $product_data['available_stock'];
					$unavailable_stock = $product_data['unavailable_stock'];
					$start_date = $product_data['start_date'];
					$last_date = $product_data['last_date'];

					$actual_available_stock = max(0, min($ordered_quantity, $total_available_stock));
					$actual_unavailable_stock = max(0, $ordered_quantity - (int)$total_available_stock);
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
								<?php echo $total_available_stock ?>
							</span>
						</td>
						<td>
							<span style="color: red; font-weight: bold;">
								<?php echo $actual_unavailable_stock ?>
							</span>
						</td>
						<?php
						if ($order_status == 'processing') {
						?>
							<td>
								<span>
									<?php echo $available_stock ?>
								</span>
							</td>
						<?php
						}
						?>
						<td>
							<div>
								<span style="font-weight: bold;">Stock: </span>
								<?php echo $stock_quantity ?>
							</div>
							<div>
								<span style="font-weight: bold;">Fixed Stock: </span>
								<?php echo $fixed_stock ?>
							</div>
							<div>
								<span style="font-weight: bold;">Ordered Quantity: </span>
								<?php echo $ordered_quantity ?>
							</div>
							<div>
								<span style="font-weight: bold;">Available Stock: </span>
								<?php echo $available_stock ?>
							</div>
							<div>
								<span style="font-weight: bold;">Unavailable Stock: </span>
								<?php echo $unavailable_stock ?>
							</div>
							<div>
								<span style="font-weight: bold;">Available Stock In Range: </span>
								<?php echo $actual_available_stock ?>
							</div>
						</td>
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
			<span style="font-weight: bold;"><?= __('Stock Reduced:', 'stock-management'); ?> </span>
			<span><?php echo ($order_status == 'processing' ? __('Yes', 'stock-management') : __('No', 'stock-management')) ?></span>
			<span>( <?php echo $order_status_name ?> )</span>
		</span>
	</div>
<?php
}













function change_order_status_scheduled_daily()
{
	$current_date = current_time('mysql');
	$target_date = date_i18n('j F Y', strtotime($current_date));

	// error_log("ORDERS AUTO EXECUTED AT " . date("Y-m-d H:i:s"));

	$args = array(
		'status' => array('on-hold', 'processing'),
		'limit' => -1,
		'meta_query' => array(
			array(
				'key'     => '_section_h21t1g21h2_laatste_huurdag',
				'value'   => $target_date,
				'compare' => '='
			),
		),
	);

	$orders = wc_get_orders($args);

	// admin_console($orders, "Orders");

	if ($orders) {
		foreach ($orders as $order) {
			$order->update_status('completed', 'Order status updated to completed.');
		}
	}
}

// add_action('admin_init', 'change_order_status_scheduled_daily');


register_activation_hook(__FILE__, 'my_plugin_activation');

function my_plugin_activation()
{
	$target_time = '23:59:59';
	$adjusted_time = adjustTimeForTimezone($target_time);

	wp_schedule_event(strtotime($adjusted_time), 'daily', 'daily_event_hook');
}

add_action('daily_event_hook', 'change_order_status_scheduled_daily');



function adjustTimeForTimezone($target_time)
{
	$site_timezone = wp_timezone();

	// Create DateTime objects for target time and site timezone
	$target_datetime = DateTime::createFromFormat('H:i:s', $target_time);

	// Get the site timezone offset as seconds
	$offset_seconds = $site_timezone->getOffset($target_datetime);

	// Subtract site timezone offset from target time
	$target_datetime->sub(new DateInterval('PT' . abs($offset_seconds) . 'S'));

	return $target_datetime->format('H:i:s');
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


// run on status change
add_action('woocommerce_order_status_changed', 'run_everytime_status_changed', 10, 4);

// run on new order
// add_action('woocommerce_thankyou', 'update_available_unavailable_stock');
// add_action('woocommerce_order_details_after_order_table', 'update_available_unavailable_stock');
add_action('woocommerce_new_order', 'update_available_unavailable_stock');

// run on order items quantity change or on saving order
add_action('woocommerce_new_order_item', 'update_available_unavailable_stock');

// run after product is saved
add_action('save_post', 'run_on_product_save', 10, 2);
function run_on_product_save($product_id, $product)
{
	if ($product->post_type === 'product') {
		$fixed_stock = get_post_meta($product_id, '_fixed_stock', true);

		update_available_unavailable_stock();

		// error_log("Product Fixed Quantity: $fixed_stock");
	}
}

// run on orders trash (when we restore order we change order status and again function runs)
add_action('woocommerce_trash_order', 'run_on_trash');
function run_on_trash($order_id)
{
	$order = wc_get_order($order_id);
	$trash_meta_status = $order->get_meta('_wp_trash_meta_status');

	if ($trash_meta_status === 'wc-processing') {
		reset_fixed_stock($order_id);

		// error_log_console("trash_meta_status: ", $trash_meta_status);
	}
}




function run_everytime_status_changed($order_id, $old_status, $new_status, $order)
{
	// error_log_console('*********************** ON STATUS CHANGED', '');

	$allowed_statuses = [
		'cancelled',
		'refunded',
		'completed',
		'pending',
		'on-hold',
		'failed',
		'ywraq-new',
		'ywraq-pending',
		'ywraq-expired',
		'ywraq-accepted',
		'ywraq-rejected',
		'trash'
	];

	if ($new_status == 'processing') {
		extract_stock_on_processing($order_id);
	} elseif ($old_status == 'processing' && in_array($new_status, $allowed_statuses)) {
		reset_fixed_stock($order_id);
	}
}





function extract_stock_on_processing($order_id)
{
	// error_log('*********************** Stock Extracted On Processing');

	$order = wc_get_order($order_id);

	if ($order) {

		foreach ($order->get_items() as $item_id => $item) {
			$product_id = $item->get_product_id();
			$fixed_stock = get_post_meta($product_id, '_fixed_stock', true);
			$ordered_quantity = $item->get_quantity();


			// update available, unavailable stock
			$available_stock = max(0, min($ordered_quantity, $fixed_stock));
			$unavailable_stock = max(0, $ordered_quantity - (int)$fixed_stock);

			wc_update_order_item_meta($item_id, '_available_stock', $available_stock);
			wc_update_order_item_meta($item_id, '_unavailable_stock', $unavailable_stock);


			// update fixed stock
			$updated_fixed_stock = (int)$fixed_stock - (int)$ordered_quantity;

			if ($updated_fixed_stock <= 0) {
				update_post_meta($product_id, '_fixed_stock', 0);
			} else {
				update_post_meta($product_id, '_fixed_stock', $updated_fixed_stock);
			}
		}

		update_available_unavailable_stock();
	}
}





function reset_fixed_stock($order_id)
{
	// error_log_console('*********************** Reset Fixed Stock', '');

	$order = wc_get_order($order_id);

	if ($order) {

		foreach ($order->get_items() as $item_id => $item) {
			$product_id = $item->get_product_id();
			$fixed_stock = get_post_meta($product_id, '_fixed_stock', true);
			$ordered_quantity = $item->get_quantity();

			// add available stock back to 
			$available_stock = wc_get_order_item_meta($item_id, '_available_stock', true);
			$updated_fixed_stock = (int)$fixed_stock + (int)$available_stock;
			update_post_meta($product_id, '_fixed_stock', $updated_fixed_stock);
		}

		update_available_unavailable_stock();
	}
}





function get_orders_by_statuses_dates()
{
	$args_processing = array(
		'limit'       => -1,
		'status'      => 'processing',
		'orderby'     => 'date',
		'order'       => 'ASC',
	);

	$args_other = array(
		'limit'       => -1,
		'status'      => array(
			'pending',
			'on-hold',
			'completed',
			'cancelled',
			'refunded',
			'failed',
			'ywraq-new',
			'ywraq-pending',
			'ywraq-expired',
			'ywraq-accepted',
			'ywraq-rejected',
		),
		'orderby'     => 'date',
		'order'       => 'ASC',
	);

	$orders_processing = wc_get_orders($args_processing);
	$orders_other = wc_get_orders($args_other);

	$orders = array_merge($orders_processing, $orders_other);

	// Filter out orders with missing IDs
	$orders = array_filter($orders, function ($order) {
		return is_object($order) && $order->get_id();
	});

	return $orders;
}





function get_total_available_stock()
{
	// error_log('*********************** Get Total Available Stock of Product Within Date Range');

	$args = array(
		'limit'       => -1,
		// 'status'      => 'processing',
		'orderby'     => 'date',
		'order'       => 'ASC',
	);

	$orders = wc_get_orders($args);

	if ($orders) {
		foreach ($orders as $current_order) {
			$sum_old_available_stock = array();
			$sum_old_ = array();

			$current_order_status = $current_order->get_status();
			$current_order_id = $current_order->get_id();
			$current_start_date = $current_order->get_meta('_section_h21t1g21h2_eerste_huurdag');
			$current_last_date = $current_order->get_meta('_section_h21t1g21h2_laatste_huurdag');
			// $current_stock = get_post_meta($product_id, '_fixed_stock', true);
			$current_order_items = $current_order->get_items();

			foreach ($current_order_items as $current_item_id => $current_item) {
				$product_id = $current_item->get_product_id();

				$sum_old_[$product_id] = 0;
			}

			$order_id = $current_order->get_id();
			// error_log_console("************* order_id ", $order_id);

			// sum of all reduced stock from available stock
			foreach ($orders as $order) {
				$order_items = $order->get_items();
				$order_status = $order->get_status();
				$first_date = $order->get_meta('_section_h21t1g21h2_eerste_huurdag');
				$last_date = $order->get_meta('_section_h21t1g21h2_laatste_huurdag');

				if ($order_status === 'processing') {
					// error_log_console("******************* last_date ", $last_date);

					if (strtotime($current_start_date) > strtotime($last_date) || strtotime($current_last_date) < strtotime($first_date)) {
						foreach ($order_items as $item_id => $item) {
							$product_id = $item->get_product_id();

							$old_available_stock = wc_get_order_item_meta($item_id, '_available_stock', true);

							foreach ($sum_old_ as $current_product_id => $current_item) {
								if ($product_id == $current_product_id) {
									$sum_old_[$product_id] += intval($old_available_stock);
								}
							}
						}
					}
				}
			}


			$total_available_stock = array();

			foreach ($current_order_items as $current_item_id => $current_item) {
				$product_id = $current_item->get_product_id();
				$current_stock = get_post_meta($product_id, '_fixed_stock', true);
				$current_available_stock = wc_get_order_item_meta($current_item_id, '_available_stock', true);

				if ($current_order_status == 'processing') {
					$total_stock = (int)$sum_old_[$product_id] + (int)$current_stock + (int)$current_available_stock;
				} else {
					$total_stock = (int)$sum_old_[$product_id] + (int)$current_stock;
				}

				$total_available_stock[$product_id] = $total_stock;

				wc_update_order_item_meta($current_item_id, '_total_available_stock', $total_stock);
			}
		}
	}
}





function update_available_unavailable_stock()
{
	// error_log('*********************** Available Unavailable Values Updated');

	$orders = get_orders_by_statuses_dates();

	if ($orders) {
		// error_log_console('orders', $orders);

		foreach ($orders as $order) {
			$order_status = $order->get_status();
			$order_items = $order->get_items();
			$order_id = $order->get_id();

			// error_log_console('*********** order_id: ' . $order_id);

			if ($order_status == 'processing') {
				foreach ($order_items as $item_id => $item) {
					$product_id = $item->get_product_id();
					$ordered_quantity = $item->get_quantity();
					$old_fixed_stock = get_post_meta($product_id, '_fixed_stock', true);
					$old_available_stock = wc_get_order_item_meta($item_id, '_available_stock', true);

					$total_available_stock = wc_get_order_item_meta($item_id, '_total_available_stock', true);

					$new_fixed_stock = (int)$old_fixed_stock + (int)$old_available_stock;

					// update available, unavailable stock
					$available_stock = max(0, min($ordered_quantity, $new_fixed_stock));
					$unavailable_stock = max(0, $ordered_quantity - (int)$new_fixed_stock);

					wc_update_order_item_meta($item_id, '_available_stock', $available_stock);
					wc_update_order_item_meta($item_id, '_unavailable_stock', $unavailable_stock);

					// update fixed stock
					$updated_fixed_stock = $new_fixed_stock - $ordered_quantity;
					// error_log_console('xxxxxxxxxxxxxxx updated_fixed_stock: ' . $updated_fixed_stock);

					if ($updated_fixed_stock < 0) {
						update_post_meta($product_id, '_fixed_stock', 0);
					} else {
						update_post_meta($product_id, '_fixed_stock', $updated_fixed_stock);
					}
				}
			} else {
				foreach ($order_items as $item_id => $item) {
					$product_id = $item->get_product_id();
					$fixed_stock = get_post_meta($product_id, '_fixed_stock', true);
					$ordered_quantity = $item->get_quantity();


					// update available, unavailable stock
					$available_stock = max(0, min($ordered_quantity, $fixed_stock));
					$unavailable_stock = max(0, $ordered_quantity - (int)$fixed_stock);

					wc_update_order_item_meta($item_id, '_available_stock', $available_stock);
					wc_update_order_item_meta($item_id, '_unavailable_stock', $unavailable_stock);
				}
			}
		}
	}

	get_total_available_stock();
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








// create date range for an order manually 

// add_action('woocommerce_process_shop_order_meta', 'add_custom_dates_on_order_create', 10, 1);

function add_custom_dates_on_order_create($order_id)
{
	// error_log('*********************** Set Date Range Manually');

	$first_date = '2 February 2024';
	$last_date = '8 February 2024';

	$order = wc_get_order($order_id);

	$order->update_meta_data('_section_h21t1g21h2_eerste_huurdag', $first_date);
	$order->update_meta_data('_section_h21t1g21h2_laatste_huurdag', $last_date);

	$order->save();
}












// add column in products table
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

	// error_log("mo_file " . $mo_file);
}
