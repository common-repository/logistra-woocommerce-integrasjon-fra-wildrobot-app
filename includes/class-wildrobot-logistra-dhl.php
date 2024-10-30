<?php

class Wildrobot_Logistra_DHL
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

	public function logistra_robots_add_shipping_option_commodity_code()
	{
		global $post;
		$product = wc_get_product($post->ID);

		echo '<h5 style="background-color: #f0f0f0; padding: 10px; border-left: 4px solid #FAFF6F; margin-bottom: 10px;">' . __('Wildrobot eksport innstillinger', 'wildrobot-logistra') . '</h5>';
		echo '<p class="description" style="color: #777; padding-left: 10px; padding-right: 10px; font-style: italic;">' . __('Denne informasjonen er nødvendig dersom du skal sende produktet ut av landet via DHL Express og elektronisk fortolling.', 'wildrobot-logistra') . '</p>';


		echo '<div class="options_group">';

		woocommerce_wp_text_input(array(
			'id'          => '_logistra_robots_product_commodity_code',
			'label'       => __('Varenummer (HS code)', 'wildrobot-logistra'),
			'placeholder' => 'F.eks. 39.24.1002',
			'desc_tip'    => 'true',
			'description' => __('Ofte refertert til som tolltariffer, se mer her: https://tolltariffen.toll.no/tolltariff', 'wildrobot-logistra'),
			'value'       => $product->get_meta('_logistra_robots_product_commodity_code', true),
		));

		woocommerce_wp_text_input(array(
			'id'          => '_logistra_robots_product_freight_description',
			'label'       => __('Fraktbeskrivelse', 'wildrobot-logistra'),
			'placeholder' => "Produkt beskrivelse for frakt",
			'desc_tip'    => 'true',
			'description' => __('Om ikke settes vil den hente beskrivelsen fra Woocommerce produktet.', 'wildrobot-logistra'),
			'value'       => $product->get_meta('_logistra_robots_product_freight_description', true),
		));

		woocommerce_wp_select([
			'id'          => '_logistra_robots_product_country_of_origin',
			'label'         => __('Opprinnelsesland', 'wildrobot-logistra'),
			'placeholder' => "Velg land",
			'desc_tip'    => 'true',
			'description' => __('Sett opprinnelsesland for produktet for elektronisk faktura informasjon.', 'wildrobot-logistra'),
			'options' => WC()->countries->get_countries(),
			'value'       => $product->get_meta('_logistra_robots_product_country_of_origin', true),
		]);

		echo '<p class="description" style="color: #777; padding-left: 10px; padding-right: 10px; font-style: italic;">' . __('Her kan legge til informasjon som vil bli vist på etikett ved DHL eksport. Feks. Lithium batterier osv.', 'wildrobot-logistra') . '</p>';
		$tags = get_option("wildrobot_logistra_warning_label_tags", null);
		if ($tags === null) {
			echo '<p class=" form-field _logistra_robots_product_country_of_origin_field">
			<label for="_logistra_robots_product_freight_warning_label_no_found">Etikett informasjon</label>
				<span>Ingen etiketter funnet, vennligst opprett en nedenfor</span>
				</p>';
			// echo '<p>Ingen etiketter funnet, vennligst opprett en nedenfor</p>';
		} else {
			$this->wildrobot_logistra_woocommerce_wp_select_multiple(
				array(
					'id' => '_logistra_robots_product_freight_warning_label',
					'name' => '_logistra_robots_product_freight_warning_label[]',
					'class' => 'logistra-robots-warning-label-tags',
					'label' => __('Etikett informasjon', 'woocommerce'),
					'description' => __('Velg flere etiketter ved å holde inne CTRL (Windows/Linux) / Command (MacOS). Lag ny etikett nedenfor.', 'logistra-robots'),
					'options' => get_option("wildrobot_logistra_warning_label_tags", []),
				)
			);
		}

		woocommerce_wp_text_input(array(
			'id'          => '_logistra_robots_product_freight_warning_label_new',
			'label'       => __('Lag ny etikett', 'logistra-robots'),
			'placeholder' => '',
			'desc_tip'    => 'true',
			'description' => __('Finner du ingen eller ikke en passende tag i listen ovenfor, kan du lage en ny en her. Den vil automatisk bli lagt til som nåværende tag og andre kan begynne å bruke den også.', 'wildrobot-logistra'),
		));

		echo '</div>';
	}

	public function logistra_robots_save_shipping_option_commodity_code($post_id)
	{

		$product = wc_get_product($post_id);
		$_logistra_robots_product_commodity_code = $_POST['_logistra_robots_product_commodity_code'];
		if (isset($_logistra_robots_product_commodity_code))
			$product->update_meta_data('_logistra_robots_product_commodity_code', esc_attr($_logistra_robots_product_commodity_code));

		$_logistra_robots_product_freight_description = $_POST['_logistra_robots_product_freight_description'];
		if (isset($_logistra_robots_product_freight_description))
			$product->update_meta_data('_logistra_robots_product_freight_description', esc_attr($_logistra_robots_product_freight_description));

		$_logistra_robots_product_country_of_origin = $_POST['_logistra_robots_product_country_of_origin'];
		if (isset($_logistra_robots_product_country_of_origin))
			$product->update_meta_data('_logistra_robots_product_country_of_origin', esc_attr($_logistra_robots_product_country_of_origin));


		$_logistra_robots_product_freight_warning_label_new = $_POST['_logistra_robots_product_freight_warning_label_new'];
		if (isset($_logistra_robots_product_freight_warning_label_new) && !empty($_logistra_robots_product_freight_warning_label_new)) {
			$tags = get_option("wildrobot_logistra_warning_label_tags", []);
			array_push($tags, $_logistra_robots_product_freight_warning_label_new);
			update_option("wildrobot_logistra_warning_label_tags", $tags);
		}

		$_logistra_robots_product_freight_warning_label = isset($_POST['_logistra_robots_product_freight_warning_label']) ? (array) $_POST['_logistra_robots_product_freight_warning_label'] : array();
		if (isset($_logistra_robots_product_freight_warning_label)) {
			$_logistra_robots_product_freight_warning_label = array_map('esc_attr', $_logistra_robots_product_freight_warning_label);
			$product->update_meta_data('_logistra_robots_product_freight_warning_label', $_logistra_robots_product_freight_warning_label);
		}
		$product->save();
	}

	public function wildrobot_logistra_woocommerce_wp_select_multiple($field)
	{
		global $thepostid, $post;
		$product = wc_get_product($thepostid);

		$thepostid              = empty($thepostid) ? $post->ID : $thepostid;
		$field['class']         = isset($field['class']) ? $field['class'] : 'select short';
		$field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
		$field['name']          = isset($field['name']) ? $field['name'] : $field['id'];
		$field['value']         = isset($field['value']) ? $field['value'] : ($product->get_meta($field['id'], true) ? $product->get_meta($field['id'], true) : array());

		echo '<p class="form-field ' . esc_attr($field['id']) . '_field ' . esc_attr($field['wrapper_class']) . '"><label for="' . esc_attr($field['id']) . '">' . wp_kses_post($field['label']) . '</label><select id="' . esc_attr($field['id']) . '" name="' . esc_attr($field['name']) . '" class="' . esc_attr($field['class']) . '" multiple="multiple">';

		foreach ($field['options'] as $key => $value) {

			echo '<option value="' . esc_attr($key) . '" ' . (in_array($key, $field['value']) ? 'selected="selected"' : '') . '>' . esc_html($value) . '</option>';
		}

		echo '</select> ';

		if (!empty($field['description'])) {

			if (isset($field['desc_tip']) && false !== $field['desc_tip']) {
				echo '<img class="help_tip" data-tip="' . esc_attr($field['description']) . '" src="' . esc_url(WC()->plugin_url()) . '/assets/images/help.png" height="16" width="16" />';
			} else {
				echo '<span class="description">' . wp_kses_post($field['description']) . '</span>';
			}
		}
		echo '</p>';
	}

	public static function get_dhl_electronic_invoice_data($orderId)
	{
		$order = wc_get_order($orderId);

		$items = $order->get_items();

		$weight_unit = get_option('woocommerce_weight_unit');
		$warning_label_tags = get_option("wildrobot_logistra_warning_label_tags", []);

		$data = [];
		$errors = [];
		$warnings = [];
		foreach ($items as $item) {
			$product = wc_get_product($item['product_id']);
			$type = $product->get_type();
			if ($type == "variation") {
				$productId = $product->get_parent_id();
			} else if ($type == "bundle") {
				continue;
			} else {
				$productId = $product->get_id();
			}
			$productName = $product->get_name();
			$productIdentifier = $productName . "(" . $productId . ")";

			// commodity_code
			$commodity_code = $product->get_meta('_logistra_robots_product_commodity_code', true);
			if (empty($commodity_code)) {
				array_push($errors, $productIdentifier . " mangler varekode");
			}
			// country_of_origin
			$country_of_origin = $product->get_meta('_logistra_robots_product_country_of_origin', true);
			if (empty($country_of_origin)) {
				array_push($errors, $productIdentifier . " mangler opprinnelsesland");
			}
			// freight_description
			$freight_description = $product->get_meta('_logistra_robots_product_freight_description', true);
			if ($type == "variation") {
				$parentProduct = wc_get_product($productId);
				$original_parent_product_description = $parentProduct->get_short_description() ? $parentProduct->get_short_description() : $parentProduct->get_description();
				$product_description_for_export = empty($freight_description) ? $original_parent_product_description  : $freight_description;
				if (empty($product_description_for_export)) {
					array_push($errors, $productIdentifier . " mangler varebeskrivelse");
				}
			} else {
				$original_product_description = $product->get_short_description() ? $product->get_short_description() : $product->get_description();
				$product_description_for_export = empty($freight_description) ? $original_product_description  : $freight_description;
				if (empty($product_description_for_export)) {
					array_push($errors, $productIdentifier . " mangler varebeskrivelse");
				}
			}

			// freight_warning_label
			$product = wc_get_product($productId);
			$freight_warning_label = $product->get_meta('_logistra_robots_product_freight_warning_label', true);
			if (!empty($freight_warning_label)) {
				foreach ($freight_warning_label as $key => $value) {
					$index = intval($value);
					if (isset($warning_label_tags[$index]) && !empty($warning_label_tags[$index])) {
						array_push($warnings, $warning_label_tags[$index]);
					}
				}
			}
			$quantity = $item['quantity'];
			$unit_value = intval($item->get_total()) / intval($item['quantity']);
			$weight =  (!empty($product->get_weight()) ? $product->get_weight() : 0) * $item['quantity'];
			if (empty($weight)) {
				array_push($errors, $productIdentifier . " mangler vekt");
			} else {
				if ($weight_unit !== "kg") {
					$weight = wc_get_weight($weight, "kg", $weight_unit);
				}
			}

			$trimmed_product_description = trim(preg_replace('/ +/', ' ', preg_replace('/[^A-Za-z0-9 ]/', ' ', urldecode(html_entity_decode(strip_tags($product_description_for_export))))));
			array_push($data, [
				"description" => apply_filters("logistra_robots_dhl_electronic_invoice_description", $trimmed_product_description, $orderId, $productId),
				"commodity_code" => apply_filters("logistra_robots_dhl_electronic_invoice_commodity_code", $commodity_code, $orderId, $productId),
				"quantity" => apply_filters("logistra_robots_dhl_electronic_invoice_quantity", $quantity, $orderId, $productId),
				"unit_value" => apply_filters("logistra_robots_dhl_electronic_invoice_unit_value", $unit_value, $orderId, $productId),
				"sub_total_value" => apply_filters("logistra_robots_dhl_electronic_invoice_sub_total_value", ($unit_value * $quantity), $orderId, $productId),
				"net_weight" => apply_filters("logistra_robots_dhl_electronic_invoice_net_weight", round($weight, 3, PHP_ROUND_HALF_DOWN), $orderId, $productId),
				"gross_weight" => apply_filters("logistra_robots_dhl_electronic_invoice_gross_weight", $weight, $orderId, $productId),
				"country_of_origin" => apply_filters("logistra_robots_dhl_electronic_invoice_country_of_origin", $country_of_origin, $orderId, $productId),
			]);
		}

		$freight_cost = apply_filters("logistra_robots_dhl_electronic_invoice_freight_cost", $order->get_shipping_total(), $orderId);
		$terms_of_payment = apply_filters("logistra_robots_dhl_electronic_invoice_terms_of_payment", $order->get_payment_method_title(), $orderId);
		$type_of_invoice = apply_filters("logistra_robots_dhl_electronic_invoice_type_of_invoice", "commercial", $orderId);
		$billing_eori = $order->get_meta('_billing_eori', true);
		$billed_to_name = $order->get_formatted_billing_full_name();
		$billed_to_address = $order->get_billing_address_1();
		$billed_to_address2 = $order->get_billing_address_2();
		$billed_to_country = $order->get_billing_country();
		$billed_to_postcode = $order->get_billing_postcode();
		$billed_to_city = $order->get_billing_city();
		$billed_to_phone = $order->get_billing_phone();
		$consignor_eori_no = !empty($billing_eori) ? $billing_eori : "";


		if (!empty($errors)) {
			throw new Exception("DHL Elektronisk faktura krever følgende innstillinger på produktet som skal leveres: " . implode(", ", $errors));
		}
		return [
			"data" => $data,
			"warnings" => array_unique($warnings),
			"freight_cost" => $freight_cost,
			"terms_of_payment" => $terms_of_payment,
			"type_of_invoice" => $type_of_invoice,
			"billed_to_name" => $billed_to_name,
			"billed_to_address1" => $billed_to_address,
			"billed_to_address2" => $billed_to_address2,
			"billed_to_country" => $billed_to_country,
			"billed_to_postcode" => $billed_to_postcode,
			"billed_to_city" => $billed_to_city,
			"billed_to_phone" => $billed_to_phone,
			"consignor_eori_no" => $consignor_eori_no,
			"currency_code" => get_woocommerce_currency()
		];
	}
}
