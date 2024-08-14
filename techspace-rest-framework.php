<?php
/**
 * Plugin Name: TechSpace REST Framework
 * Description: A flexible REST API framework for WordPress, allowing access to any database table with API key management and analytics.
 * Version: 1.0
 * Author: TechSpace Softwares
 * Author URI: https://techspace.co.ke
 * Text Domain: techspace-rest-framework
 * Domain Path: /languages
 */

// Ensure direct access to this file is not allowed
defined('ABSPATH') or die('No script kiddies please!');

// Define plugin constants
define('TECHSPACE_RF_VERSION', '1.0');
define('TECHSPACE_RF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TECHSPACE_RF_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once TECHSPACE_RF_PLUGIN_DIR . 'includes/class-techspace-api.php';
require_once TECHSPACE_RF_PLUGIN_DIR . 'includes/class-techspace-db.php';
require_once TECHSPACE_RF_PLUGIN_DIR . 'includes/class-techspace-admin.php';

class TechSpace_REST_Framework {

    private $api;
    private $db;
    private $admin;

    public function __construct() {
        // Initialize components
        $this->db = new TechSpace_DB();
        $this->api = new TechSpace_API($this->db);
        $this->admin = new TechSpace_Admin($this->db);

        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize REST API routes
        add_action('rest_api_init', array($this->api, 'register_routes'));

        // Add admin menu
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Add settings link on plugin page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    public function activate() {
        $this->db->create_api_key_table();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_techspace-dashboard' !== $hook && 'techspace-api_page_techspace-api-settings' !== $hook) {
            return;
        }

        wp_enqueue_style('techspace-admin-css', TECHSPACE_RF_PLUGIN_URL . 'admin/css/techspace-admin.css', array(), TECHSPACE_RF_VERSION);
        wp_enqueue_script('techspace-admin-js', TECHSPACE_RF_PLUGIN_URL . 'admin/js/techspace-admin.js', array('jquery'), TECHSPACE_RF_VERSION, true);
        wp_enqueue_script('techspace-toast-js', TECHSPACE_RF_PLUGIN_URL . 'assets/js/techspace-toast.js', array(), TECHSPACE_RF_VERSION, true);

        wp_localize_script('techspace-admin-js', 'techspaceRF', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('techspace_rf_nonce')
        ));
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=techspace-api-settings">' . __('Settings', 'techspace-rest-framework') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize the plugin
$techspace_rest_framework = new TechSpace_REST_Framework();