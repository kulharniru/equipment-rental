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
	add_option( 'er_cal_reserved_text', 'Broneeritud' );
	add_option( 'er_cal_reserved_col', '#FFFF00' );
	add_option( 'er_cal_free_text', 'Vaba' );
	add_option( 'er_cal_free_col', '#00FF00' );
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
	// Handle the updating if the form is posted
	if ( isset( $_POST[ "busy_text" ] ) ) {
		// Update the fields to the submitted ones
		update_option( 'er_cal_busy_text', $_POST[ "busy_text" ] );
		update_option( 'er_cal_busy_col', $_POST[ "busy_col" ] );
		update_option( 'er_cal_reserved_text', $_POST[ "res_text" ] );
		update_option( 'er_cal_reserved_col', $_POST[ "res_col" ] );
		update_option( 'er_cal_free_text', $_POST[ "free_text" ] );
		update_option( 'er_cal_free_col', $_POST[ "free_col" ] );
	}
	?>
	<h2>Here you can tune generic options for the plugin</h2>
	<br/>
	<form name="settings_form" method="post" action="">
	<table cellpadding=10>
	<tbody>
		<tr><td align="right">Busy text:</td><td><input type="text" name="busy_text" value="<?php echo get_option('er_cal_busy_text');?>" size=20></td></tr>
		<tr><td align="right">Busy color:</td><td bgcolor="<?php echo get_option('er_cal_busy_col');?>"><input type="text" name="busy_col" value="<?php echo get_option('er_cal_busy_col');?>" size=20></td></tr>
		<tr><td align="right">Reserved text:</td><td><input type="text" name="res_text" value="<?php echo get_option('er_cal_reserved_text');?>" size=20></td></tr>
		<tr><td align="right">Reserved color:</td><td bgcolor="<?php echo get_option('er_cal_reserved_col');?>"><input type="text" name="res_col" value="<?php echo get_option('er_cal_reserved_col');?>" size=20></td></tr>
		<tr><td align="right">Free text:</td><td><input type="text" name="free_text" value="<?php echo get_option('er_cal_free_text');?>" size=20></td></tr>
		<tr><td align="right">Free color:</td><td bgcolor="<?php echo get_option('er_cal_free_col');?>"><input type="text" name="free_col" value="<?php echo get_option('er_cal_free_col');?>" size=20></td></tr>
		<tr><td colspan=2 align="center"><input type=submit name="Update" class="button-primary"></td></tr>
	</tbody>
	</table>
	</form>
	<?php
}

function er_menu_equipment() {
	global $wpdb;
	
	// Define basics
	$but_label = "Add new";
	$eq_name = "";
	$eq_id = "";

	if ( isset( $_POST[ 'eq_id' ] ) && $_POST[ 'action' ] == 'Delete' ) {
		// Means we're deleting this entry
		$wpdb->delete( $wpdb->prefix . "equipment_assets", array( "id" => $_POST[ 'eq_id' ] ) );
	}

	if ( isset( $_POST[ 'eq_id' ] ) && $_POST[ 'action' ] == 'Edit' ) {
		// Means we got a request to show it for updating
		$eq_id = $_POST[ 'eq_id' ];
		$eq_name = $wpdb->get_var( 'SELECT name FROM ' . $wpdb->prefix . 'equipment_assets WHERE id = ' . $eq_id . ';' );
		$but_label = "Update";
	}

	if ( isset( $_POST[ 'eq_id' ] ) && $_POST[ 'action' ] == 'Update' ) {
		// Means updating is actually happening
		$wpdb->update( $wpdb->prefix . 'equipment_assets', array( 'name' => $_POST[ 'eq_name' ] ), array( 'id' => $_POST[ 'eq_id' ] ) );
	}

	if ( isset( $_POST[ 'eq_name' ] ) && $_POST[ 'action' ] == "Add new" ) {
		// New addition
		$wpdb->insert( $wpdb->prefix . 'equipment_assets', array( 'name' => $_POST[ 'eq_name' ] ) );
	}

	?>
	<h2>Rental equipment management</h2>
	<form name="equipment_form" method="post" action="">
	Equipment name: 
	<input type=hidden name="eq_id" value="<?php echo $eq_id;?>">
	<input type=text name="eq_name" value="<?php echo $eq_name;?>" size=40>
	<input type="submit" name="action" class="button-primary" value="<?php echo $but_label;?>"></form>
	<table cellpadding=5 style="border:1px">
	<thead><th>ID</th><th>Asset name</th><th>Actions</th></thead>
	<tbody>
	<?php
	$neq = $wpdb->get_var( 'SELECT COUNT(id) FROM ' . $wpdb->prefix . 'equipment_assets' );
	if ($neq) {
		$res = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'equipment_assets' );
		foreach ($res as $r) {
			echo "<tr>
				<form method=post action=''>
				<td><input type=hidden name=eq_id value=" . $r->id . ">" . $r->id . "</td>
				<td>" . $r->name . "</td>
				<td><input type=submit name=action value=Edit class=\"button-primary\">
				<input type=submit name=action value=Delete class=\"button-primary\"></td>
			      </tr>";
		}
	}
	?>
	</tbody>
	</table>
	<?php
}

