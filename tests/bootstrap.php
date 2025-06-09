<?php
// Minimal bootstrap for tests
// Define stubs for WordPress functions used in FAM_File
if (!class_exists('WP_UnitTestCase')) {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }
    class WP_UnitTestCase extends PHPUnit\Framework\TestCase {}
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'nonce-' . $action;
    }
}
if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = '') {
        $query = http_build_query($args);
        if (strpos($url, '?') === false) {
            return $url . '?' . $query;
        }
        return $url . '&' . $query;
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '') {
        return 'https://example.org';
    }
}
