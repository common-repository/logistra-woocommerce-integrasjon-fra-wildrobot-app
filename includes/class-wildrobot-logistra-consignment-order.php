<?php

class Wildrobot_Logistra_Consignment_Order
{
	public static function get_consignment_args_from_order_id($order_id, $throw_on_empty_delivery_relation = true)
	{
		$order = wc_get_order($order_id);
		$order_number = $order->get_order_number();
		$shipping_methods = $order->get_shipping_methods();

		// Get order values
		$weight = Wildrobot_Logistra_Order_Utils::get_weight_for_order(
			$order
		);
		$country = self::country($order);
		$postcode = self::postcode($order);
		$city = self::city($order);
		$transport_description = self::transport_description($order);
		$packages = self::packages($order, $transport_description, $weight);
		// need args
		$carrier_message = self::carrier_message($order);
		$consignee_message = self::consignee_message($order);
		$consignee = self::consignee($order);

		// iterate over shipping methods
		$consignment_args = [];
		foreach ($shipping_methods as $shipping_method) {
			$delivery_relation = Wildrobot_Logistra_DB::get_delivery_relation($shipping_method);
			// Check for fallback if not found
			if (empty($delivery_relation["wr_id"])) {
				if (get_option('wildrobot_logistra_fallback_freight_product') === "yes") {
					$delivery_relation = Wildrobot_Logistra_DB::get_delivery_relation("fallbackFreightProduct:0");
					if (empty($delivery_relation) && $throw_on_empty_delivery_relation) {
						throw new Exception('Fant ikke levering innstillinger for tilbakefallende fraktmetode, kontroller innstillinger på tilbakefallende fraktmetode.');
					}
				} else if ($throw_on_empty_delivery_relation) {
					throw new Exception('Fant ikke leverings innstillinger for fraktmetode ' . $shipping_method->get_method_title() . ". Tilbakefallende levering er ikke skrudd på.");
				}
			}
			// If no delivery relation found, return with basic data if allowed.
			if (empty($delivery_relation["wr_id"]) && !$throw_on_empty_delivery_relation) {
				$consignment_arg = [
					"order_id" => $order_id,
					"order_number" => $order_number,
					"weight" => $weight,
					"country" => $country,
					"postcode" => $postcode,
					"city" => $city,
					"transport_description" => $transport_description,
					"packages" => $packages,
					"carrier_message" => $carrier_message,
					"consignee_message" => $consignee_message,
					"consignee" => $consignee,
					"is_return_label" => false,
				];
				array_push($consignment_args, $consignment_arg);
				continue;
			}
			$choosen_service_point_id = $order->get_meta('_shipping_service_partner', true);
			$order_amounts = self::get_order_amounts($order);
			$customs_value = self::customs_value($order, $order_amounts, $delivery_relation["terms_of_delivery_customer_number"]);

			$electronic_invoice = self::electronic_invoice($order, $delivery_relation["services"], $delivery_relation["export_reason"], $delivery_relation["export_type"]);
			if (!empty($electronic_invoice["other_remarks"])) {
				$transport_description = $electronic_invoice["other_remarks"];
				// We need to recalculate packages because of change in transport description
				$packages = self::packages($order, $transport_description, $weight);
			}

			$consignment_arg =  [
				"wr_id" =>  $delivery_relation["wr_id"],
				"carrier" => $delivery_relation["carrier"],
				"services" => $delivery_relation["services"],
				"printer" => $delivery_relation["printer"],
				"print_time" => $delivery_relation["print_time"],
				"transfer_time" => $delivery_relation["transfer_time"],
				"terms_of_delivery_code" => $delivery_relation["terms_of_delivery_code"],
				"terms_of_delivery_name" => $delivery_relation["terms_of_delivery_name"],
				"terms_of_delivery_customer_number" => $delivery_relation["terms_of_delivery_customer_number"],
				"export_reason" => $delivery_relation["export_reason"],
				"export_type" => $delivery_relation["export_type"],
				"bring_priority" => $delivery_relation["bring_priority"],
				"choosen_service_point_id" => $choosen_service_point_id,
				"order_id" => $order_id,
				"order_number" => $order_number,
				"weight" => $weight,
				"country" => $country,
				"postcode" => $postcode,
				"city" => $city,
				"transport_description" => $transport_description,
				"packages" => $packages,
				"carrier_message" => $carrier_message,
				"consignee_message" => $consignee_message,
				"consignee" => $consignee,
				"customs_value" => $customs_value,
				"electronic_invoice" => $electronic_invoice,
				"is_return_label" => false,
				"currency" => $order_amounts["currency"],
				"total_with_tax" => $order_amounts["total_with_tax"],
				"total_without_tax" => $order_amounts["total_without_tax"],
			];
			array_push($consignment_args, $consignment_arg);

			// Check if we need a return label and add it to the consignment
			foreach ($delivery_relation["services"] as $service) {
				if ($service == "wildrobot_automatic_print_return_label" || $service == "wildrobot_automatic_email_return_label") {
					$is_return_email = $service === "wildrobot_automatic_email_return_label";
					$print_return_delivery_relation = Wildrobot_Logistra_DB::get_delivery_relation($shipping_method, true);
					if (empty($print_return_delivery_relation)) {
						// maybe its fallback
						if (get_option('wildrobot_logistra_fallback_freight_product') === "yes") {
							$print_return_delivery_relation = Wildrobot_Logistra_DB::get_delivery_relation("fallbackFreightProduct:0", true);
							if (empty($print_return_delivery_relation)) {
								throw new Exception($shipping_method->get_method_title() . " har automatisk retur etikett. Men ikke gyldig retur etikett innstillinger.");
							}
						}
					}
					// Settings overrides args here. So we dont send in something that should be overran on the delivery that takes effect on return delivery.
					$print_return_order_args =  [
						"wr_id" => $print_return_delivery_relation["wr_id"],
						"carrier" => $print_return_delivery_relation["carrier"],
						"services" => $print_return_delivery_relation["services"],
						"print_time" => $is_return_email ? "" : $print_return_delivery_relation["print_time"],
						"printer" => $print_return_delivery_relation["printer"],
						"transfer_time" => $print_return_delivery_relation["transfer_time"],
						"terms_of_delivery_code" => $print_return_delivery_relation["terms_of_delivery_code"],
						"terms_of_delivery_name" => $print_return_delivery_relation["terms_of_delivery_name"],
						"terms_of_delivery_customer_number" => $print_return_delivery_relation["terms_of_delivery_customer_number"],
						"export_reason" => $print_return_delivery_relation["export_reason"],
						"export_type" => $print_return_delivery_relation["export_type"],
						"bring_priority" => $print_return_delivery_relation["bring_priority"],
						"service_point_id" => $choosen_service_point_id,
						"email_return_label_to_consignee" => $is_return_email,
						"is_return_label" => true,
						"order_id" => $order_id,
						"order_number" => $order_number,
						"weight" => $weight,
						"country" => $country,
						"postcode" => $postcode,
						"city" => $city,
						"transport_description" => $transport_description,
						"packages" => $packages,
						"carrier_message" => $carrier_message,
						"consignee_message" => $consignee_message,
						"consignee" => $consignee,
						"customs_value" => $customs_value,
						"electronic_invoice" => $electronic_invoice,
						"currency" => $order_amounts["currency"],
						"total_with_tax" => $order_amounts["total_with_tax"],
						"total_without_tax" => $order_amounts["total_without_tax"],
					];
					array_push($consignment_args, $print_return_order_args);
				}
			}
		}
		if (empty($consignment_args) && !$throw_on_empty_delivery_relation) {
			$consignment_arg = [
				"order_id" => $order_id,
				"order_number" => $order_number,
				"weight" => $weight,
				"country" => $country,
				"postcode" => $postcode,
				"city" => $city,
				"transport_description" => $transport_description,
				"packages" => $packages,
				"carrier_message" => $carrier_message,
				"consignee_message" => $consignee_message,
				"consignee" => $consignee,
				"is_return_label" => false,
			];
			array_push($consignment_args, $consignment_arg);
		}
		if (empty($consignment_args)) {
			throw new Exception("Fant ingen leveringsinnstillinger for ordren.");
		}
		return $consignment_args;
	}

