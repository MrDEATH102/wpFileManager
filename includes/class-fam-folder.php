<?php

// Version: 1.2.5 - 2025-05-26 - Update debug logging for folder update

class FAM_Folder {
    private $wpdb;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'fam_folders';
    }

    public function create($archive_id, $name, $parent_id = null) {
        $slug = sanitize_title($name);
        
        // بررسی تکراری بودن فولدر
        if ($parent_id === null || $parent_id === '' || $parent_id == 0) {
            $sql = $this->wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE archive_id = %d AND parent_id IS NULL AND slug = %s",
                $archive_id,
                $slug
            );
            $parent_id_db = null;
        } else {
            $sql = $this->wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE archive_id = %d AND parent_id = %d AND slug = %s",
                $archive_id,
                $parent_id,
                $slug
            );
            $parent_id_db = intval($parent_id);
        }
        $existing = $this->wpdb->get_var($sql);
        if ($existing) {
            return new WP_Error('duplicate_slug', __('Folder with this name already exists in this location.', 'file-archive-manager'));
        }

        $result = $this->wpdb->insert(
            $this->table_name,
            array(
                'archive_id' => $archive_id,
                'parent_id' => $parent_id_db,
                'name' => sanitize_text_field($name),
                'slug' => $slug
            ),
            array('%d', ($parent_id_db === null ? 'NULL' : '%d'), '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create folder. DB error: ', 'file-archive-manager') . $this->wpdb->last_error);
        }

        return $this->wpdb->insert_id;
    }

    public function get($id) {
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }

    public function get_by_path($archive_id, $path) {
        $path_parts = explode('/', trim($path, '/'));
        $current_parent = null;
        $folder = null;

        foreach ($path_parts as $part) {
            if ($current_parent === null || $current_parent === '' || $current_parent == 0) {
                $sql = $this->wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE archive_id = %d AND parent_id IS NULL AND slug = %s",
                    $archive_id,
                    $part
                );
            } else {
                $sql = $this->wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE archive_id = %d AND parent_id = %d AND slug = %s",
                    $archive_id,
                    $current_parent,
                    $part
                );
            }
            $folder = $this->wpdb->get_row($sql);

            if (!$folder) {
                return null;
            }

            $current_parent = $folder->id;
        }

        return $folder;
    }

    public function get_children($archive_id, $parent_id = null) {
        if ($parent_id === null) {
            return $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE archive_id = %d AND parent_id IS NULL ORDER BY name ASC",
                $archive_id
            ));
        } else {
            return $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE archive_id = %d AND parent_id = %d ORDER BY name ASC",
                $archive_id, $parent_id
            ));
        }
    }

    public function get_tree($archive_id) {
        $folders = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE archive_id = %d ORDER BY parent_id ASC, name ASC",
            $archive_id
        ));

        $tree = array();
        $map = array();

        foreach ($folders as $folder) {
            $folder->children = array();
            $map[$folder->id] = $folder;

            if ($folder->parent_id === null) {
                $tree[] = $folder;
            } else {
                $map[$folder->parent_id]->children[] = $folder;
            }
        }

        return $tree;
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

        if (array_key_exists('parent_id', $data)) {
            if ($data['parent_id'] !== null) {
                $update_data['parent_id'] = intval($data['parent_id']);
                $format[] = '%d';
            }
        }

        if (empty($update_data)) {
            error_log('FAM UPDATE: No data to update');
            return false;
        }

        error_log('FAM UPDATE INPUT: ' . print_r($update_data, true));
        error_log('FAM UPDATE FORMAT: ' . print_r($format, true));
        
        // First check if the folder exists
        $folder = $this->get($id);
        if (!$folder) {
            error_log('FAM UPDATE: Folder not found with ID: ' . $id);
            return false;
        }

        // Check for duplicate slug in the same location
        $slug = $update_data['slug'] ?? $folder->slug;
        $parent_id = $update_data['parent_id'] ?? $folder->parent_id;
        
        if ($parent_id === null || $parent_id === '' || $parent_id == 0) {
            $duplicate_check = $this->wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE archive_id = %d AND parent_id IS NULL AND slug = %s AND id != %d",
                $folder->archive_id,
                $slug,
                $id
            );
        } else {
            $duplicate_check = $this->wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE archive_id = %d AND parent_id = %d AND slug = %s AND id != %d",
                $folder->archive_id,
                $parent_id,
                $slug,
                $id
            );
        }
        $existing = $this->wpdb->get_var($duplicate_check);
        if ($existing) {
            error_log('FAM UPDATE: Duplicate slug found');
            return new WP_Error('duplicate_slug', __('A folder with this name already exists in this location.', 'file-archive-manager'));
        }

        $result = $this->wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        error_log('FAM UPDATE SQL ERROR: ' . $this->wpdb->last_error);
        error_log('FAM UPDATE RESULT: ' . print_r($result, true));

        // If parent_id is null, set it separately
        if (array_key_exists('parent_id', $data) && $data['parent_id'] === null) {
            $this->wpdb->query($this->wpdb->prepare(
                "UPDATE {$this->table_name} SET parent_id = NULL WHERE id = %d",
                $id
            ));
        }

        return $result;
    }

    public function delete($id) {
        return $this->wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Update folder name by ID
     * @param int $folder_id
     * @param string $new_name
     * @return bool
     */
    public function update_folder_name( int $folder_id, string $new_name ): bool {
        global $wpdb;
        return (bool) $wpdb->update(
            "{$wpdb->prefix}fam_folders",
            ['name' => sanitize_text_field($new_name)],
            ['id' => $folder_id],
            ['%s'],
            ['%d']
        );
    }

    public function get_folders( int $archive_id, int $parent_id = 0 ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare("
                SELECT * 
                FROM {$wpdb->prefix}fam_folders
                WHERE archive_id = %d
                  AND COALESCE(parent_id, 0) = %d
                ORDER BY name ASC
            ", $archive_id, $parent_id)
        );
    }
} 