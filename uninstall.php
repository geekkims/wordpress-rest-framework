<?php
// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete the custom table
global $wpdb;
$table_name = $wpdb->prefix . 'techspace_api_keys';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Delete any options the plugin might have stored
delete_option( 'techspace_rest_framework_version' );
delete_option( 'techspace_rest_framework_settings' );

// Clear any cached data that may have been cached
wp_cache_flush();