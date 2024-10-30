<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wildrobot.app/wildrobot-logistra-cargonizer-woocommerce-integrasjon/
 * @since             1.0.0
 * @package           Wildrobot_Logistra
 *
 * @wordpress-plugin
 * Plugin Name:       Wildrobot frakt integrasjon
 * Plugin URI:        https://wildrobot.app/wildrobot-logistra-cargonizer-woocommerce-integrasjon/
 * Description:       Integrate WooCommerce with Logistra Cargonizer or Profrakt - Freight administration made easy by Wildrobot!
 * Version:           7.4.4
 * Author:            Robertosnap
 * Author URI:        https://wildrobot.app/wildrobot-logistra-cargonizer-woocommerce-integrasjon/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wildrobot-logistra
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Require composer
require 'vendor/autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WILDROBOT_LOGISTRA_VERSION', '7.4.4');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wildrobot-logistra-activator.php
 */
function activate_wildrobot_logistra()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wildrobot-logistra-activator.php';
	Wildrobot_Logistra_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wildrobot-logistra-deactivator.php
 */
function deactivate_wildrobot_logistra()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wildrobot-logistra-deactivator.php';
	Wildrobot_Logistra_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wildrobot_logistra');
register_deactivation_hook(__FILE__, 'deactivate_wildrobot_logistra');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wildrobot-logistra.php';

/* Declare HPOS compatability */
add_action('before_woocommerce_init', function () {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
	}
});



/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wildrobot_logistra()
{

	$plugin = new Wildrobot_Logistra();
	$plugin->run();

	/* Check if woocommerce is active, cant make this check on multisite */
	if (!is_multisite()) {
		if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			add_action('admin_notices', function () {
				$class = 'notice notice-error';
				$message = __('Du må aktivere Woocommerce for å bruke Wildrobot integrasjonen.', 'wildrobot-logistra');

				printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
			});
			add_action('admin_init', function () {
				deactivate_plugins(plugin_basename(__FILE__), true);
			});
			return;
		}
	}

	// Maybe show notice adjust settings
	if (empty(get_option("wildrobot_logistra_cargonizer_apikey"))) {
		add_action('admin_notices', function () {
			$class = 'notice notice-warning';
			$message = __('Du må konfigurere frakt integrasjonen.', 'wildrobot-logistra');
			$url = admin_url("admin.php?page=wc-settings&tab=wildrobot-logistra_tab");
			$link_text = __('Trykk her. ', 'wildrobot-logistra');

			printf('<div class="%1$s"><p>%2$s <a href="%3$s">%4$s</a></p></div>', esc_attr($class), esc_html($message), esc_url($url), esc_html($link_text));
		});
	} else if (empty(get_option('wildrobot_logistra_sender_id'))) {
		add_action('admin_notices', function () {
			$class = 'notice notice-warning';
			$message = __('Du må velge avsender for dine leveranser i frakt integrasjonen.', 'wildrobot-logistra');
			$url = admin_url("admin.php?page=wc-settings&tab=wildrobot-logistra_tab");
			$link_text = __('Trykk her. ', 'wildrobot-logistra');

			printf('<div class="%1$s"><p>%2$s <a href="%3$s">%4$s</a></p></div>', esc_attr($class), esc_html($message), esc_url($url), esc_html($link_text));
		});
	}


	$db_version = get_option('wildrobot_logistra_db_version', false);
	if ($db_version !== WILDROBOT_LOGISTRA_VERSION) {
		add_action('admin_notices', function () {
			$class = 'notice notice-info';
			$message = __('Wildrobot integrasjon oppdaterte databasen i bakgrunnen.', 'wildrobot-logistra');
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
		});
		require_once plugin_dir_path(__FILE__) . 'includes/class-wildrobot-logistra-activator.php';
		Wildrobot_Logistra_Activator::update();
	}
}
run_wildrobot_logistra();
