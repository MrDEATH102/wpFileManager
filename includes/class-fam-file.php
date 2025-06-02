<?php

class FAM_File {
    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'fam_files';
    }

    public function create($folder_id, $file_data, $external_url = null) {
        if ($external_url) {
            $result = $this->wpdb->insert(
                $this->table_name,
                array(
                    'folder_id' => $folder_id,
                    'name' => sanitize_text_field($file_data['name']),
                    'file_path' => '',
                    'file_type' => sanitize_text_field($file_data['type']),
                    'file_size' => 0,
                    'external_url' => esc_url_raw($external_url)
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s')
            );
        } else {
            if (!function_exists('media_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }
            // آپلود فایل به کتابخانه رسانه وردپرس
            $attachment_id = media_handle_upload('file', 0);
            if (is_wp_error($attachment_id)) {
                return new WP_Error('upload_error', __('Media upload error: ', 'file-archive-manager') . $attachment_id->get_error_message());
            }
            $attachment = get_post($attachment_id);
            $file_path = get_attached_file($attachment_id);
            $file_url = wp_get_attachment_url($attachment_id);
            $file_type = get_post_mime_type($attachment_id);
            $file_size = filesize($file_path);
            $result = $this->wpdb->insert(
                $this->table_name,
                array(
                    'folder_id' => $folder_id,
                    'name' => sanitize_text_field($attachment->post_title . '.' . pathinfo($file_path, PATHINFO_EXTENSION)),
                    'file_path' => $file_url,
                    'file_type' => $file_type,
                    'file_size' => $file_size,
                    'external_url' => null
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s')
            );
            if ($result === false) {
                return new WP_Error('db_error', __('DB error: ', 'file-archive-manager') . $this->wpdb->last_error);
            }
        }
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create file record.', 'file-archive-manager'));
        }
        return $this->wpdb->insert_id;
    }

    public function get($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    public function get_by_folder($folder_id) {
        return $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE folder_id = %d ORDER BY name ASC",
            $folder_id
        ));
    }

    public function get_download_url($id) {
        $file = $this->get($id);
        if (!$file) {
            return false;
        }

        if ($file->external_url) {
            return $file->external_url;
        }

        // Generate secure download token
        $token = wp_create_nonce('fam_download_' . $id);
        return add_query_arg(array(
            'fam_action' => 'download',
            'file_id' => $id,
            'token' => $token
        ), home_url());
    }

    public function increment_download_count($id) {
        return $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->table_name} SET download_count = download_count + 1 WHERE id = %d",
            $id
        ));
    }

    public function update($id, $data) {
        $update_data = array();
        $format = array();

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $format[] = '%s';
        }

        if (isset($data['external_url'])) {
            $update_data['external_url'] = esc_url_raw($data['external_url']);
            $format[] = '%s';
        }

        if (empty($update_data)) {
            return false;
        }

        return $this->wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
    }

    public function delete($id) {
        $file = $this->get($id);
        if (!$file) {
            return false;
        }

        // Delete physical file if it exists
        if (!$file->external_url) {
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['basedir'] . $file->file_path;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        return $this->wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Update file name and external URL by file ID
     * @param int $file_id
     * @param string $new_name
     * @param string $new_url
     * @return bool|int
     */
    public function update_file($file_id, $new_name, $new_url) {
        $data = array('name' => sanitize_text_field($new_name));
        $format = array('%s');
        if (!empty($new_url)) {
            $data['external_url'] = esc_url_raw($new_url);
            $format[] = '%s';
        } else {
            $data['external_url'] = null;
            $format[] = '%s';
        }
        return $this->wpdb->update(
            $this->table_name,
            $data,
            array('id' => $file_id),
            $format,
            array('%d')
        );
    }
} 