<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Ale_Prom_Import
 *
 * @wordpress-plugin
 * Plugin Name:       Ale Prom Import
 * Plugin URI:        
 * Description:       Prom import settings plugin.
 * Version:           1.0.0
 * Author:            Alex
 * Author URI:        
 * License:           GPL-2.0+
 * License URI:       
 * Text Domain:       ale-prom-import
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if(!ABSPATH) { 
	die; 
}



define('PROM_IMPORT_SCRIPT_PATH', ABSPATH . 'prom-import');
define('PROM_IMPORT_SCRIPT_CONFIG_PATH', PROM_IMPORT_SCRIPT_PATH . '/categories_config.json');

define ('ALE_PROM_IMPORT_VERSION', '1.0.0' );
define ('ALE_PROM_IMPORT_URL', plugin_dir_url( __FILE__ ));
define ('ALE_PROM_IMPORT_PATH', plugin_dir_path( __FILE__ ));
define("ALE_CI", 'ale-prom-import');


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ale-prom-import-activator.php
 */
function activate_ale_prom_import() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ale-prom-import-activator.php';
	Ale_Prom_Import_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ale-prom-import-deactivator.php
 */
function deactivate_ale_prom_import() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ale-prom-import-deactivator.php';
	Ale_Prom_Import_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ale_prom_import' );
register_deactivation_hook( __FILE__, 'deactivate_ale_prom_import' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ale-prom-import.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_ale_prom_import() {

	$plugin = new Ale_Prom_Import();
	$plugin->run();

}
run_ale_prom_import();
