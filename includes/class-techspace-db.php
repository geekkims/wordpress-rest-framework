<?php

class TechSpace_DB {
    private $api_key_table;

    public function __construct() {
        global $wpdb;
        $this->api_key_table = $wpdb->prefix . 'techspace_api_keys';
    }

    public function create_api_key_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->api_key_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            api_key varchar(64) NOT NULL,
            allowed_tables text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            last_used datetime DEFAULT NULL,
            request_count bigint(20) DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY api_key (api_key),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function generate_api_key($user_id, $allowed_tables) {
        $api_key = wp_generate_password(64, false);
        global $wpdb;

        $result = $wpdb->insert(
            $this->api_key_table,
            array(
                'user_id' => $user_id,
                'api_key' => $api_key,
                'allowed_tables' => json_encode($allowed_tables),
            ),
            array('%d', '%s', '%s')
        );

        return $result ? $api_key : false;
    }

    public function validate_api_key($api_key) {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $this->api_key_table WHERE api_key = %s",
            $api_key
        ));
        return $result > 0;
    }

    public function get_allowed_tables_for_key($api_key) {
        global $wpdb;
        $allowed_tables = $wpdb->get_var($wpdb->prepare(
            "SELECT allowed_tables FROM $this->api_key_table WHERE api_key = %s",
            $api_key
        ));
        return json_decode($allowed_tables, true);
    }

    public function get_all_tables() {
        global $wpdb;
        $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
        $wordpress_tables = array();
        foreach ($tables as $table) {
            if (strpos($table[0], $wpdb->prefix) === 0) {
                $wordpress_tables[] = substr($table[0], strlen($wpdb->prefix));
            }
        }
        return $wordpress_tables;
    }

    public function log_api_request($api_key, $route) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE $this->api_key_table 
            SET last_used = CURRENT_TIMESTAMP, request_count = request_count + 1 
            WHERE api_key = %s",
            $api_key
        ));
    }

    public function get_analytics($days = 30) {
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(last_used) as date, COUNT(*) as requests
            FROM $this->api_key_table
            WHERE last_used >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            GROUP BY DATE(last_used)
            ORDER BY date ASC",
            $days
        ));
        return $result;
    }

    public function get_top_users($limit = 10) {
        global $wpdb;
        $result = $wpdb->get_results($wpdb->prepare(
            "SELECT u.user_login, a.request_count
            FROM $this->api_key_table a
            JOIN {$wpdb->users} u ON a.user_id = u.ID
            ORDER BY a.request_count DESC
            LIMIT %d",
            $limit
        ));
        return $result;
    }

    public function get_user_api_key($user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT api_key FROM $this->api_key_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
    }

    public function delete_api_key($api_key) {
        global $wpdb;
        return $wpdb->delete(
            $this->api_key_table,
            array('api_key' => $api_key),
            array('%s')
        );
    }

    public function update_allowed_tables($api_key, $allowed_tables) {
        global $wpdb;
        return $wpdb->update(
            $this->api_key_table,
            array('allowed_tables' => json_encode($allowed_tables)),
            array('api_key' => $api_key),
            array('%s'),
            array('%s')
        );
    }
}