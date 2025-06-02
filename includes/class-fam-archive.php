<?php

class FAM_Archive {
    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'fam_archives';
    }

    public function create($name, $description = '') {
        $slug = sanitize_title($name);
        
        // Check if slug already exists
        $existing = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE slug = %s",
            $slug
        ));

        if ($existing) {
            return new WP_Error('duplicate_slug', __('Archive with this name already exists.', 'file-archive-manager'));
        }

        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'name' => sanitize_text_field($name),
                'slug' => $slug,
                'description' => sanitize_textarea_field($description)
            ),
            array('%s', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create archive.', 'file-archive-manager'));
        }

        return $this->wpdb->insert_id;
    }

    public function get($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    public function get_by_slug($slug) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE slug = %s",
            $slug
        ));
    }

    public function update($id, $data) {
        $update_data = array();
        $format = array();

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
            $update_data['slug'] = sanitize_title($data['name']);
            $format[] = '%s';
            $format[] = '%s';
        }

        if (isset($data['description'])) {
            $update_data['description'] = sanitize_textarea_field($data['description']);
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
        return $this->wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    public function get_all() {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY name ASC"
        );
    }
} 