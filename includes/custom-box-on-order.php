<?php

add_action('add_meta_boxes', 'custom_order_meta_box');
function custom_order_meta_box()
{
    add_meta_box(
        'order_stock_management', // Unique ID
        __('Available Stock Management', 'stock-management'), // Box title
        'order_stock_management_callback', // Content callback function
        'shop_order', // screen id
        'normal',
        'high'
    );
}

function order_stock_management_callback($post)
{
    $order_id = $post->ID;

    custom_order_page_section($order_id);
}

function custom_order_page_section($order_id)
{
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
                    <?php echo (defined('PLUGIN_DEV') && PLUGIN_DEV) ? '<th>Extra</th>' : ''; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($order->get_items() as $item_id => $item) {
                    $product_id = $item->get_product_id();
                    $product = wc_get_product($product_id);
                    $product_name = $product->get_name();
                    $ordered_quantity = $item->get_quantity();

                    $available_stock = wc_get_order_item_meta($item_id, '_available_stock', true);
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
                        if (defined('PLUGIN_DEV') && PLUGIN_DEV) {
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
            <span style="font-weight: bold;"><?= __('Range', 'stock-management'); ?>:</span>
            <span><?php echo $start_date ?></span>
            <span>&nbsp;--</span>
            <span><?php echo $last_date ?></span>
        </span>
        <span style="margin-left: 10px">
            <span style="font-weight: bold;"><?= __('Status', 'stock-management'); ?>:</span>
            <span><?php echo $order_status_name ?></span>
        </span>
    </div>
<?php
}


function beauty_console($data)
{
    ob_start();
?>
    <pre style="max-height: 300px; margin: 0; max-width: 450px; overflow: auto; border:1px solid #aaa;">
		<?php
        echo '<style>code { background-color: #fff; }</style>';
        echo '<code style="background-color: #fff;">' . highlight_string("<?php\n" . var_export($data, true) . "", true) . '</code>';
        ?>
    </pre>
<?php
    $output = ob_get_clean();

    echo $output;
}
