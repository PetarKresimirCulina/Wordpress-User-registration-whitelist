<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @wordpress-plugin
 * Plugin Name:       HDZ Whitelista za registraciju članova
 * Description:       Dozvoljava registraciju samo sa emailovima članova.
 * Version:           1.0.0
 * Author:            Petar-Krešimir Čulina
 */




add_filter( 'registration_errors', 'wpse8170_registration_errors', 10, 3 );
function wpse8170_registration_errors( $errors, $sanitized_user_login, $user_email ) {
	global $wpdb;
	$table_name = $wpdb->prefix . "user_registration_email_whitelist";
	$wlemail = $wpdb->get_var("SELECT * FROM $table_name where email= '$user_email'");
	if($wlemail == null) {
		$errors->add( 'user_login_error', __('<strong>Greška: </strong>E-mail adresa nije evidentirana u bazi članstva. Molimo kontaktirajte tajnika na tajnistvo@hdz-samobor.com.' ) );
	}
	return $errors;
}


add_action( 'user_register', 'add_display_name', 10, 1 );
function add_display_name ( $user_id) {

    // get the user data

    $user_info = get_userdata( $user_id );

    // pick our default display name
	global $wpdb;
	$table_name = $wpdb->prefix . "user_registration_email_whitelist";
	$user_email = $user_info->user_email;
	
    $firstName = $wpdb->get_var("SELECT firstName FROM $table_name where email= '$user_email'");
	$lastName = $wpdb->get_var("SELECT lastName FROM $table_name where email= '$user_email'");
	

    // update the display name

    wp_update_user( array ('ID' => $user_id, 'first_name' =>  $firstName, 'last_name' =>  $lastName));
}

add_action( 'admin_init', 'restrict_admin_with_redirect', 1 );
function restrict_admin_with_redirect() {

    if ( ! current_user_can( 'manage_options' ) && ( ! wp_doing_ajax() ) ) {
        wp_safe_redirect( 'https://hdz-samobor.com' ); // Replace this with the URL to redirect to.
		show_admin_bar(false);
        exit;
    }
}

add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
	if ( ! current_user_can( 'manage_options' ) && ( ! wp_doing_ajax() ) ) {
	  show_admin_bar(false);
	}
}

function installer(){
    global $wpdb;
	$table_name = $wpdb->prefix . "user_registration_email_whitelist";
	$db_version = '1.0.0';
	$charset_collate = $wpdb->get_charset_collate();

	if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name ) {

		$sql = "CREATE TABLE $table_name (
				ID mediumint(9) NOT NULL AUTO_INCREMENT,
				`email` text NOT NULL,
				`firstName` text NOT NULL,
				`lastName` text NOT NULL,
				PRIMARY KEY  (ID)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option('urw_db_version', $db_version);
	}
}

function uninstaller(){
    global $wpdb;
	$table_name = $wpdb->prefix . "user_registration_email_whitelist";
	$db_version = '1.0.0';
	
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}");
    delete_option("urw_db_version");
}

register_activation_hook(__file__, 'installer');
register_uninstall_hook(__file__, 'uninstaller');

