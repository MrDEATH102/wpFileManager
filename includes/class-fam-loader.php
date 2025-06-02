<?php

class FAM_Loader {
    protected $actions;
    protected $filters;

    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once FAM_PLUGIN_DIR . 'includes/class-fam-archive.php';
        require_once FAM_PLUGIN_DIR . 'includes/class-fam-folder.php';
        require_once FAM_PLUGIN_DIR . 'includes/class-fam-file.php';
        require_once FAM_PLUGIN_DIR . 'admin/class-fam-admin.php';
        require_once FAM_PLUGIN_DIR . 'public/class-fam-public.php';
        require_once FAM_PLUGIN_DIR . 'includes/class-fam-elementor.php';
    }

    private function define_admin_hooks() {
        $plugin_admin = new FAM_Admin();
        
        $this->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    }

    private function define_public_hooks() {
        $plugin_public = new FAM_Public();
        
        $this->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        return $hooks;
    }

    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
} 