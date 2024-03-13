<?php

function admin_console($data, $label = null, $maxHeight = 500)
{
    ob_start();
?>
    <div class="notice notice-info">
        <pre style="max-height: <?php echo $maxHeight; ?>px; padding-left: 10px; max-width: 80%; overflow: auto; border:1px solid #aaa;">
            <?php if (isset($label)) {
                echo '<h3 style="margin: 0; padding: 0;">' . $label . '</h3>';
            }

            echo '<style>';
            echo 'code { background-color: #fff; }';
            echo '</style>';
            echo '<code style="background-color: #fff;">' . highlight_string("<?php\n" . var_export($data, true) . "", true) . '</code>'; ?>
        </pre>
    </div>
<?php
    $output = ob_get_clean();

    echo $output;
}

function error_log_console($label = null, $data = null)
{
    $logData = print_r($data, true);

    $errorLogMessage = "\n" . "CUSTOM MESSAGE: " . $label . ': ' . $logData . "\n";

    error_log($errorLogMessage);
}
