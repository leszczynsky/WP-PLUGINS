<?php

/**
 * Plugin Name: Konfigurator Fastar
 * Plugin URI:
 * Description: Konfigurator usług
 * Version: 1.0.0
 * Author: <a href="https://lepszastrona.net">LepszaStrona.net</a>
 */


// set constants
define('PROFORMAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PROFORMAT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Require all files from shortcodes directory
foreach (glob(PROFORMAT_PLUGIN_DIR . 'shortcodes/*.php') as $file) {
    require_once $file;
}