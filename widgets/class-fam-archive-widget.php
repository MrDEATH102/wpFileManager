<?php
class FAM_Archive_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'fam_archive_viewer';
    }

    public function get_title() {
        return __('File Archive Viewer', 'file-archive-manager');
    }

    public function get_icon() {
        return 'eicon-folder-o';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        // Archive Selection
        $this->start_controls_section(
            'section_archive',
            [
                'label' => __('Archive Settings', 'file-archive-manager'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $archives = $this->get_archives();
        $this->add_control(
            'archive_id',
            [
                'label' => __('Select Archive', 'file-archive-manager'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $archives,
                'default' => array_key_first($archives),
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Style', 'file-archive-manager'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'folder_color',
            [
                'label' => __('Folder Color', 'file-archive-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .fam-folder-item' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'file_color',
            [
                'label' => __('File Color', 'file-archive-manager'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#444444',
                'selectors' => [
                    '{{WRAPPER}} .fam-file-item' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'folder_icon_gap',
            [
                'label' => __('Folder Icon Gap', 'file-archive-manager'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => ['min' => 0, 'max' => 30],
                    'em' => ['min' => 0, 'max' => 3],
                ],
                'default' => [
                    'size' => 0.5,
                    'unit' => 'em',
                ],
                'selectors' => [
                    '{{WRAPPER}} .fam-folder-label .fa-folder' => 'margin-right: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function get_archives() {
        global $wpdb;
        $archives = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}fam_archives ORDER BY name ASC");
        $options = [];
        
        if (empty($archives)) {
            $options[''] = __('No archives found. Please create an archive first.', 'file-archive-manager');
        } else {
            foreach ($archives as $archive) {
                $options[$archive->id] = $archive->name;
            }
        }
        
        return $options;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $archive_id = !empty($settings['archive_id']) ? absint($settings['archive_id']) : 0;
        if (empty($archive_id)) {
            echo '<p>' . __('Please create an archive first.', 'file-archive-manager') . '</p>';
            return;
        }
        // Add Font Awesome CDN for icons
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous">';
        // Add minimal CSS for expand/collapse
        echo '<style>
        .fam-folder-tree ul { margin-left: 18px; padding-left: 12px; border-left: 1px solid #eee; }
        .fam-folder-item { cursor: pointer; user-select: none; }
        .fam-folder-label { display: inline-flex; align-items: center; }
        .fam-folder-label .fa-folder { margin-right: 0 !important; color: #0073aa; }
        .fam-folder-label .fam-folder-name { margin-right: 0.5em; }
        .fam-folder-label .fa-caret-right { margin-right: 4px; color: #888; }
        .fam-folder-item.collapsed > ul { display: none; }
        .fam-file-list { margin-left: 24px; }
        .fam-file-item { cursor: default; }
        .fam-file-icon { margin-right: 7px; }
        .fam-download-btn { color: #0073aa; margin-left: 6px; }
        .fam-download-btn:hover { color: #005177; }
        </style>';
        // Add JS for expand/collapse
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".fam-folder-label").forEach(function(label) {
                label.addEventListener("click", function(e) {
                    e.stopPropagation();
                    var li = label.closest(".fam-folder-item");
                    if (li) li.classList.toggle("collapsed");
                });
            });
        });
        </script>';
        global $wpdb;
        require_once FAM_PLUGIN_DIR . 'includes/class-fam-folder.php';
        $folder = new FAM_Folder();
        $folders = $folder->get_folders($archive_id, 0);
        if (empty($folders)) {
            echo '<p class="fam-empty">' . __('No root folders found.', 'file-archive-manager') . '</p>';
            return;
        }
        echo '<div class="fam-archive-viewer">';
        echo '<h4>' . __('Folder Structure', 'file-archive-manager') . '</h4>';
        $this->render_folder_tree($folders, $archive_id, 0, $folder);
        echo '</div>';
    }

    private function render_folder_tree($folders, $archive_id, $parent_id = 0, $folder = null) {
        if (empty($folders)) return;
        echo '<ul class="fam-folder-tree">';
        foreach ($folders as $f) {
            if ((int)$f->parent_id === (int)$parent_id) {
                echo '<li class="fam-folder-item collapsed">';
                echo '<span class="fam-folder-label"><i class="fas fa-caret-right"></i><i class="fas fa-folder"></i><span class="fam-folder-name">' . esc_html($f->name) . '</span></span>';
                // Recursively render children
                $children = $folder ? $folder->get_folders($archive_id, $f->id) : [];
                $this->render_folder_tree($children, $archive_id, $f->id, $folder);
                // Show files in this folder
                require_once FAM_PLUGIN_DIR . 'includes/class-fam-file.php';
                $file = new FAM_File();
                $files = $file->get_by_folder($f->id);
                if (!empty($files)) {
                    echo '<ul class="fam-file-list">';
                    foreach ($files as $file_item) {
                        // Always use attachment_id if available for download
                        $file_url = '';
                        if (!empty($file_item->attachment_id)) {
                            $file_url = wp_get_attachment_url($file_item->attachment_id);
                        } elseif (!empty($file_item->external_url)) {
                            $file_url = $file_item->external_url;
                        } elseif (!empty($file_item->file_path)) {
                            $file_url = $file_item->file_path;
                        }
                        $icon = '<i class="fas fa-file fam-file-icon"></i>';
                        $ext = strtolower(pathinfo($file_item->name, PATHINFO_EXTENSION));
                        if (in_array($ext, ['pdf'])) $icon = '<i class="fas fa-file-pdf fam-file-icon"></i>';
                        elseif (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) $icon = '<i class="fas fa-file-image fam-file-icon"></i>';
                        elseif (in_array($ext, ['mp4','avi','mov','wmv','webm'])) $icon = '<i class="fas fa-file-video fam-file-icon"></i>';
                        elseif (in_array($ext, ['mp3','wav','ogg'])) $icon = '<i class="fas fa-file-audio fam-file-icon"></i>';
                        elseif (in_array($ext, ['zip','rar','7z'])) $icon = '<i class="fas fa-file-archive fam-file-icon"></i>';
                        echo '<li class="fam-file-item">';
                        echo $icon . ' ';
                        echo '<a href="' . esc_url($file_url) . '" target="_blank">' . esc_html($file_item->name) . '</a> ';
                        echo '<a href="' . esc_url($file_url) . '" class="fam-download-btn" title="Download" download><i class="fas fa-download"></i></a>';
                        echo '</li>';
                    }
                    echo '</ul>';
                }
                echo '</li>';
            }
        }
        echo '</ul>';
    }
} 