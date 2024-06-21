<?php

$hostname = $_SERVER['HTTP_HOST'];
if ($hostname == 'localhost:10018') {
    define('PLUGIN_DEV', true);
} elseif ($hostname == 'nop.dev-ed.nl/staging') {
    define('PLUGIN_DEV', true);
} else {
    define('PLUGIN_DEV', false);
}

$first_rental_date_meta_key = 'eerste_huurdag';
$return_rental_date_meta_key = 'retourdatum';
