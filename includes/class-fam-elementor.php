<?php

class FAM_Elementor {
    public function __construct() {
        // Check if Elementor is installed and activated
        if (did_action('elementor/loaded')) {
            add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
            add_action('wp_ajax_fam_load_public_folder', array($this, 'ajax_load_public_folder'));
            add_action('wp_ajax_nopriv_fam_load_public_folder', array($this, 'ajax_load_public_folder'));
            add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_styles']);
        }
    }

    public function register_widgets() {
        // Check if Elementor is active
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        require_once FAM_PLUGIN_DIR . 'widgets/class-fam-archive-widget.php';
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new FAM_Archive_Widget());
    }

    public function ajax_load_public_folder() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to view this archive.', 'file-archive-manager'));
        }
        $archive_id = intval($_POST['archive_id']);
        $folder_id = isset($_POST['folder_id']) ? intval($_POST['folder_id']) : null;
        $breadcrumb = isset($_POST['breadcrumb']) ? $_POST['breadcrumb'] : [];
        $folder = new FAM_Folder();
        $file = new FAM_File();
        $current_folder = $folder_id ? $folder->get($folder_id) : null;
        $folders = $folder_id ? $folder->get_children($folder_id) : $folder->get_children(null);
        $files = $folder_id ? $file->get_by_folder($folder_id) : [];
        // محاسبه حجم و تاریخ آخرین ویرایش فولدر
        function fam_get_folder_stats($folder_id, $file) {
            $files = $file->get_by_folder($folder_id);
            $total_size = 0;
            $last_modified = 0;
            foreach ($files as $f) {
                $total_size += $f->file_size;
                $mod = strtotime($f->updated_at);
                if ($mod > $last_modified) $last_modified = $mod;
            }
            return [
                'size' => $total_size,
                'last_modified' => $last_modified
            ];
        }
        ob_start();
        ?>
        <div class="fam-breadcrumb fam-rtl">
            <a href="#" class="fam-breadcrumb-link" data-folder-id="0"><?php _e('Root', 'file-archive-manager'); ?></a>
            <?php
            if (!empty($breadcrumb)) {
                foreach ($breadcrumb as $crumb) {
                    echo ' / <a href="#" class="fam-breadcrumb-link" data-folder-id="' . esc_attr($crumb['id']) . '">' . esc_html($crumb['name']) . '</a>';
                }
            }
            ?>
        </div>
        <div class="fam-items fam-rtl">
            <?php foreach ($folders as $f) : 
                $stats = fam_get_folder_stats($f->id, $file);
                $size = fam_format_size($stats['size']);
                $lastmod = $stats['last_modified'] ? date_i18n('Y/m/d H:i', $stats['last_modified']) : '-';
            ?>
                <div class="fam-item fam-folder-item" data-folder-id="<?php echo esc_attr($f->id); ?>">
                    <div class="fam-item-icon"><i class="fas fa-folder"></i></div>
                    <div class="fam-item-name"><?php echo esc_html($f->name); ?></div>
                    <div class="fam-item-desc fam-folder-desc">
                        <span class="fam-folder-size"><?php echo $size; ?></span> |
                        <span class="fam-folder-modified"><?php echo $lastmod; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php foreach ($files as $file_item) : 
                $icon = fam_get_file_icon($file_item->name);
            ?>
                <div class="fam-item fam-file-item">
                    <div class="fam-item-icon"><i class="fas <?php echo esc_attr($icon); ?>"></i></div>
                    <div class="fam-item-name">
                        <a href="<?php echo esc_url($file->get_download_url($file_item->id)); ?>" target="_blank"><?php echo esc_html($file_item->name); ?></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success($html);
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'fam-archive-widget',
            FAM_PLUGIN_URL . 'widgets/css/fam-archive-widget.css',
            [],
            FAM_VERSION
        );
    }
}

if (!function_exists('fam_get_file_icon')) {
    function fam_get_file_icon($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $icons = [
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'ppt' => 'fa-file-powerpoint',
            'pptx' => 'fa-file-powerpoint',
            'jpg' => 'fa-file-image',
            'jpeg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'gif' => 'fa-file-image',
            'mp4' => 'fa-file-video',
            'avi' => 'fa-file-video',
            'mov' => 'fa-file-video',
            'mp3' => 'fa-file-audio',
            'wav' => 'fa-file-audio',
            'zip' => 'fa-file-archive',
            'rar' => 'fa-file-archive',
            'txt' => 'fa-file-alt',
        ];
        return isset($icons[$ext]) ? $icons[$ext] : 'fa-file';
    }
}
if (!function_exists('fam_format_size')) {
    function fam_format_size($bytes) {
        if ($bytes == 0) return '0 B';
        $sizes = array('B', 'KB', 'MB', 'GB', 'TB');
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $sizes[$i];
    }
}

