<?php

class Wildrobot_Logistra_Options
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

	private static $admin_options = [
		'wildrobot_logistra_apikey' => [
			"default" => "",
		],
		'wildrobot_logistra_cargonizer_apikey' => [
			"default" => "",
		],
		'wildrobot_logistra_backend_token' => [
			"default" => "",
		],
		'wildrobot_logistra_settings_current_tab' => [
			"default" => "",
		],
		'wildrobot_logistra_sender_id' => [
			"default" => "",
		],
		'wildrobot_logistra_sender_name' => [
			"default" => "",
		],
		'wildrobot_logistra_printers' => [
			"default" => [],
		],
		'wildrobot_logistra_migrations' => [
			"default" => "",
		],
		'wildrobot_logistra_enviroment' => [
			"default" => "PROD",
		],
		'wildrobot_logistra_cargonizer_backend_url' => [
			"default" => "https://cargonizer.no",
		],
		'wildrobot_logistra_cargonizer_backend_url_profrakt' => [
			"default" => "https://edi.no/compat",
		],
		'wildrobot_logistra_backend_url' => [
			"default" => "https://backend.prod.wildrobot.app/v1",
		],
		"wildrobot_logistra_transport_agreements" => [
			"default" => []
		],
		"wildrobot_logistra_cargonizer_provider" => [
			"default" => "logistra"
		],
	];

	private static $public_options = [
		'wildrobot_logistra_pickuppoint_cart' => [
			"default" => "no",
		],
		'wildrobot_logistra_pickuppoint_checkout' => [
			"default" => "no",
		],
	];

	private static $return_address_options = [
		"wildrobot_logistra_return_address_address1" => [
			"default" => "",
		],
		"wildrobot_logistra_return_address_address2" => [
			"default" => "",
		],
		"wildrobot_logistra_return_address_city" => [
			"default" => "",
		],
		"wildrobot_logistra_return_address_country" => [
			"default" => "",
		],
		"wildrobot_logistra_return_address_postcode" => [
			"default" => "",
		],
		"wildrobot_logistra_return_address_phone" => [
			"default" => "",
		],
		"wildrobot_logistra_return_address_mobile" => [
			"default" => "",
		],
		"wildrobot_logistra_return_address_name" => [
			"default" => "",
		],
	];

	private static $general_options = [
		'wildrobot_logistra_setting_complete_order' => [
			"default" => "no",
		],
		'wildrobot_logistra_setting_complete_order_to_status' => [
			"default" => "wc-completed",
		],
		'wildrobot_logistra_setting_send_consignment_on_complete_order' => [
			"default" => "no",
		],
		'wildrobot_logistra_pickuppoint_cart' => [
			"default" => "no",
		],
		'wildrobot_logistra_pickuppoint_checkout' => [
			"default" => "no",
		],
		'wildrobot_logistra_add_org_name_to_order' => [
			"default" => "no",
		],
		'wildrobot_logistra_static_weight_on_orders' => [
			"default" => "no",
		],
		'wildrobot_logistra_static_weight_amount' => [
			"default" => 0,
		],
		'wildrobot_logistra_consignment_description_product_names' => [
			"default" => "yes",
		],
		'wildrobot_logistra_use_table_rate_integration' => [
			"default" => "no",
		],
		'wildrobot_logistra_freight_track_url_email' => [
			"default" => "no",
		],
		'wildrobot_logistra_free_freight_notice' => [
			"default" => "no",
		],
		'wildrobot_logistra_free_freight_almost_value' => [
			"default" => 0,
		],
		'wildrobot_logistra_fallback_freight_product' => [
			"default" => "no",
		],
		'wildrobot_logistra_calculate_volume' => [
			"default" => "no",
		],
		'wildrobot_logistra_calculate_dimensions' => [
			"default" => "no",
		],
		'wildrobot_logistra_pickuppoint_checkout_inline' => [
			"default" => "no",
		],
		'wildrobot_logistra_consignee_message' => [
			"default" => '',
		],
		'wildrobot_logistra_hide_send_buttons' => [
			"default" => 'no',
		],
		'wildrobot_logistra_hide_override_buttons' => [
			"default" => 'no',
		],
		'wildrobot_logistra_filter_out_pakkeautomat' => [
			"default" => 'no',
		],
		'wildrobot_logistra_show_label_after_send_order' => [
			"default" => 'no',
		],
	];

	private static $other_options = [
		"wildrobot_logistra_senders" => [
			"default" => [],
		],
		"wildrobot_order_automation_rules" => [
			"default" => [],
		],
		"wildrobot_order_automation_active" => [
			"default" => "no",
		],
	];

	private static $print_transfer_options = [
		'wildrobot_logistra_printer_default' => [
			"default" => "",
		],
		'wildrobot_logistra_printer_interval' => [
			"default" => "",
		],
		'wildrobot_logistra_printer_interval_time' => [
			"default" => "",
		],
		'wildrobot_logistra_selected_transfer_method' => [
			"default" => "",
		],
		'wildrobot_logistra_selected_transfer_time' => [
			"default" => "",
		],
	];

	private static $user_options = [
		"wildrobot_logistra_user_printer" => [
			"default" => ""
		]
	];

	private static $picklist_options = [
		'wildrobot_logistra_picklist_active' => [
			"default" => "no",
		],
		'wildrobot_logistra_picklist_printer' => [
			"default" => "",
		],
		'wildrobot_logistra_picklist_page' => [
			"default" => "",
		],
	];


	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}


	private static function all_options()
	{
		return array_merge(
			self::$admin_options,
			self::$return_address_options,
			self::$general_options,
			self::$other_options,
			self::$print_transfer_options,
			self::$picklist_options
		);
	}

	private static function all_options_keys()
	{
		return array_merge(
			array_keys(self::$admin_options),
			array_keys(self::$return_address_options),
			array_keys(self::$general_options),
			array_keys(self::$other_options),
			array_keys(self::$print_transfer_options),
			array_keys(self::$picklist_options)
		);
	}

	public static function get_admin_options()
	{
		$options = [];
		foreach (self::$admin_options as $key => $value) {
			$options = array_merge($options, [$key => get_option($key, $value["default"])]);
		}

		$wc_options = [
			'wc_capabilities' => wp_get_current_user()->allcaps ?? (object) [],
			'wc_order_statuses' => wc_get_order_statuses() ?? (object) [],
			'wc_admin_email' => get_option('admin_email') ?? "",
			'wc_shop_name' => get_bloginfo('name') ?? "",
			'wc_page_title' => site_url() ?? "",
			'wc_dimension_unit' => get_option('woocommerce_dimension_unit') ?? "",
			'wc_weight_unit' =>  get_option('woocommerce_weight_unit') ?? "",
			"wildrobot_logistra_user_printer" => get_user_option("wildrobot_logistra_user_printer", ""),
		];

		return array_merge($options, $wc_options);
	}

	public static function get_public_options()
	{
		$options = [];
		foreach (self::$public_options as $key => $value) {
			$options = array_merge($options, [$key => get_option($key, $value["default"])]);
		}

		$wc_options = [
			'wc_page_title' => site_url() ?? "",
			'is_cart' => is_cart() ? "yes" : "no",
			'is_checkout' => is_checkout() ? "yes" : "no",
		];

		return array_merge($options, $wc_options);
	}
	public static function get_options_safe($options_to_get)
	{
		$return_options = [];
		$gettable_options = self::all_options();
		foreach ($options_to_get as $option) {
			if (array_key_exists($option, $gettable_options)) {
				$return_options = array_merge($return_options, [$option => get_option($option, $gettable_options[$option]["default"])]);
			}
		}

		return $return_options;
	}

	public static function update_options($settings_to_update)
	{
		$response = [];
		$updateable_options = self::all_options_keys();
		$user_option_keys = array_keys(self::$user_options);
		foreach ($settings_to_update as $key => $value) {
			if (in_array($key, $updateable_options)) {
				$updated = update_option($key, $value, true);
				$response = array_merge($response, [
					$key => [
						"message" => "",
						"updated" => $updated,
						"value" => get_option($key),
					]
				]);
			} else if (in_array($key, $user_option_keys)) {
				$user = wp_get_current_user();
				$updated = update_user_option($user->data->ID, $key, $value);
				$response = array_merge($response, [
					$key => [
						"message" => "",
						"updated" => $updated,
						"value" => get_user_option($key),
					]
				]);
			} else {
				$response = array_merge($response, [
					$key => [
						"message" => _("Denne innstillingen er ikke godkjent for oppdatering av Wildrobot"),
						"updated" => false,
						"value" => ""
					]
				]);
				throw new Exception(__($key . " er ikke godkjent for oppdatering av Wildrobot og kan ikke oppdateres."));
			}
		}
		return $response;
	}

	public static function set_semi_constants()
	{
		if (empty(get_option("wildrobot_logistra_backend_url"))) {
			update_option('wildrobot_logistra_backend_url', "https://backend.prod.wildrobot.app/v1", true);
			update_option('wildrobot_logistra_backend_url_DEV', "https://backend.dev.wildrobot.app/v1", true);
		}
		if (empty(get_option("wildrobot_logistra_cargonizer_backend_url"))) {
			update_option('wildrobot_logistra_cargonizer_backend_url', "https://cargonizer.no", true);
			update_option('wildrobot_logistra_cargonizer_backend_url_DEV', "https://sandbox.cargonizer.no", true);
		}
		if (empty(get_option("wildrobot_logistra_cargonizer_backend_url_profrakt"))) {
			update_option('wildrobot_logistra_cargonizer_backend_url_profrakt', "https://edi.no/compat", true);
			update_option('wildrobot_logistra_cargonizer_backend_url_profrakt_DEV', "https://edi-stage-de777ts5.ew.gateway.dev/compat", true);
		}
	}

	public static function set_dev()
	{

		if (get_option("wildrobot_logistra_enviroment") === "PROD") {
			// save possible PROD info
			update_option("wildrobot_logistra_apikey_PROD", get_option('wildrobot_logistra_apikey'));
			update_option("wildrobot_logistra_cargonizer_apikey_PROD", get_option('wildrobot_logistra_cargonizer_apikey'));
			update_option("wildrobot_logistra_backend_token_PROD", get_option('wildrobot_logistra_backend_token'));
			update_option("wildrobot_logistra_sender_id_PROD", get_option('wildrobot_logistra_sender_id'));
			update_option("wildrobot_logistra_printers_PROD", get_option('wildrobot_logistra_printers'));
			update_option("wildrobot_logistra_cargonizer_backend_url_PROD", get_option('wildrobot_logistra_cargonizer_backend_url'));
			update_option("wildrobot_logistra_backend_url_PROD", get_option('wildrobot_logistra_backend_url'));
			update_option("wildrobot_logistra_transport_agreements_PROD", get_option('wildrobot_logistra_transport_agreements'));
			update_option("wildrobot_logistra_cargonizer_backend_url_profrakt_PROD", get_option('wildrobot_logistra_cargonizer_backend_url_profrakt'));
			// // !empty(get_option('xxx')) ?? update_option("_PROD", get_option('xxxx'), true);
		}


		return self::update_options([
			"wildrobot_logistra_enviroment" => "DEV",
			"wildrobot_logistra_apikey" => !empty(get_option('wildrobot_logistra_apikey_DEV')) ? get_option('wildrobot_logistra_apikey_DEV') : "",
			"wildrobot_logistra_cargonizer_apikey" => !empty(get_option('wildrobot_logistra_cargonizer_apikey_DEV')) ? get_option('wildrobot_logistra_cargonizer_apikey_DEV') : "",
			"wildrobot_logistra_backend_token" => !empty(get_option('wildrobot_logistra_backend_token_DEV')) ? get_option('wildrobot_logistra_backend_token_DEV') : "",
			"wildrobot_logistra_sender_id" => !empty(get_option('wildrobot_logistra_sender_id_DEV')) ? get_option('wildrobot_logistra_sender_id_DEV') : "",
			"wildrobot_logistra_printers" => !empty(get_option('wildrobot_logistra_printers_DEV')) ? get_option('wildrobot_logistra_printers_DEV') : [],
			"wildrobot_logistra_cargonizer_backend_url" => !empty(get_option('wildrobot_logistra_cargonizer_backend_url_DEV')) ? get_option('wildrobot_logistra_cargonizer_backend_url_DEV') : "",
			"wildrobot_logistra_backend_url" => !empty(get_option('wildrobot_logistra_backend_url_DEV')) ? get_option('wildrobot_logistra_backend_url_DEV') : "",
			"wildrobot_logistra_transport_agreements" => !empty(get_option('wildrobot_logistra_transport_agreements_DEV')) ? get_option('wildrobot_logistra_transport_agreements_DEV') : [],
			"wildrobot_logistra_cargonizer_backend_url_profrakt" => !empty(get_option('wildrobot_logistra_cargonizer_backend_url_profrakt_DEV')) ? get_option('wildrobot_logistra_cargonizer_backend_url_profrakt_DEV') : "https://edi-stage-de777ts5.ew.gateway.dev/compat",
		]);
	}

	public static function set_prod()
	{
		if (get_option("wildrobot_logistra_enviroment") === "DEV") {
			// save possible DEV info
			update_option("wildrobot_logistra_apikey_DEV", get_option('wildrobot_logistra_apikey'));
			update_option("wildrobot_logistra_cargonizer_apikey_DEV", get_option('wildrobot_logistra_cargonizer_apikey'));
			update_option("wildrobot_logistra_backend_token_DEV", get_option('wildrobot_logistra_backend_token'));
			update_option("wildrobot_logistra_sender_id_DEV", get_option('wildrobot_logistra_sender_id'));
			update_option("wildrobot_logistra_printers_DEV", get_option('wildrobot_logistra_printers'));
			update_option("wildrobot_logistra_cargonizer_backend_url_DEV", get_option('wildrobot_logistra_cargonizer_backend_url'));
			update_option("wildrobot_logistra_backend_url_DEV", get_option('wildrobot_logistra_backend_url'));
			update_option("wildrobot_logistra_transport_agreements_DEV", get_option('wildrobot_logistra_transport_agreements'));
			update_option("wildrobot_logistra_cargonizer_backend_url_profrakt_DEV", get_option('wildrobot_logistra_cargonizer_backend_url_profrakt'));
		}


		// TODO - Probably need to set some env variables
		return self::update_options([
			"wildrobot_logistra_enviroment" => "PROD",
			"wildrobot_logistra_apikey" => !empty(get_option('wildrobot_logistra_apikey_PROD'))  ? get_option('wildrobot_logistra_apikey_PROD') : "",
			"wildrobot_logistra_cargonizer_apikey" => !empty(get_option('wildrobot_logistra_cargonizer_apikey_PROD'))  ? get_option('wildrobot_logistra_cargonizer_apikey_PROD') : "",
			"wildrobot_logistra_backend_token" => !empty(get_option('wildrobot_logistra_backend_token_PROD'))  ? get_option('wildrobot_logistra_backend_token_PROD') : "",
			"wildrobot_logistra_sender_id" => !empty(get_option('wildrobot_logistra_sender_id_PROD'))  ? get_option('wildrobot_logistra_sender_id_PROD') : "",
			"wildrobot_logistra_printers" => !empty(get_option('wildrobot_logistra_printers_PROD'))  ? get_option('wildrobot_logistra_printers_PROD') : [],
			"wildrobot_logistra_cargonizer_backend_url" => !empty(get_option('wildrobot_logistra_cargonizer_backend_url_PROD'))  ? get_option('wildrobot_logistra_cargonizer_backend_url_PROD') : "",
			"wildrobot_logistra_backend_url" => !empty(get_option('wildrobot_logistra_backend_url_PROD'))  ? get_option('wildrobot_logistra_backend_url_PROD') : "",
			"wildrobot_logistra_transport_agreements" => !empty(get_option('wildrobot_logistra_transport_agreements_PROD'))  ? get_option('wildrobot_logistra_transport_agreements_PROD') : [],
			"wildrobot_logistra_cargonizer_backend_url_profrakt" => !empty(get_option('wildrobot_logistra_cargonizer_backend_url_profrakt_PROD'))  ? get_option('wildrobot_logistra_cargonizer_backend_url_profrakt_PROD') : [],
		]);
	}
}
