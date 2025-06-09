<?php
/**
 * Plugin Name: File Archive Manager
 * Plugin URI: https://themsah.com
* Description: A file management system with Elementor integration for WordPress
 * Version: 1.4.6
 * Author: Themsah
 * Author URI: https://themsah.com
 * Text Domain: file-archive-manager
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FAM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FAM_VERSION', '1.0.0');
define('FAM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FAM_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once FAM_PLUGIN_DIR . 'includes/class-fam-loader.php';
require_once FAM_PLUGIN_DIR . 'includes/class-fam-activator.php';
require_once FAM_PLUGIN_DIR . 'includes/class-fam-deactivator.php';
require_once FAM_PLUGIN_DIR . 'includes/class-fam-elementor.php';

register_activation_hook(__FILE__, array('FAM_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('FAM_Deactivator', 'deactivate'));

function run_file_archive_manager() {
    $plugin = new FAM_Loader();
    $plugin->run();
}

// Initialize Elementor integration
if (did_action('elementor/loaded')) {
    new FAM_Elementor();
}

run_file_archive_manager(); 