	private static function packages($order, $transport_description, $weight)
	{
		$packages = [];
		$counter = 0;
		if (get_option('wildrobot_logistra_calculate_dimensions') === "yes" || get_option('wildrobot_logistra_calculate_volume') === "yes") {
			$dimension_result = Wildrobot_Logistra_Order_Utils::get_dimensions_from_order($order);
			$packages[$counter]["length"] = $dimension_result["length"];
			$packages[$counter]["height"] = $dimension_result["height"];
			$packages[$counter]["width"] = $dimension_result["width"];
			$packages[$counter]["volume"] = $dimension_result["volume"];
		}
		$packages[$counter]["amount"] = 1;
		$packages[$counter]["description"] = $transport_description;
		$packages[$counter]["type"] = "package";
		$packages[$counter]["weight"] = $weight;

		$counted_groups = [];
		$items = $order->get_items();
		$product_map = array_reduce($items, function ($carry, $item) {
			$pid = $item['product_id'];
			$variation_id = isset($item['variation_id']) && $item['variation_id'] > 0 ? $item['variation_id'] : null;
			$quantity = $item['quantity'];
			$carry[$pid] = [
				'quantity' => $quantity,
				'variation_id' => $variation_id
			];
			return $carry;
		}, []);
		foreach ($items as $item) {
			Wildrobot_Logistra_Order_Utils::calculate_package_amount($packages, $counter, $counted_groups, $item['product_id'], $item['quantity'], $product_map);
		}
		return $packages;
	}

