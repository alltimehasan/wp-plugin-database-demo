<?php
/*
 * Plugin Name:       Database Demo
 * Plugin URI:        https://hasan4web.com/plugins/data-table/
 * Description:       Working with WP list table
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Hasan Ali
 * Author URI:        https://hasan4web.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       database-demo
 * Domain Path:       /languages/
 */

/**
 * Load Text Domain
 */
function dbdemo_load_textdomain() {
    load_plugin_textdomain( 'database-demo', false, dirname(__FILE__) . '/languages' );
}
add_action( 'plugins_loaded', 'dbdemo_load_textdomain' );

/**
 * Activation Hook
 */
define('DBDEMO_DB_VERSION', '1.2');

require_once('class.dbdemousers.php');

function dbdemo_activation_hook() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'persons';
    $sql = "CREATE TABLE {$table_name} (
        id INT NOT NULL AUTO_INCREMENT,
        name VARCHAR(250),
        email VARCHAR(250),
        PRIMARY KEY (id)
    )";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('dbdemo_db_version', DBDEMO_DB_VERSION);

    if(get_option('dbdemo_db_version') != DBDEMO_DB_VERSION) {
        $sql = "CREATE TABLE {$table_name} (
            id INT NOT NULL AUTO_INCREMENT,
            name VARCHAR(250),
            email VARCHAR(250),
            age INT,
            PRIMARY KEY (id)
        )";
        dbDelta($sql);
        update_option('dbdemo_db_version', DBDEMO_DB_VERSION);
    }
}
register_activation_hook( __FILE__, 'dbdemo_activation_hook' );

/**
 * Drop DB column
 */
function dbdemo_drop_column() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'persons';
	if ( get_option( "dbdemo_db_version" ) != DBDEMO_DB_VERSION ) {
		$query = "ALTER TABLE {$table_name} DROP COLUMN age";
		$wpdb->query( $query );
	}
	update_option( "dbdemo_db_version", DBDEMO_DB_VERSION );
}
add_action( "plugins_loaded", "dbdemo_drop_column" );

add_action('admin_enqueue_scripts', function($hook){
    if('toplevel_page_dbdemo' == $hook) {
        wp_enqueue_style('dbdemo-style', plugin_dir_url(__FILE__) . 'assets/css/form.css');
    }
});

/**
 * Load data when plugin activation
 */
function dbdemo_load_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'persons';
    $wpdb->insert($table_name, [
        'name' => 'John Doe',
        'email' => 'john@dow.com'
    ]);
    $wpdb->insert($table_name, [
        'name' => 'Jane Doe',
        'email' => 'jane@dow.com'
    ]);
}
register_activation_hook( __FILE__, 'dbdemo_load_data' );

/**
 * Deactivation Hook
 */
function dbdemo_deactivation_hook() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'persons';
    $query = "TRUNCATE TABLE {$table_name}";
    $wpdb->query($query);
}
register_deactivation_hook( __FILE__, 'dbdemo_deactivation_hook' );

/**
 * Create Admin Menu Page
 */
add_action('admin_menu', function(){
    add_menu_page('DB Demo', 'DB Demo', 'manage_options', 'dbdemo', 'dbdemo_admin_page');
});

function dbdemo_admin_page() {
    global $wpdb;
    if(isset($_GET['pid'])) {
        if(!isset($_GET['n']) || !wp_verify_nonce($_GET['n'], 'dbdemo_edit')) {
            wp_die('Sorry! You are not authorizes to do this.');
        }

        if(isset($_GET['action']) && $_GET['action'] == 'delete') {
            $wpdb->delete("{$wpdb->prefix}persons", ['id' => sanitize_key($_GET['pid'])]);
            $_GET['pid'] = null;
        }
    }
    echo "<h2>Kleanup</h2>";
    $id = $_GET['pid'] ?? 0;
    $id = sanitize_key($id);
    if($id) {
        $result = $wpdb->get_row("select * from {$wpdb->prefix}persons where id='{$id}'");
        if($result) {
            echo "Name: {$result->name}<br>";
            echo "Email: {$result->email}<br>";
        }
    }
    ?>
    <div class="form_box">
        <div class="form_box_header">
            <?php _e( 'Data Form', 'database-demo' ) ?>
        </div>
        <div class="form_box_content">
            <form action="<?php echo admin_url('admin-post.php'); ?>" method="POST">
                <?php wp_nonce_field('dbdemo', 'nonce') ?>
                <input type="hidden" name="action" value="dbdemo_add_record">
                <label for=""><strong>Name:</strong></label><br>
                <input type="text" name="name" value="<?php if($id) echo $result->name; ?>"><br>
                <label for=""><strong>Email: </strong></label><br>
                <input type="email" name="email" value="<?php if($id) echo $result->email; ?>"><br>
                <?php
                if($id) {
                    echo '<input type="hidden" name="id" value="'. $id .'">';
                    submit_button('Update Record');
                } else {
                    submit_button('Add Record');
                }
                ?>
            </form>
        </div>
        
    </div>

    <div class="form_box" style="margin-top: 20px;">
        <div class="form_box_header">
            <?php _e( 'Users', 'database-demo' ) ?>
        </div>
        <div class="form_box_content">
            <?php
                global $wpdb;
                $dbdemo_users = $wpdb->get_results("SELECT id, name, email FROM {$wpdb->prefix}persons ORDER BY id DESC", ARRAY_A);
                $dbtu = new DBTableUsers($dbdemo_users);
                $dbtu->prepare_items();
                $dbtu->display();
            ?>
        </div>
        
    </div>
    <?php

    if(isset($_POST['submit'])) {
        $nonce =sanitize_text_field($_POST['nonce']);
        if(wp_verify_nonce($nonce, 'dbdemo')) {
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_text_field($_POST['email']);
    
            $wpdb->insert("{$wpdb->prefix}persons", ['name' => $name, 'email' => $email]);
            echo "Data inserted!";
        } else {
            echo "You are not allowed.";
        }
    }

}

add_action('admin_post_dbdemo_add_record', function(){
    global $wpdb;
    $nonce =sanitize_text_field($_POST['nonce']);
    if(wp_verify_nonce($nonce, 'dbdemo')) {
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_text_field($_POST['email']);
        $id = sanitize_text_field($_POST['id']);
        
        if($id) {
            $wpdb->update("{$wpdb->prefix}persons", ['name' => $name, 'email' => $email], ['id' => $id]);
            $nonce = wp_create_nonce('dbdemo_edit');
            wp_redirect(admin_url('admin.php?page=dbdemo&pid='. $id . "&n={$nonce}"));
        } else {
            $wpdb->insert("{$wpdb->prefix}persons", ['name' => $name, 'email' => $email]);
            $new_id = $wpdb->insert_id;
            $nonce = wp_create_nonce('dbdemo_edit');
            wp_redirect(admin_url('admin.php?page=dbdemo&pid=' . $new_id . "&n={$nonce}"));
        }
        echo "Data inserted!";
    } else {
        echo "You are not allowed.";
    }
});