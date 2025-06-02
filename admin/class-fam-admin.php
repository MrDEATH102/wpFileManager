<?php
class FAM_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_fam_create_archive', array($this, 'ajax_create_archive'));
        add_action('wp_ajax_fam_create_folder', array($this, 'ajax_create_folder'));
        add_action('wp_ajax_fam_upload_file', array($this, 'ajax_upload_file'));
        add_action('wp_ajax_fam_delete_item', array($this, 'ajax_delete_item'));
        add_action('wp_ajax_fam_edit_archive', array($this, 'ajax_edit_archive'));
        add_action('wp_ajax_fam_load_archive', array($this, 'ajax_load_archive'));
        add_action('wp_ajax_fam_edit_folder', [$this, 'ajax_edit_folder']);
        add_action('wp_ajax_fam_edit_file', array($this, 'ajax_edit_file'));
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            __('File Archive Manager', 'file-archive-manager'),
            __('File Archives', 'file-archive-manager'),
            'manage_options',
            'file-archive-manager',
            array($this, 'display_plugin_admin_page'),
            'dashicons-category',
            30
        );
    }

    public function enqueue_styles($hook) {
        if ('toplevel_page_file-archive-manager' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'fam-admin',
            FAM_PLUGIN_URL . 'admin/css/fam-admin.css',
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

    public function enqueue_scripts($hook) {
        if ('toplevel_page_file-archive-manager' !== $hook) {
            return;
        }

        wp_enqueue_script('jquery');

        wp_enqueue_script(
            'fam-admin',
            FAM_PLUGIN_URL . 'admin/js/fam-admin.js',
            array('jquery'),
            FAM_VERSION,
            true
        );

        wp_localize_script('fam-admin', 'famAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fam-admin-nonce'),
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this item?', 'file-archive-manager'),
                'error' => __('An error occurred. Please try again.', 'file-archive-manager')
            )
        ));
    }

    public function display_plugin_admin_page() {
        $archive = new FAM_Archive();
        $archives = $archive->get_all();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="fam-admin-container">
                <div class="fam-sidebar">
                    <div class="fam-actions">
                        <button type="button" class="button button-primary" id="fam-add-archive">
                            <i class="fas fa-plus"></i> <?php _e('Add New Archive', 'file-archive-manager'); ?>
                        </button>
                    </div>
                    
                    <div class="fam-archives-list">
                        <?php if (!empty($archives)) : ?>
                            <ul>
                                <?php foreach ($archives as $archive_item) : ?>
                                    <li data-archive-id="<?php echo esc_attr($archive_item->id); ?>">
                                        <span class="archive-name"><?php echo esc_html($archive_item->name); ?></span>
                                        <div class="archive-actions">
                                            <button type="button" class="button button-small edit-archive">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="button button-small delete-archive">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <p class="no-archives"><?php _e('No archives found. Create your first archive!', 'file-archive-manager'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="fam-content">
                    <div id="fam-archive-content">
                        <div class="fam-welcome">
                            <h2><?php _e('Welcome to File Archive Manager', 'file-archive-manager'); ?></h2>
                            <p><?php _e('Select an archive from the sidebar or create a new one to get started.', 'file-archive-manager'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Archive Modal -->
        <div id="fam-add-archive-modal" class="fam-modal">
            <div class="fam-modal-content">
                <span class="fam-modal-close">&times;</span>
                <h2><?php _e('Add New Archive', 'file-archive-manager'); ?></h2>
                <form id="fam-add-archive-form">
                    <div class="form-field">
                        <label for="archive-name"><?php _e('Archive Name', 'file-archive-manager'); ?></label>
                        <input type="text" id="archive-name" name="name" required>
                    </div>
                    <div class="form-field">
                        <label for="archive-description"><?php _e('Description', 'file-archive-manager'); ?></label>
                        <textarea id="archive-description" name="description"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Create Archive', 'file-archive-manager'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Folder Modal -->
        <div id="fam-add-folder-modal" class="fam-modal">
            <div class="fam-modal-content">
                <span class="fam-modal-close">&times;</span>
                <h2><?php _e('Add New Folder', 'file-archive-manager'); ?></h2>
                <form id="fam-add-folder-form">
                    <input type="hidden" id="folder-archive-id" name="archive_id">
                    <input type="hidden" id="folder-parent-id" name="parent_id">
                    <div class="form-field">
                        <label for="folder-name"><?php _e('Folder Name', 'file-archive-manager'); ?></label>
                        <input type="text" id="folder-name" name="name" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Create Folder', 'file-archive-manager'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Upload File Modal -->
        <div id="fam-upload-file-modal" class="fam-modal">
            <div class="fam-modal-content">
                <span class="fam-modal-close">&times;</span>
                <h2><?php _e('Upload File', 'file-archive-manager'); ?></h2>
                <form id="fam-upload-file-form">
                    <input type="hidden" id="file-folder-id" name="folder_id">
                    <div class="form-field">
                        <label for="file-upload"><?php _e('Select File', 'file-archive-manager'); ?></label>
                        <input type="file" id="file-upload" name="file">
                    </div>
                    <div class="form-field">
                        <label for="file-external-url"><?php _e('Or External URL', 'file-archive-manager'); ?></label>
                        <input type="url" id="file-external-url" name="external_url" placeholder="https://">
                    </div>
                    <div class="form-field">
                        <label for="file-external-name"><?php _e('File Name (for external URL)', 'file-archive-manager'); ?></label>
                        <input type="text" id="file-external-name" name="external_name" placeholder="example.pdf">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Upload File', 'file-archive-manager'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Archive Modal -->
        <div id="fam-edit-archive-modal" class="fam-modal">
            <div class="fam-modal-content">
                <span class="fam-modal-close">&times;</span>
                <h2><?php _e('Edit Archive', 'file-archive-manager'); ?></h2>
                <form id="fam-edit-archive-form">
                    <input type="hidden" id="edit-archive-id" name="id">
                    <div class="form-field">
                        <label for="edit-archive-name"><?php _e('Archive Name', 'file-archive-manager'); ?></label>
                        <input type="text" id="edit-archive-name" name="name" required>
                    </div>
                    <div class="form-field">
                        <label for="edit-archive-description"><?php _e('Description', 'file-archive-manager'); ?></label>
                        <textarea id="edit-archive-description" name="description"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary"><?php _e('Save Changes', 'file-archive-manager'); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Folder Modal -->
        <div id="fam-edit-folder-modal" class="fam-modal" style="display:none;">
            <div class="fam-modal-content">
                <span class="fam-modal-close">&times;</span>
                <h2>ویرایش پوشه</h2>
                <form id="fam-edit-folder-form">
                    <input type="hidden" name="folder_id" id="edit-folder-id">
                    <label for="edit-folder-name">نام جدید پوشه</label>
                    <input type="text" name="name" id="edit-folder-name" required>
                    <button type="submit" class="button button-primary">ذخیره</button>
                </form>
            </div>
        </div>

        <!-- Edit File Modal -->
        <div id="fam-edit-file-modal" class="fam-modal" style="display:none;">
            <div class="fam-modal-content">
                <span class="fam-modal-close">&times;</span>
                <h2>ویرایش فایل</h2>
                <form id="fam-edit-file-form">
                    <input type="hidden" name="file_id" id="edit-file-id">
                    <div class="form-field">
                        <label for="edit-file-name">نام فایل</label>
                        <input type="text" name="name" id="edit-file-name" required>
                    </div>
                    <div class="form-field">
                        <label for="edit-file-url">لینک فایل</label>
                        <input type="url" name="url" id="edit-file-url">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button button-primary">ذخیره تغییرات</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    public function ajax_create_archive() {
        check_ajax_referer('fam-admin-nonce', 'nonce');

        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);

        $archive = new FAM_Archive();
        $result = $archive->create($name, $description);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'id' => $result,
            'name' => $name,
            'message' => __('Archive created successfully.', 'file-archive-manager')
        ));
    }

    public function ajax_create_folder() {
        check_ajax_referer('fam-admin-nonce', 'nonce');
        $archive_id = intval($_POST['archive_id']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $name = sanitize_text_field($_POST['name']);
        $folder = new FAM_Folder();
        $result = $folder->create($archive_id, $name, $parent_id);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success(array(
            'id' => $result,
            'name' => $name,
            'message' => __('Folder created successfully.', 'file-archive-manager')
        ));
    }

    public function ajax_upload_file() {
        check_ajax_referer('fam-admin-nonce', 'nonce');

        $folder_id = intval($_POST['folder_id']);
        $external_url = !empty($_POST['external_url']) ? esc_url_raw($_POST['external_url']) : null;
        $external_name = !empty($_POST['external_name']) ? sanitize_text_field($_POST['external_name']) : null;

        $file = new FAM_File();
        
        if ($external_url) {
            $file_name = $external_name ? $external_name : basename($external_url);
            $file_data = array(
                'name' => $file_name,
                'type' => wp_check_filetype($file_name)['type']
            );
            $result = $file->create($folder_id, $file_data, $external_url);
        } else {
            if (empty($_FILES['file'])) {
                wp_send_json_error(__('No file uploaded.', 'file-archive-manager'));
            }

            $result = $file->create($folder_id, $_FILES['file']);
        }

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'id' => $result,
            'message' => __('File uploaded successfully.', 'file-archive-manager')
        ));
    }

    public function ajax_delete_item() {
        check_ajax_referer('fam-admin-nonce', 'nonce');

        $type = sanitize_text_field($_POST['type']);
        $id = intval($_POST['id']);

        if ($type === 'archive') {
            $archive = new FAM_Archive();
            $result = $archive->delete($id);
        } elseif ($type === 'folder') {
            $folder = new FAM_Folder();
            $result = $folder->delete($id);
        } elseif ($type === 'file') {
            $file = new FAM_File();
            $result = $file->delete($id);
        } else {
            wp_send_json_error(__('Invalid item type.', 'file-archive-manager'));
        }

        if ($result === false) {
            wp_send_json_error(__('Failed to delete item.', 'file-archive-manager'));
        }

        wp_send_json_success(array(
            'message' => __('Item deleted successfully.', 'file-archive-manager')
        ));
    }

    public function ajax_edit_archive() {
        check_ajax_referer('fam-admin-nonce', 'nonce');
        $id = intval($_POST['id']);
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $archive = new FAM_Archive();
        $result = $archive->update($id, array('name' => $name, 'description' => $description));
        if ($result === false) {
            wp_send_json_error(__('Failed to update archive.', 'file-archive-manager'));
        }
        wp_send_json_success(array('message' => __('Archive updated successfully.', 'file-archive-manager')));
    }

    public function ajax_load_archive() {
        check_ajax_referer('fam-admin-nonce', 'nonce');
        $archive_id = intval($_POST['archive_id']);
        $folder_id = isset($_POST['folder_id']) && $_POST['folder_id'] !== '' && $_POST['folder_id'] != '0' ? intval($_POST['folder_id']) : null;
        $folder = new FAM_Folder();
        $file = new FAM_File();
        $current_folder = $folder_id ? $folder->get($folder_id) : null;
        $folders = $folder->get_children($archive_id, $folder_id);
        $files = $folder_id ? $file->get_by_folder($folder_id) : [];
        // --- Build breadcrumb path ---
        $breadcrumb = array();
        $parent_id = $folder_id;
        while ($parent_id) {
            $f = $folder->get($parent_id);
            if ($f) {
                array_unshift($breadcrumb, array('id' => $f->id, 'name' => $f->name, 'parent_id' => $f->parent_id));
                $parent_id = $f->parent_id;
            } else {
                break;
            }
        }
        ob_start();
        ?>
        <div class="fam-browser">
            <div class="fam-browser-header">
                <div class="fam-breadcrumb">
                    <button class="fam-breadcrumb-up" data-parent-id="<?php echo $current_folder ? esc_attr($current_folder->parent_id) : '0'; ?>" title="Up a level"><i class="fas fa-level-up-alt"></i></button>
                    <a href="#" class="fam-breadcrumb-admin" data-archive-id="<?php echo esc_attr($archive_id); ?>" data-folder-id="0"><?php _e('Root', 'file-archive-manager'); ?></a>
                    <?php foreach ($breadcrumb as $crumb) : ?>
                        / <a href="#" class="fam-breadcrumb-admin" data-archive-id="<?php echo esc_attr($archive_id); ?>" data-folder-id="<?php echo esc_attr($crumb['id']); ?>"><?php echo esc_html($crumb['name']); ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="fam-browser-actions">
                    <button type="button" class="button" id="fam-add-folder" data-archive-id="<?php echo esc_attr($archive_id); ?>" data-parent-id="<?php echo $folder_id ? esc_attr($folder_id) : ''; ?>">
                        <i class="fas fa-folder-plus"></i> <?php _e('Add Folder', 'file-archive-manager'); ?>
                    </button>
                    <button type="button" class="button" id="fam-upload-file" data-folder-id="<?php echo $folder_id ? esc_attr($folder_id) : ''; ?>">
                        <i class="fas fa-upload"></i> <?php _e('Upload File', 'file-archive-manager'); ?>
                    </button>
                </div>
            </div>
            <div class="fam-browser-content">
                <div class="fam-items">
                    <?php foreach ($folders as $f) : ?>
                        <div class="fam-item fam-folder-item" data-folder-id="<?php echo esc_attr($f->id); ?>">
                            <div class="fam-item-icon"><i class="fas fa-folder"></i></div>
                            <div class="fam-item-name"><?php echo esc_html($f->name); ?></div>
                            <div class="fam-item-actions">
                                <button type="button" class="button button-small fam-edit-folder" data-folder-id="<?php echo esc_attr($f->id); ?>" data-folder-name="<?php echo esc_attr($f->name); ?>" data-folder-desc="<?php echo esc_attr($f->description); ?>"><i class="fas fa-edit"></i></button>
                                <button type="button" class="button button-small delete-folder" data-folder-id="<?php echo esc_attr($f->id); ?>"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ($files as $file_item) : ?>
                        <div class="fam-item fam-file-item">
                            <div class="fam-item-icon"><i class="fas <?php echo esc_attr(fam_get_file_icon($file_item->name)); ?>"></i></div>
                            <div class="fam-item-name"><?php echo esc_html($file_item->name); ?></div>
                            <div class="fam-item-actions">
                                <button type="button" class="button button-small fam-edit-file" data-file-id="<?php echo esc_attr($file_item->id); ?>" data-file-name="<?php echo esc_attr($file_item->name); ?>" data-file-url="<?php echo esc_attr($file_item->external_url); ?>"><i class="fas fa-edit"></i></button>
                                <button type="button" class="button button-small delete-file" data-file-id="<?php echo esc_attr($file_item->id); ?>"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success($html);
    }

    public function ajax_edit_folder() {
        check_ajax_referer('fam-admin-nonce', 'nonce');
        $folder_id = intval($_POST['folder_id']);
        $name = sanitize_text_field($_POST['name']);
        require_once FAM_PLUGIN_DIR . 'includes/class-fam-folder.php';
        $folder = new FAM_Folder();
        $result = $folder->update_folder_name($folder_id, $name);
        if ($result) {
            wp_send_json_success();
        }
        // No error response - just return success
        wp_send_json_success();
    }

    public function ajax_edit_file() {
        check_ajax_referer('fam-admin-nonce', 'nonce');
        $file_id  = absint($_POST['file_id']);
        $new_name = sanitize_text_field($_POST['name']);
        $new_url  = esc_url_raw($_POST['url']);
        require_once FAM_PLUGIN_DIR . 'includes/class-fam-file.php';
        $file = new FAM_File();
        $updated = $file->update_file($file_id, $new_name, $new_url);
        if ($updated) {
            wp_send_json_success();
        }
        // wp_send_json_error();
        // wp_die();
    }
} 