	private static function customs_value($order, $order_amounts, $terms_of_delivery_customer_number)
	{

		return [
			'amount' => $order_amounts['total_without_tax'],
			'currency' => $order_amounts['currency'],
			'paid_by_custno' => $terms_of_delivery_customer_number
		];
	}

	private static function electronic_invoice($order, $services, $export_reason, $export_type)
	{
		$electronic_invoice = null;
		foreach ($services as $service) {
			// electronic invoice
			if ($service === "dhl_express_electronic_invoice") {
				$electronic_invoice_data = Wildrobot_Logistra_DHL::get_dhl_electronic_invoice_data($order);
				$electronic_invoice = [
					"reason_for_export" => $export_reason,
					"type_of_export" => $export_type,
					"other_remarks" => implode(", ", $electronic_invoice_data["warnings"]),
					"freight_cost" => $electronic_invoice_data["freight_cost"],
					"terms_of_payment" => $electronic_invoice_data["terms_of_payment"],
					"type_of_invoice" => $electronic_invoice_data["type_of_invoice"],
					"items" => $electronic_invoice_data["data"],
					"billed_to_name" => $electronic_invoice_data["billed_to_name"],
					"billed_to_address1" => $electronic_invoice_data["billed_to_address1"],
					"billed_to_address2" => $electronic_invoice_data["billed_to_address2"],
					"billed_to_country" => $electronic_invoice_data["billed_to_country"],
					"billed_to_city" => $electronic_invoice_data["billed_to_city"],
					"billed_to_postcode" => $electronic_invoice_data["billed_to_postcode"],
					"consignor_eori_no" => $electronic_invoice_data["consignor_eori_no"],
					"currency_code" => $electronic_invoice_data["currency_code"],
				];
			}
		}
		return $electronic_invoice;
	}

	private static function postcode($order)
	{
		$postcode = $order->get_shipping_postcode();
		if (empty($postcode)) {
			$postcode = $order->get_billing_postcode();
		}
		if (empty($postcode)) {
			$postcode = "";
		}
		return $postcode;
	}

	private static function city($order)
	{
		$city = $order->get_shipping_city();
		if (empty($city)) {
			$city = $order->get_billing_city();
		}
		if (empty($city)) {
			$city = "";
		}
		return $city;
	}

	private static function country($order)
	{
		$country = $order->get_shipping_country();
		if (empty($country)) {
			$country = $order->get_billing_country();
		}
		if (empty($country)) {
			$country = array_search($country, WC()->countries->get_countries());
			if (empty($country)) {
				$country = "NO";
			}
		}
		return $country;
	}

	private static function transport_description($order)
	{
		$transport_description = "";
		$items = $order->get_items();
		$use_product_names = get_option('wildrobot_logistra_consignment_description_product_names') === 'yes';

		if ($use_product_names) {
			$name_array = array_map(function ($product) {
				return $product->get_quantity() . 'x' . $product->get_name() . ',';
			}, $items);
			$transport_description = join(' ', $name_array);
		} else {
			$count = 0;
			$has_packages = false;

			foreach ($items as $item) {
				$product = wc_get_product($item['product_id']);
				$has_separate_package = $product->get_meta('_wildrobot_separate_package_for_product', true) === "yes";
				$package_amount = $product->get_meta('_wildrobot_package_amount', true);
				$count++;
				if ($has_separate_package || $package_amount > 0) {
					$has_packages = true;
				}
			}

			$description_text = $count > 1 ? "produkter" : "produkt";
			if ($has_packages) {
				$description_text .= ", flere kolli";
			}
			$transport_description = sprintf(__('%s %s', 'logistra-robots'), $count, $description_text);
		}

		return esc_html(substr($transport_description, 0, 200));
	}
	private static function carrier_message($order)
	{
		return esc_html($order->get_customer_note());
	}

