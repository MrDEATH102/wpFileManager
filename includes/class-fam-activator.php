<?php

class FAM_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table names
        $table_archives = $wpdb->prefix . 'fam_archives';
        $table_folders = $wpdb->prefix . 'fam_folders';
        $table_files = $wpdb->prefix . 'fam_files';

        // Archives table
        $sql_archives = "CREATE TABLE $table_archives (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Folders table (with foreign keys in CREATE TABLE, not ALTER)
        $sql_folders = "CREATE TABLE $table_folders (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            archive_id bigint(20) NOT NULL,
            parent_id bigint(20) DEFAULT NULL,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY archive_id (archive_id),
            KEY parent_id (parent_id)
        ) $charset_collate;";

        // Files table (with foreign key in CREATE TABLE, not ALTER)
        $sql_files = "CREATE TABLE $table_files (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            folder_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            file_type varchar(50) NOT NULL,
            file_size bigint(20) NOT NULL,
            external_url varchar(255) DEFAULT NULL,
            download_count bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY folder_id (folder_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_archives);
        dbDelta($sql_folders);
        dbDelta($sql_files);

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $fam_dir = $upload_dir['basedir'] . '/file-archive-manager';
        if (!file_exists($fam_dir)) {
            wp_mkdir_p($fam_dir);
        }

        // Add .htaccess to protect direct access
        $htaccess_file = $fam_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }
} 