function er_menu_clients() {
	global $wpdb;
	
	// Define basics
	$but_label = "Add new";
	$cl_data = "";
	$cl_id = "";

	if ( isset( $_POST[ 'cl_id' ] ) && $_POST[ 'action' ] == 'Delete' ) {
		// Means we're deleting this entry
		$wpdb->delete( $wpdb->prefix . "equipment_clients", array( "id" => $_POST[ 'cl_id' ] ) );
	}

	if ( isset( $_POST[ 'cl_id' ] ) && $_POST[ 'action' ] == 'Edit' ) {
		// Means we got a request to show it for updating
		$cl_id = $_POST[ 'cl_id' ];
		$cl_data = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'equipment_clients WHERE id = ' . $cl_id . ';' );
		$but_label = "Update";
	}

	if ( isset( $_POST[ 'cl_id' ] ) && $_POST[ 'action' ] == 'Update' ) {
		// Means updating is actually happening
		$wpdb->update( $wpdb->prefix . 'equipment_clients', 
			array( 
				'name' => $_POST[ 'cl_name' ],
				'email' => $_POST[ 'cl_email' ],
				'phone' => $_POST[ 'cl_phone' ]
			), 
			array( 'id' => $_POST[ 'cl_id' ] ) 
			);
	}

	if ( isset( $_POST[ 'cl_name' ] ) && $_POST[ 'action' ] == "Add new" ) {
		// New addition
		$wpdb->insert( $wpdb->prefix . 'equipment_clients', 
			array( 
				'name' => $_POST[ 'cl_name' ],
				'email' => $_POST[ 'cl_email' ],
				'phone' => $_POST[ 'cl_phone' ]
			) );
	}

	?>
	<h2>Client list management</h2>
	<form name="equipment_form" method="post" action="">
	<input type=hidden name="cl_id" value="<?php echo $cl_id;?>">
	<table>
	<tr><td>Client name:</td><td><input type=text name="cl_name" value="<?php echo $cl_data->name;?>" size=40></td></tr>
	<tr><td>Client e-mail:</td><td><input type=text name="cl_email" value="<?php echo $cl_data->email;?>" size=40></td></tr>
	<tr><td>Client phone:</td><td><input type=text name="cl_phone" value="<?php echo $cl_data->phone;?>" size=40></td></tr>
	<tr><td colspan=2 align=center><input type="submit" name="action" class="button-primary" value="<?php echo $but_label;?>"></td></tr>
	</table>
	</form>
	<table cellpadding=5 style="border:1px">
	<thead><th>ID</th><th>Client name</th><th>E-Mail</th><th>Phone</th><th>Actions</th></thead>
	<tbody>
	<?php
	$neq = $wpdb->get_var( 'SELECT COUNT(id) FROM ' . $wpdb->prefix . 'equipment_clients' );
	if ($neq) {
		$res = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'equipment_clients' );
		foreach ($res as $r) {
			echo "<tr>
				<form method=post action=''>
				<td><input type=hidden name=cl_id value=" . $r->id . ">" . $r->id . "</td>
				<td>" . $r->name . "</td>
				<td>" . $r->email . "</td>
				<td>" . $r->phone . "</td>
				<td><input type=submit name=action value=Edit class=\"button-primary\">
				<input type=submit name=action value=Delete class=\"button-primary\"></td>
			      </tr>";
		}
	}
	?>
	</tbody>
	</table>
	<?php
}

function er_menu_rentals() {
	echo "<h2>Rental management</h2>";
}

?>
