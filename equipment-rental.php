<?php
/**
 * Plugin Name: Equipment Rental
 * Plugin URI: http://teslarent.eu/wp-plugins/equipment-rental/
 * Description: The plugin helps manage a basic equipment rental service including calendar
 * Version: 0.1
 * Author: Mario Kadastik
 * Author URI: http://teslarent.eu/Mario
 * Network: false
 * License: GPL2
 */

/*  Copyright 2015  Mario Kadastik  (email : mario@teslarent.eu)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined('ABSPATH') or die('No direct access please');

global $er_db_version;
$er_db_version = '1.0';

// Function to create the initial database structure
function er_install() {
	global $wpdb;
	global $er_db_version;

	// Do some preliminary work
	$pre = $wpdb->prefix;
	$charset = $wpdb->get_charset_collate();
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php' );

	// Create equipment table
	$tab = $pre . 'equipment_assets';	
	$sql = "CREATE TABLE $tab (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name text NOT NULL,
		UNIQUE KEY id (id) 
		) $charset;";
	dbDelta( $sql );

	// Create clients table
	$tab = $pre . 'equipment_clients';
	$sql = "CREATE TABLE $tab (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		name text NOT NULL,
		email text,
		phone text,
		UNIQUE KEY id (id)
		) $charset;";
	dbDelta( $sql );

	// Create rentals table
	$tab = $pre . 'equipment_rentals';
	$sql = "CREATE TABLE $tab (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		asset mediumint(9) NOT NULL,
		client mediumint(9) NOT NULL,
		start datetime NOT NULL,
		end datetime NOT NULL,
		confirmed tinyint(1) NOT NULL DEFAULT 0,
		UNIQUE KEY id (id)
		) $charset;";
	dbDelta( $sql );
	add_option( 'er_db_version', $er_db_version );

	// Default options for colors in the calendar as well as the names of those
	add_option( 'er_cal_busy_text', 'H&otilde;ivatud' );
	add_option( 'er_cal_busy_col', '#FF0000' );
	add_option( 'er_cal_busy_text', 'Broneeritud' );
	add_option( 'er_cal_busy_col', '#FFFF00' );
	add_option( 'er_cal_busy_text', 'Vaba' );
	add_option( 'er_cal_busy_col', '#00FF00' );
}	

// Register hook for initial database creation
register_activation_hook( __FILE__, 'er_install' );

// Let's create the options management menu and page
add_action( 'admin_menu', 'er_plugin_menu' );

function er_plugin_menu() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	add_menu_page( 'Equipment Rental', 'Equipment rental', 'manage_options', 'ER_main', 'er_menu_main' );
	add_submenu_page( 'ER_main', 'Equipment list', 'Equipment list', 'manage_options', 'ER_equipment', 'er_menu_equipment' );
	add_submenu_page( 'ER_main', 'Client list', 'Client list', 'manage_options', 'ER_clients', 'er_menu_clients' );
	add_submenu_page( 'ER_main', 'Rentals', 'Rentals', 'manage_options', 'ER_rentals', 'er_menu_rentals' );
}

function er_menu_main() {
	?>
	<h2>Here you can tune generic options for the plugin</h2>
	<?php
}

function er_menu_equipment() {
	echo "<h2>Rental equipment management</h2>";
}

function er_menu_clients() {
	echo "<h2>Client list management</h2>";
}

function er_menu_rentals() {
	echo "<h2>Rental management</h2>";
}

?>