	private static function consignee_message($order)
	{
		if (!empty(get_option("wildrobot_logistra_consignee_message"))) {
			return get_option("wildrobot_logistra_consignee_message");
		} else {
			return __('Avsender: ', 'wildrobot-logistra') . esc_html(get_bloginfo("name")) . ',' . __(' Mottaker: ', 'wildrobot-logistra') . esc_html($order->get_formatted_shipping_full_name());
		}
	}

	private static function consignee($order)
	{
		$country = !empty($order->get_shipping_country()) ? $order->get_shipping_country() : $order->get_billing_country();
		if (strlen($country) === 0) {
			$country = "NO";
		} else if (strlen($country) > 2) {
			$country = array_search($country, WC()->countries->get_countries());
			if (!empty($country)) {
				$country = "NO";
			}
		}
		$possible_shipping_email = $order->get_meta('shipping_email', true);
		$possible_shipping_phone = $order->get_meta('shipping_phone', true);
		$address1 = esc_html(!empty($order->get_shipping_address_1()) ? $order->get_shipping_address_1() : $order->get_billing_address_1());
		$postcode = !empty($order->get_shipping_postcode()) ? $order->get_shipping_postcode() : $order->get_billing_postcode();
		$name = self::parse_name_for_order($order);
		$city = !empty($order->get_shipping_city()) ? $order->get_shipping_city() : $order->get_billing_city();
		$phone = !empty($possible_shipping_phone) ? esc_html($possible_shipping_phone) : $order->get_billing_phone();
		$email = !empty($possible_shipping_email) ?  esc_html($possible_shipping_email) : esc_html($order->get_billing_email());
		$contact_person = esc_html(!empty($order->get_formatted_shipping_full_name()) ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name());
		$address2 = esc_html(!empty($order->get_shipping_address_2()) ? $order->get_shipping_address_2() : $order->get_billing_address_2());
		return [
			"name"     			=> 	 	$name,
			"address1" 			=>   	$address1,
			"country"  			=>   	$country,
			"postcode" 			=>   	$postcode,
			"city"     			=>   	$city,
			"phone"    			=>   	$phone,
			"mobile"   			=>   	$phone,
			"email"    			=>   	$email,
			"contact-person"    => 		$contact_person,
			"address2"    		=> 	 	$address2
		];
	}

	// Helpers
	private static function parse_name_for_order($order)
	{
		$has_shipping_name = !empty(str_replace(" ", "", $order->get_formatted_shipping_full_name()));
		$name = $has_shipping_name ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name();
		// Should we add company information
		if (get_option('wildrobot_logistra_add_org_name_to_order') == 'yes') {
			$org_name_from_order = $order->get_shipping_company() ? $order->get_shipping_company() : $order->get_billing_company();
			if ($org_name_from_order) {
				// removed Name
				$name = $org_name_from_order . " v/" . $name;
			} else {
				// try to get it from the user
				$org_name_from_customer = (get_user_meta($order->get_customer_id(), 'shipping_company', true))
					? get_user_meta($order->get_customer_id(), 'shipping_company', true)
					: get_user_meta($order->get_customer_id(), 'billing_company', true);
				if ($org_name_from_customer) {
					$name = $org_name_from_customer  . " v/" . $name;
				}
			}
		}
		return esc_html($name);
	}

	private static function get_order_amounts($order)
	{
		if (empty($order)) {
			return [
				"total" => 0,
				"currency" => get_woocommerce_currency()
			];
		} else {
			$tax = $order->get_total_tax();
			$amount = $order->get_total();
			$currency = $order->get_currency();
			return [
				"total_with_tax" => $amount,
				"total_without_tax" =>  $amount - $tax,
				"currency" => strtolower($currency)
			];
		}
	}
}
