<?php

class TechSpace_Admin {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        add_action('wp_ajax_techspace_generate_api_key', array($this, 'ajax_generate_api_key'));
        add_action('wp_ajax_techspace_update_allowed_tables', array($this, 'ajax_update_allowed_tables'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'TechSpace API',
            'TechSpace API',
            'manage_options',
            'techspace-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-rest-api',
            30
        );

        add_submenu_page(
            'techspace-dashboard',
            'API Settings',
            'API Settings',
            'manage_options',
            'techspace-api-settings',
            array($this, 'render_api_settings')
        );
    }

    public function render_dashboard() {
        $analytics = $this->db->get_analytics();
        $top_users = $this->db->get_top_users();
        $current_user_id = get_current_user_id();
        $api_key = $this->db->get_user_api_key($current_user_id);
        $allowed_tables = $api_key ? $this->db->get_allowed_tables_for_key($api_key) : array();
        ?>
<div class="wrap">
    <h1>TechSpace API Dashboard</h1>
    <div class="techspace-dashboard-wrapper">
        <div class="techspace-chart">
            <h2>API Usage (Last 30 Days)</h2>
            <canvas id="apiUsageChart"></canvas>
        </div>
        <div class="techspace-top-users">
            <h2>Top API Users</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Request Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_users as $user) : ?>
                    <tr>
                        <td><?php echo esc_html($user->user_login); ?></td>
                        <td><?php echo esc_html($user->request_count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h2>API Usage Instructions</h2>
    <?php if ($api_key) : ?>
    <p>Your API Key: <strong><?php echo esc_html($api_key); ?></strong></p>
    <p>Allowed Tables:</p>
    <ul>
        <?php foreach ($allowed_tables as $table) : ?>
        <li>
            <?php echo esc_html($table); ?>
            <br>
            Endpoint: <code><?php echo esc_url(rest_url("techspace/v1/data/{$table}")); ?></code>
            <br>
            cURL example:
            <pre>curl -X GET '<?php echo esc_url(rest_url("techspace/v1/data/{$table}")); ?>' -H 'X-API-Key: <?php echo esc_html($api_key); ?>'</pre>
            PHP example:
            <pre>
$url = '<?php echo esc_url(rest_url("techspace/v1/data/{$table}")); ?>';
$args = array(
    'headers' => array(
        'X-API-Key' => '<?php echo esc_html($api_key); ?>'
    )
);
$response = wp_remote_get($url, $args);
if (!is_wp_error($response)) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    // Process $data here
}
                            </pre>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else : ?>
    <p>You haven't generated an API key yet. Please go to the API Settings page to generate one.</p>
    <?php endif; ?>
</div>
<script>
// Use Chart.js to render the API usage chart
var ctx = document.getElementById('apiUsageChart').getContext('2d');
var chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(wp_list_pluck($analytics, 'date')); ?>,
        datasets: [{
            label: 'API Requests',
            data: <?php echo json_encode(wp_list_pluck($analytics, 'requests')); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
<?php
    }

    public function render_api_settings() {
        $current_user_id = get_current_user_id();
        $api_key = $this->db->get_user_api_key($current_user_id);
        $all_tables = $this->db->get_all_tables();
        $allowed_tables = $api_key ? $this->db->get_allowed_tables_for_key($api_key) : array();
        ?>
<div class="wrap">
    <h1>TechSpace API Settings</h1>
    <form id="techspace-api-settings-form">
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Your API Key</th>
                <td>
                    <input type="text" id="techspace_api_key" value="<?php echo esc_attr($api_key); ?>" readonly />
                    <button type="button" id="generate_api_key" class="button button-secondary">
                        <?php echo $api_key ? 'Regenerate API Key' : 'Generate API Key'; ?>
                    </button>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Allowed Tables</th>
                <td>
                    <?php foreach ($all_tables as $table) : ?>
                    <label>
                        <input type="checkbox" name="allowed_tables[]" value="<?php echo esc_attr($table); ?>"
                            <?php checked(in_array($table, $allowed_tables)); ?>>
                        <?php echo esc_html($table); ?>
                    </label><br>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" id="update_allowed_tables" class="button button-primary">Update Allowed
                Tables</button>
        </p>
    </form>
</div>
<script>
jQuery(document).ready(function($) {
    $('#generate_api_key').on('click', function() {
        var allowedTables = $('input[name="allowed_tables[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'techspace_generate_api_key',
                nonce: '<?php echo wp_create_nonce('techspace_generate_api_key'); ?>',
                allowed_tables: allowedTables
            },
            success: function(response) {
                if (response.success) {
                    $('#techspace_api_key').val(response.data.api_key);
                    TechSpaceToast.success(
                        'New API key generated successfully. Please save this key as it won\'t be shown again.'
                        );
                } else {
                    TechSpaceToast.error('Error generating API key: ' + response.data
                        .message);
                }
            }
        });
    });

    $('#techspace-api-settings-form').on('submit', function(e) {
        e.preventDefault();
        var allowedTables = $('input[name="allowed_tables[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'techspace_update_allowed_tables',
                nonce: '<?php echo wp_create_nonce('techspace_update_allowed_tables'); ?>',
                api_key: $('#techspace_api_key').val(),
                allowed_tables: allowedTables
            },
            success: function(response) {
                if (response.success) {
                    TechSpaceToast.success('Allowed tables updated successfully.');
                } else {
                    TechSpaceToast.error('Error updating allowed tables: ' + response.data
                        .message);
                }
            }
        });
    });
});
</script>
<?php
    }

    public function ajax_generate_api_key() {
        check_ajax_referer('techspace_generate_api_key', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        }

        $allowed_tables = isset($_POST['allowed_tables']) ? $_POST['allowed_tables'] : array();
        $user_id = get_current_user_id();
        $api_key = $this->db->generate_api_key($user_id, $allowed_tables);

        if ($api_key) {
            wp_send_json_success(array('api_key' => $api_key));
        } else {
            wp_send_json_error(array('message' => 'Failed to generate API key.'));
        }
    }

    public function ajax_update_allowed_tables() {
        check_ajax_referer('techspace_update_allowed_tables', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $allowed_tables = isset($_POST['allowed_tables']) ? $_POST['allowed_tables'] : array();

        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Invalid API key.'));
        }

        $result = $this->db->update_allowed_tables($api_key, $allowed_tables);

        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => 'Failed to update allowed tables.'));
        }
    }
}