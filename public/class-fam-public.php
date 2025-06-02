<?php

class FAM_Public {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'handle_file_download'));
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'fam-public',
            FAM_PLUGIN_URL . 'public/css/fam-public.css',
            array(),
            FAM_VERSION
        );

        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            array(),
            '5.15.4'
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'fam-public',
            FAM_PLUGIN_URL . 'public/js/fam-public.js',
            array('jquery'),
            FAM_VERSION,
            true
        );

        wp_localize_script('fam-public', 'famPublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fam-public-nonce')
        ));
    }

    public function handle_file_download() {
        if (!isset($_GET['fam_action']) || $_GET['fam_action'] !== 'download') {
            return;
        }

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to download files.', 'file-archive-manager'));
        }

        $file_id = intval($_GET['file_id']);
        $token = sanitize_text_field($_GET['token']);

        if (!wp_verify_nonce($token, 'fam_download_' . $file_id)) {
            wp_die(__('Invalid download token.', 'file-archive-manager'));
        }

        $file = new FAM_File();
        $file_data = $file->get($file_id);

        if (!$file_data) {
            wp_die(__('File not found.', 'file-archive-manager'));
        }

        if ($file_data->external_url) {
            wp_redirect($file_data->external_url);
            exit;
        }

        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . $file_data->file_path;

        if (!file_exists($file_path)) {
            wp_die(__('File not found on server.', 'file-archive-manager'));
        }

        // Increment download count
        $file->increment_download_count($file_id);

        // Set headers for file download
        header('Content-Type: ' . $file_data->file_type);
        header('Content-Disposition: attachment; filename="' . basename($file_data->name) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output file
        readfile($file_path);
        exit;
    }
} 