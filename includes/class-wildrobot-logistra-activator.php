<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wildrobot.app/wildrobot-logistra-cargonizer-woocommerce-integrasjon/
 * @since      1.0.0
 *
 * @package    Wildrobot_Logistra
 * @subpackage Wildrobot_Logistra/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wildrobot_Logistra
 * @subpackage Wildrobot_Logistra/includes
 * @author     Robertosnap <robertosnap@pm.me>
 */
class Wildrobot_Logistra_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		self::install_db();
	}

	public static function update()
	{
		self::install_db();
	}

	private static function install_db()
	{
		global $wpdb;

		$db_version = WILDROBOT_LOGISTRA_VERSION;
		$table_name = $wpdb->prefix . 'wildrobot_logistra_delivery_relations';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			shipping_method_identifier varchar(255) NOT NULL,
			last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			carrier tinytext NOT NULL,
			services mediumtext NOT NULL,
			print_time tinytext NOT NULL,
			printer tinytext NOT NULL,
			transfer_time tinytext NOT NULL,
			terms_of_delivery_code tinytext NOT NULL,
			terms_of_delivery_name tinytext NOT NULL,
			terms_of_delivery_customer_number tinytext NOT NULL,
			export_reason tinytext NOT NULL,
			export_type tinytext NOT NULL,
			bring_priority tinytext NOT NULL,
			wr_id tinytext NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		$success = empty($wpdb->last_error);
		if (!$success) {
			throw new Exception($wpdb->last_error);
		}


		update_option('wildrobot_logistra_db_version', $db_version);
	}
}
