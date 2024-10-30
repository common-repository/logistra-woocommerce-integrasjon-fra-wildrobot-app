<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wildrobot.app/wildrobot-logistra-cargonizer-woocommerce-integrasjon/
 * @since      1.0.0
 *
 * @package    Wildrobot_Logistra
 * @subpackage Wildrobot_Logistra/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wildrobot_Logistra
 * @subpackage Wildrobot_Logistra/public
 * @author     Robertosnap <robertosnap@pm.me>
 */
class Wildrobot_Logistra_Public
{

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	function wildrobot_logistra_locate_template($template, $template_name, $template_path)
	{
		global $woocommerce;
		$_template = $template;
		if (!$template_path)
			$template_path = $woocommerce->template_url;

		// $plugin_path  = untrailingslashit(plugin_dir_path(__FILE__))  . '/template/woocommerce/';
		$plugin_path  = plugin_dir_path(dirname(__FILE__)) . 'templates/woocommerce/';
		// $plugin_path  = plugin_dir_path(dirname(__FILE__) . '/template/woocommerce/');

		// Look within passed path within the theme - this is priority
		$template = locate_template(
			array(
				$template_path . $template_name,
				$template_name
			)
		);

		if (!$template && file_exists($plugin_path . $template_name))
			$template = $plugin_path . $template_name;

		if (!$template)
			$template = $_template;

		return $template;
	}

	public function add_picklist_to_page($content = null)
	{
		$page_id = get_the_ID();
		if (is_page() && (get_option("wildrobot_logistra_picklist_active") === "yes")  && !empty($page_id)) {
			if ($page_id == get_option('wildrobot_logistra_picklist_page', null)) {
				$content = '<div id="wildrobot-complete-picklist-order">Laster plukkliste fullføring...</div>';
			}
		}
		return $content;
	}

	public function add_service_partner_picker_field($fields)
	{
		$fields['billing']['shipping_service_partner'] = array(
			'label'     => __('Utleveringssted', 'logistra-robots'),
			'placeholder'   => _x('Velg utleveringssted', 'placeholder', 'logistra-robots'),
			'required'  => false,
			'class'     => array('form-row-wide hidden'),
			'clear'     => true,
			'type' => 'text',
		);

		return $fields;
	}

	function wildrobot_logistra_woocommerce_checkout_process($order_id, $posted_data, $order)
	{
		if (isset($_POST["logistra_robots_select_servicepartner"]) && !empty($_POST["logistra_robots_select_servicepartner"])) {
			$service_partner_number = $_POST["logistra_robots_select_servicepartner"];
		} else if (isset($_POST["shipping_service_partner"]) && !empty($_POST["shipping_service_partner"])) {
			$service_partner_number = $_POST["shipping_service_partner"];
		}
		if (!empty($service_partner_number)) {
			$order->update_meta_data("_shipping_service_partner", $service_partner_number);
			$order->save();
		}
	}

	/**
	 * Generates the service partner object for the cart shipping template based on the selected shipping method.
	 *
	 * This function retrieves the delivery relation and transport agreement based on the chosen shipping method.
	 * It checks if service partners are possible and filters out certain carriers. It then fetches service partners
	 * based on the destination postcode and country. The function returns a service partner object which includes
	 * details like carrier name, identifier, and available service partners.
	 *
	 * @param object $service_partner_object An object to be populated with service partner details.
	 * @param array $args An array containing shipping method and destination details. Minimum values required are "chosen_method" and "package" with "destination" containing "postcode" and "country".
	 * [
	 * 	"chosen_method": string,
	 * 	"package": [
	 * 		"destination": [
	 * 			"postcode": string,
	 * 			"country": string
	 * 		]
	 * 	]
	 * ]
	 * @return object|bool Returns the service partner object with populated details or false on failure.
	 * {
	 * 	"carrier_name": string,
	 * 	"carrier_identifier": string,
	 * 	"service_partner_possible": bool,
	 * 	"requires_service_partner": bool,
	 * 	"service_partners": array,
	 * 	"service_partner_select_values": array
	 * }
	 */
	function logistra_robots_cart_shipping_template_args($service_partner_object, $args)
	{
		try {
			$delivery_realtion = Wildrobot_Logistra_DB::get_delivery_relation_with_transport_agreement($args["chosen_method"]);
			if (empty($delivery_realtion)) {
				return false;
			}
			$transportAgreement = $delivery_realtion["transport_agreement"];
			$service_partner_possible = false;
			if ($transportAgreement["service_partner_possible"]) {
				$service_partner_possible = true;
			}
			if (in_array($transportAgreement["identifier"], ["bring_small_parcel_a_no_rfid", "bring_small_parcel_a", "bring2_small_parcel_a_no_rfid", "bring2_small_parcel_a", "postnord_mypack_home_small"])) {
				$service_partner_possible = false;
			}
			$carrier_identifier = $transportAgreement["ta_carrier"]["identifier"];
			$carrier_name = $transportAgreement["ta_carrier"]["name"];

			$postcode = $args["package"]["destination"]["postcode"];
			$country = $args["package"]["destination"]["country"];
			if (empty($postcode)) {
				return false;
			}
			$res = Wildrobot_Logistra_Cargonizer::get_service_partners(
				$country,
				$postcode,
				$carrier_identifier,
				$delivery_realtion["wr_id"]
			);
			if (empty($res["service_partners"])) {
				$service_partner_possible = false;
			}

			$service_partner_key_label = [
				null => "Velg nærmeste for meg",
			];
			foreach ($res["service_partners"] as $service_partner) {
				$service_partner_key_label[$service_partner["number"]] = $service_partner["name"] . ", " . $service_partner["postcode"] . ", " . $service_partner["city"];
			}
			$service_partner_object = (object) [
				"carrier_name" => $carrier_name,
				"carrier_identifier" => $carrier_identifier,
				"service_partner_possible" => $service_partner_possible,
				"requires_service_partner" => $transportAgreement["requires_service_partner"],
				"service_partners" => $res["service_partners"],
				"service_partner_select_values" => $service_partner_key_label,
			];

			return apply_filters("logistra_robots_cart_shipping_service_partner_object", $service_partner_object);
		} catch (\Throwable $error) {
			return false;
		}
	}

	public function enqueue_styles()
	{
		// wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ReactToastify.css', array(), $this->version, 'all');
	}

	public function enqueue_scripts()
	{
		$php_to_js_variables = array(
			'version' => $this->version,
			'wc_ajax_url' => WC()->ajax_url(),
			'security' => wp_create_nonce("randomTextForLogistraIntegration"),
		);

		$options = Wildrobot_Logistra_Options::get_public_options();

		$plugin_js = self::get_js_file_path('partials/frontend', 'public');
		wp_enqueue_script($this->plugin_name . "-public-js", $plugin_js, ['wp-element'], $this->version, true);
		wp_localize_script($this->plugin_name . '-public-js', 'wildrobotLogistraPublic', array_merge($php_to_js_variables, $options));
	}

	private static function get_js_file_path($folder, $file)
	{
		$files = glob(plugin_dir_path(__FILE__) . $folder . '/' . $file . '*.js');
		if (!empty($files)) {
			$full_path = $files[0];
			$app_pos = strpos($full_path, $file . '.');
			$file_name = substr($full_path, $app_pos);
			return plugin_dir_url(__FILE__) . $folder . '/' . $file_name;
		} else {
			return 'http://localhost:3000/static/js/client.js';
		}
	}
}
