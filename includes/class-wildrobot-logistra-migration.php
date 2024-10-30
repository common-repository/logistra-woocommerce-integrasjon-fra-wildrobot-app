<?php

class Wildrobot_Logistra_Migration
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function run_migrations()
	{
		$migrations = get_option('wildrobot_logistra_migrations', []);

		if (!in_array("freight_relations_6_0_0", $migrations)) {
			self::freight_relations_6_0_0();
		}
		if (!in_array("options_6_0_0", $migrations)) {
			self::options_6_0_0();
		}
		// Only for testing
		// update_option("wildrobot_logistra_transport_agreements", []);
		// update_option("wildrobot_logistra_migrations", ["options_6_0_0", "freight_relations_6_0_0"]);
	}


	private static function freight_relations_6_0_0()
	{
		$migrations = get_option('wildrobot_logistra_migrations', []);
		try {
			// Dont run freight_relations_6_0_0 migrations if user never setup a api key
			if (empty(get_option('logistra-robots-apikey-v1', null))) {
				array_push($migrations, "freight_relations_6_0_0");
				update_option("wildrobot_logistra_migrations", $migrations, true);
				return;
			}
			add_action('admin_notices', function () {
				$class = 'notice notice-info';
				$message = __('Wildrobot integrasjon migrerte frakt relasjoner for versjon 6.0.0', 'wildrobot-logistra');
				printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
			});
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/deprecated/class-wildrobot-logstra-deprecated-v6.php';
			$result = Wildrobot_Logistra_Deprecated_V6::migrate_shipping_method_relation_from_below_version_5();
			if (!empty($result["errors"])) {
				add_action('admin_notices', function () {
					$class = 'notice notice-warning';
					$message = __('Kunne ikke migrerere alle leveranse relasjoner. Vennligst kontroller dine fraktmetode til leveranse produkt relasjon i Woocommerce -> Innstillinger -> Logistra -> Fraktmetoder og Fraktprodukter', 'wildrobot-logistra');
					printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
				});
				$logger = new WC_Logger();
				$context = ['source' => 'wildrobot-logistra-migration'];
				foreach ($result["errors"] as $error) {
					$logger->warning($error, $context);
				};
			}
			array_push($migrations, "freight_relations_6_0_0");
			update_option("wildrobot_logistra_migrations", $migrations, true);
		} catch (\Throwable $error) {
			add_action('admin_notices', function () use ($error) {
				$class = 'notice notice-error';
				$message = __('Kunne ikke migrere frakt relasjoner. Feilmelding: ' . $error->getMessage(), 'wildrobot-logistra');
				printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
			});
		}
	}

	public static function options_6_0_0()
	{
		try {
			!empty(get_option('logistra-robots-apikey', null)) ? update_option("wildrobot_logistra_cargonizer_apikey",  get_option('logistra-robots-apikey'), true) : null;
			!empty(get_option('logistra-robots-apikey-v1', null)) ?  update_option("wildrobot_logistra_apikey", get_option('logistra-robots-apikey-v1'), true) : null;
			!empty(get_option('logistra-robots-token-v1', null)) ?  update_option("wildrobot_logistra_backend_token", get_option('logistra-robots-token-v1'), true) : null;
			!empty(get_option('logistra-robots-settings-current-tab', null)) ?  update_option("wildrobot_logistra_settings_current_tab", get_option('logistra-robots-settings-current-tab'), true) : null;

			!empty(get_option('logistra-robots-senderid', null)) ?  update_option("wildrobot_logistra_sender_id", get_option('logistra-robots-senderid'), true) : null;
			!empty(get_option('logistra-robots-sender-name', null)) ?  update_option("wildrobot_logistra_sender_name", get_option('logistra-robots-sender-name'), true) : null;
			!empty(get_option('logistra-robots-printers', null)) ?  update_option("wildrobot_logistra_printers", get_option('logistra-robots-printers'), true) : null;
			// !empty(get_option('logistra-robots-migrations', null)) ?  update_option("wildrobot_logistra_migrations", get_option('logistra-robots-migrations'), true) : null;

			!empty(get_option('logistra-robots-return-address-address1', null)) ?  update_option("wildrobot_logistra_return_address_address1", get_option('logistra-robots-return-address-address1'), true) : null;
			!empty(get_option('logistra-robots-return-address-address2', null)) ?  update_option("wildrobot_logistra_return_address_address2", get_option('logistra-robots-return-address-address2'), true) : null;
			!empty(get_option('logistra-robots-return-address-city', null)) ?  update_option("wildrobot_logistra_return_address_city", get_option('logistra-robots-return-address-city'), true) : null;
			!empty(get_option('logistra-robots-return-address-country', null)) ?  update_option("wildrobot_logistra_return_address_country", get_option('logistra-robots-return-address-country'), true) : null;
			!empty(get_option('logistra-robots-return-address-postcode', null)) ?  update_option("wildrobot_logistra_return_address_postcode", get_option('logistra-robots-return-address-postcode'), true) : null;


			!empty(get_option('logistra-robots-printer-default', null)) ? update_option("wildrobot_logistra_printer_default", get_option('logistra-robots-printer-default'), true) : null;
			!empty(get_option('logistra-robots-printer-interval', null)) ? update_option("wildrobot_logistra_printer_interval", get_option('logistra-robots-printer-interval'), true) : null;
			!empty(get_option('logistra-robots-printer-interval-time', null)) ? update_option("wildrobot_logistra_printer_interval_time", get_option('logistra-robots-printer-interval-time'), true) : null;
			!empty(get_option('logistra-robots-selected-transfer-method', null)) ? update_option("wildrobot_logistra_selected_transfer_method", get_option('logistra-robots-selected-transfer-method'), true) : null;
			!empty(get_option('logistra-robots-selected-transfer-time', null)) ? update_option("wildrobot_logistra_selected_transfer_time", get_option('logistra-robots-selected-transfer-time'), true) : null;
			!empty(get_option('logistra-robots-senders', null)) ? update_option("wildrobot_logistra_senders", get_option('logistra-robots-senders'), true) : null;

			// Dont update this, because we need users to update their transport agreements
			// !empty(get_option('logistra-robots-transport-agreements', null)) ? update_option("wildrobot_logistra_transport_agreements", get_option('logistra-robots-transport-agreements'), true) : null;

			!empty(get_option('logistra-robots-picklist-active', null)) ? update_option("wildrobot_logistra_picklist_active", get_option('logistra-robots-picklist-active'), true) : null;
			!empty(get_option('logistra-robots-picklist-printer', null)) ? update_option("wildrobot_logistra_picklist_printer", get_option('logistra-robots-picklist-printer'), true) : null;
			!empty(get_option('logistra-robots-picklist-page', null)) ? update_option("wildrobot_logistra_picklist_page", get_option('logistra-robots-picklist-page'), true) : null;


			!empty(get_option('logistra-robots-warning-label-tags', null)) ? update_option("wildrobot_logistra_warning_label_tags", get_option('logistra-robots-warning-label-tags'), true) : null;

			!empty(get_option('logistra-robots-setting-complete-order', null)) ? update_option("wildrobot_logistra_setting_complete_order", get_option('logistra-robots-setting-complete-order'), true) : null;
			!empty(get_option('logistra-robots-setting-complete-order-to-status', null)) ? update_option("wildrobot_logistra_setting_complete_order_to_status", get_option('logistra-robots-setting-complete-order-to-status'), true) : null;
			!empty(get_option('logistra-robots-setting-send-consignment-on-complete-order', null)) ? update_option("wildrobot_logistra_setting_send_consignment_on_complete_order", get_option('logistra-robots-setting-send-consignment-on-complete-order'), true) : null;
			!empty(get_option('logistra-robots-pickuppoint-cart', null)) ? update_option("wildrobot_logistra_pickuppoint_cart", get_option('logistra-robots-pickuppoint-cart'), true) : null;
			!empty(get_option('logistra-robots-pickuppoint-checkout', null)) ? update_option("wildrobot_logistra_pickuppoint_checkout", get_option('logistra-robots-pickuppoint-checkout'), true) : null;
			!empty(get_option('logistra-robots-add-org-name-to-order', null)) ? update_option("wildrobot_logistra_add_org_name_to_order", get_option('logistra-robots-add-org-name-to-order'), true) : null;
			!empty(get_option('logistra-robots-static-weight-on-orders', null)) ? update_option("wildrobot_logistra_static_weight_on_orders", get_option('logistra-robots-static-weight-on-orders'), true) : null;
			!empty(get_option('logistra-robots-static-weight-in-kg', null)) ? update_option("wildrobot_logistra_static_weight_amount", get_option('logistra-robots-static-weight-in-kg'), true) : null;
			!empty(get_option('logistra-robots-consignment-description-product-names', null)) ? update_option("wildrobot_logistra_consignment_description_product_names", get_option('logistra-robots-consignment-description-product-names'), true) : null;
			!empty(get_option('logistra-robots-use-table-rate-integration', null)) ? update_option("wildrobot_logistra_use_table_rate_integration", get_option('logistra-robots-use-table-rate-integration'), true) : null;
			!empty(get_option('logistra-robots-freight-track-url-email', null)) ? update_option("wildrobot_logistra_freight_track_url_email", get_option('logistra-robots-freight-track-url-email'), true) : null;
			!empty(get_option('logistra-robots-free-freight-notice', null)) ? update_option("wildrobot_logistra_free_freight_notice", get_option('logistra-robots-free-freight-notice'), true) : null;
			!empty(get_option('logistra-robots-free-freight-almost-value', null)) ? update_option("wildrobot_logistra_free_freight_almost_value", get_option('logistra-robots-free-freight-almost-value'), true) : null;
			!empty(get_option('logistra-robots-fallback-freight-product', null)) ? update_option("wildrobot_logistra_fallback_freight_product", get_option('logistra-robots-fallback-freight-product'), true) : null;
			!empty(get_option('logistra-robots-calculate-volume', null)) ? update_option("wildrobot_logistra_calculate_volume", get_option('logistra-robots-calculate-volume'), true) : null;
			!empty(get_option('logistra-robots-calculate-dimensions', null)) ? update_option("wildrobot_logistra_calculate_dimensions", get_option('logistra-robots-calculate-dimensions'), true) : null;
			!empty(get_option('logistra-robots-pickuppoint-checkout-inline', null)) ? update_option("wildrobot_logistra_pickuppoint_checkout_inline", get_option('logistra-robots-pickuppoint-checkout-inline'), true) : null;

			// !empty(get_option('XXXLOGROBOTXXX', null)) ? update_option("XXXWILROBXXXX", get_option('XXXLOGROBOTXXX'), true) : null;


			$migrations = get_option('wildrobot_logistra_migrations', []);
			array_push($migrations, "options_6_0_0");
			update_option("wildrobot_logistra_migrations", $migrations, true);

			add_action('admin_notices', function () {
				$class = 'notice notice-info';
				$message = __('Wildrobot integrasjon migrerte innstillinger for versjon 6', 'logistra-robots');
				printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
			});
			add_action('admin_notices', function () {
				$class = 'notice notice-warning';
				$message = "Vennligst oppdatert avsender for Logistra for å fullføre oppgradering. (Under APINØKKEL OG AVSENDER)";
				$url = admin_url("admin.php?page=wc-settings&tab=wildrobot-logistra_tab");
				$link_text = __('Trykk her. ', 'wildrobot-logistra');

				printf('<div class="%1$s"><p>%2$s <a href="%3$s">%4$s</a></p></div>', esc_attr($class), esc_html($message), esc_url($url), esc_html($link_text));
			});
		} catch (\Throwable $error) {
			add_action('admin_notices', function () use ($error) {
				$class = 'notice notice-info';
				$message = 'Kunne ikke migrere innstillinger for versjon 6. Feilmelding: ';
				$message .= $error->getMessage();
				printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
			});
			$logger = new WC_Logger();
			$context = ['source' => 'wildrobot-logistra-migration'];
			$logger->error($error->getMessage(), $context);
		}
	}
}