// Only define the widget class if Elementor is active
if (class_exists('\Elementor\Widget_Base')) {
    class FAM_Archive_Viewer_Widget extends \Elementor\Widget_Base {
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

        protected function _register_controls() {
            // Archive Selection
            $this->start_controls_section(
                'section_archive',
                [
                    'label' => __('Archive Settings', 'file-archive-manager'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $archives = new FAM_Archive();
            $archive_list = $archives->get_all();
            $archive_options = array();
            foreach ($archive_list as $archive) {
                $archive_options[$archive->id] = $archive->name;
            }

            $this->add_control(
                'archive_id',
                [
                    'label' => __('Select Archive', 'file-archive-manager'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => $archive_options,
                    'default' => key($archive_options),
                ]
            );

            $this->add_control(
                'display_type',
                [
                    'label' => __('Display Type', 'file-archive-manager'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'list' => __('List', 'file-archive-manager'),
                        'accordion' => __('Accordion', 'file-archive-manager'),
                        'grid' => __('Grid', 'file-archive-manager'),
                    ],
                    'default' => 'list',
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
                'text_color',
                [
                    'label' => __('Text Color', 'file-archive-manager'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .fam-archive-viewer' => 'color: {{VALUE}};',
                    ],
                ]
            );

            $this->add_control(
                'icon_size',
                [
                    'label' => __('Icon Size', 'file-archive-manager'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em'],
                    'range' => [
                        'px' => [
                            'min' => 10,
                            'max' => 100,
                        ],
                        'em' => [
                            'min' => 1,
                            'max' => 10,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .fam-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->add_control(
                'spacing',
                [
                    'label' => __('Spacing', 'file-archive-manager'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'em'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                        'em' => [
                            'min' => 0,
                            'max' => 10,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .fam-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    ],
                ]
            );

            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            
            if (!is_user_logged_in()) {
                echo '<div class="fam-login-required fam-rtl">' . __('Please log in to view this archive.', 'file-archive-manager') . '</div>';
                return;
            }

            $archive = new FAM_Archive();
            $folder = new FAM_Folder();
            $file = new FAM_File();

            $archive_data = $archive->get($settings['archive_id']);
            if (!$archive_data) {
                echo '<div class="fam-error fam-rtl">' . __('Archive not found.', 'file-archive-manager') . '</div>';
                return;
            }

            $folders = $folder->get_children(null);
            
            echo '<div id="fam-archive-viewer" class="fam-archive-viewer fam-rtl fam-blue">';
            echo '<div class="fam-breadcrumb fam-rtl">'
                . '<a href="#" class="fam-breadcrumb-link" data-folder-id="0">' . __('Root', 'file-archive-manager') . '</a>'
                . '</div>';
            echo '<div class="fam-items fam-rtl">';
            foreach ($folders as $f) {
                echo '<div class="fam-item fam-folder-item" data-folder-id="' . esc_attr($f->id) . '">';
                echo '<div class="fam-item-icon"><i class="fas fa-folder"></i></div>';
                echo '<div class="fam-item-name">' . esc_html($f->name) . '</div>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
            ?>
            <script>
            jQuery(document).ready(function($) {
                var archiveId = <?php echo intval($settings['archive_id']); ?>;
                var breadcrumb = [];
                function loadFolder(folderId, crumbArr) {
                    $.post(famPublic.ajaxurl, {
                        action: 'fam_load_public_folder',
                        archive_id: archiveId,
                        folder_id: folderId,
                        breadcrumb: crumbArr,
                        nonce: famPublic.nonce
                    }, function(response) {
                        if (response.success) {
                            $('#fam-archive-viewer').html(response.data);
                        }
                    });
                }
                $(document).on('click', '.fam-folder-item', function() {
                    var folderId = $(this).data('folder-id');
                    var folderName = $(this).find('.fam-item-name').text();
                    breadcrumb.push({id: folderId, name: folderName});
                    loadFolder(folderId, breadcrumb);
                });
                $(document).on('click', '.fam-breadcrumb-link', function(e) {
                    e.preventDefault();
                    var folderId = $(this).data('folder-id');
                    if (folderId == 0) {
                        breadcrumb = [];
                    } else {
                        var idx = $(this).index();
                        breadcrumb = breadcrumb.slice(0, idx);
                    }
                    loadFolder(folderId, breadcrumb);
                });
            });
            </script>
            <?php
        }
    }
} 