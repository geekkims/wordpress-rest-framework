<?php

class TechSpace_API {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function register_routes() {
        register_rest_route('techspace/v1', '/data/(?P<table>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_table_data'),
            'permission_callback' => array($this, 'check_api_key'),
            'args' => array(
                'table' => array(
                    'validate_callback' => array($this, 'validate_table_name')
                ),
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                ),
                'orderby' => array(
                    'default' => 'id',
                ),
                'order' => array(
                    'default' => 'DESC',
                    'enum' => array('ASC', 'DESC'),
                ),
            ),
        ));
    }

    public function check_api_key($request) {
        $api_key = $request->get_header('X-API-Key');
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'No API key provided', array('status' => 401));
        }

        $is_valid = $this->db->validate_api_key($api_key);
        if (!$is_valid) {
            return new WP_Error('invalid_api_key', 'Invalid API key', array('status' => 401));
        }

        $table = $request->get_param('table');
        $allowed_tables = $this->db->get_allowed_tables_for_key($api_key);
        if (!in_array($table, $allowed_tables)) {
            return new WP_Error('table_not_allowed', 'Access to this table is not allowed for this API key', array('status' => 403));
        }

        $this->db->log_api_request($api_key, $request->get_route());
        return true;
    }

    public function validate_table_name($param, $request, $key) {
        global $wpdb;
        $tables = $wpdb->get_col('SHOW TABLES');
        return in_array($wpdb->prefix . $param, $tables);
    }

    public function get_table_data($request) {
        global $wpdb;
        $table = $wpdb->prefix . $request->get_param('table');
        $page = $request->get_param('page');
        $per_page = $request->get_param('per_page');
        $orderby = $request->get_param('orderby');
        $order = $request->get_param('order');

        $offset = ($page - 1) * $per_page;

        // Ensure the orderby column exists in the table
        $table_columns = $wpdb->get_col("DESC {$table}");
        if (!in_array($orderby, $table_columns)) {
            $orderby = 'id'; // Default to 'id' if the specified column doesn't exist
        }

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $total_pages = ceil($total_items / $per_page);

        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY %s %s LIMIT %d OFFSET %d",
                $orderby,
                $order,
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $response = new WP_REST_Response($data, 200);
        $response->header('X-WP-Total', $total_items);
        $response->header('X-WP-TotalPages', $total_pages);

        return $response;
    }
}