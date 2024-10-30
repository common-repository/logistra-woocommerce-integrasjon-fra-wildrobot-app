<?php

class Wildrobot_Logistra_Consignment
{

	// changeable 
	private $printer;
	private $services;
	private $customs_value;
	private $electronic_invoice;
	private $order_id;
	private $order_number;
	private $packages;
	private $is_return_label;
	private $return_address;
	private $print_without_api;
	private $estimate;
	private $email_return_label_to_consignee;
	private $email_notification_to_consignee;
	private $booking_request;
	private $consignee_message;
	private $carrier_message;
	private $dhl_express_pickup;
	private $consignee;
	private $bring_priority;
	private $transport_description;
	private $carrier;
	private $weight;
	private $amount;
	private $country;
	private $city;
	private $postcode;
	private $print_time;
	private $print_interval;
	private $transfer_interval;
	private $transfer_time;
	private $terms_of_delivery_customer_number;
	private $currency;
	private $total_with_tax;
	private $total_without_tax;
	private $hash;

	// generated from terms_of_delivery_code
	private $terms_of_delivery;

	//conditonal arguments
	private $terms_of_delivery_code; // only changeable when $terms_of_delivery is NOT defined
	private $export_reason; // only changeable when $electronic_invoice is NOT defined
	private $export_type; // only changeable when $electronic_invoice is NOT defined

	// Change indicators
	private $choosen_service_point_id;
	private $wr_id;


	// meta info, not changeable
	private $transfer_now;
	private $print_timestamp;
	private $transfer_timestamp;
	private $service_partner_match = null; // Has no default if no service partner is found
	private $service_partner_data = null; // Has no default if no service partner is found
	private $transport_agreeement = null;  // Has no default if wr_id is not set
	private $transport_agreement_id = null; // Has no default if wr_id is not set
	private $product = null; // Has no default if wr_id is not set

	// local variables
	private $errors = [];
	private $warnings = [];
	private $messages = [];

	public function __construct($args)
	{
		// ensure variables
		$args = wp_parse_args($args, [
			"return_address" => [],
			"transport_description" => "",
			"print_without_api" => false,
			"estimate" => true,
			"email_return_label_to_consignee" => false,
			"email_notification_to_consignee" => false,
			"booking_request" => false,
			"carrier" => "",
			"order_id" => "",
			"order_number" => "",
			"is_return_label" => false,
			"wr_id" => "",
			"bring_priority" => false,
			"terms_of_delivery_code" => "", // terms_of_delivery_code is used when terms_of_delivery is not defined, can only be constructor argument
			"terms_of_delivery" => [],
			"dhl_express_pickup" => [],
			"printer" => "",
			"weight" => 0,
			"packages" => [],
			"amount" => 1,
			"electronic_invoice" => [],
			"export_reason" => "",
			"export_type" =>  "",
			"choosen_service_point_id" => "",
			"city" => "",
			"consignee" => [],
			"carrier_message" => "",
			"consignee_message" => "",
			"print_interval" => "",
			"services" => [],
			"country" => "",
			"postcode" => "",
			"print_time" => "",
			"product" => "",
			"transfer_interval" => "",
			"transfer_time" => "",
			"transport_agreeement" => null,
			"transport_agreement_id" => "",
			"total_amount" => 0,
			"terms_of_delivery_customer_number" => "",
			"currency" => "",
			"total_with_tax" => "",
			"total_without_tax" => "",
			"customs_value" => []
		]);
		// Just because we often pass it to filters
		$order_id = $args["order_id"];
		// set defaults
		$args["transport_description"] = self::transport_description($args["transport_description"], $order_id);
		$args["return_address"] = self::return_address($args["return_address"], $order_id);
		$args["dhl_express_pickup"] = self::dhl_express_pickup($args["dhl_express_pickup"], $order_id);
		$args["printer"] = self::printer($args["printer"], $order_id);
		$args["weight"] = self::weight($args["weight"], $order_id);
		$args["packages"] = self::packages($args["packages"], $args["amount"], $args["transport_description"], $args["weight"], $order_id);
		$args["terms_of_delivery"] = self::terms_of_delivery($args["terms_of_delivery"], $args["terms_of_delivery_code"], $args["country"], $args["postcode"], $args["city"], $order_id);
		unset($args["terms_of_delivery_code"]); // maybe not needed
		$args["electronic_invoice"] = self::electronic_invoice($args["electronic_invoice"], $args["export_reason"], $args["export_type"], $order_id);
		$args["carrier_message"] = self::carrier_message($args["carrier_message"], $order_id);
		$args["consignee_message"] = self::consignee_message($args["consignee_message"], $order_id);
		$args["consignee"] = self::consignee($args["consignee"], $order_id);
		$args["print_timestamp"] = self::print_time($args["print_interval"], $args["print_time"],  $order_id);
		$transfer_response = self::transfer_time($args["transfer_interval"], $args["transfer_time"], $args["print_timestamp"], $order_id);
		$args["transfer_timestamp"] = $transfer_response["transfer_timestamp"];
		$args["transfer_now"] = $transfer_response["transfer_now"];
		$args["customs_value"] = $this->customs_value($args["customs_value"], $order_id, $args["total_amount"], $args["currency"], $args["terms_of_delivery_customer_number"]);



		// Start setting values
		if (!empty($args["wr_id"])) {
			$wr_data = Wildrobot_Logistra_DB::get_transport_product_and_agreement_id($args["wr_id"]);
			$this->transport_agreeement = Wildrobot_Logistra_DB::get_transport_agreement_for_logistra_identifier($args["wr_id"]);
			$this->transport_agreement_id = $wr_data["transport_agreement_id"];
			$this->product =  $wr_data["product"];
			if ($this->transport_agreeement) {
				$args['carrier'] = $this->transport_agreeement["ta_carrier"]["identifier"];
			}
		}

		try {
			if ($this->transport_agreeement["service_partner_possible"]) {
				$service_partner_response = self::service_partner($args["postcode"], $args["country"], $args["carrier"], $args["wr_id"], $args["choosen_service_point_id"], $order_id, $args["consignee"]["address1"]);
				$this->service_partner_match = $service_partner_response["match"];
				$this->service_partner_data = $service_partner_response["data"];
			}
		} catch (\Throwable $error) {
			if ($this->transport_agreeement["requires_service_partner"]) {
				$this->add_error("Transporten krever utleveringssted. " . $error->getMessage());
			} else {
				$this->add_warning("Transporten krever ikke utleveringssted. " . $error->getMessage());
			}
		}

		// copy paste from args
		$this->carrier = $args['carrier'];
		$this->bring_priority = $args['bring_priority'];
		$this->amount = $args['amount'];

		// conditional
		if (empty($args["electronic_invoice"])) {
			$this->export_reason = $args['export_reason'] ?? "";
			$this->export_type = $args['export_type'] ?? "";
		} else {
			$this->export_reason = "";
			$this->export_type = "";
		}

		if (empty($args["terms_of_delivery_code"])) {
			$this->terms_of_delivery_code = $args['terms_of_delivery_code'] ?? "";
		} else {
			$this->terms_of_delivery_code = "";
		}

		$this->order_id = $args['order_id'] ?? "";
		$this->order_number = $args['order_number'] ?? "";
		$this->transport_description = $args['transport_description'];
		$this->weight = $args['weight'];
		$this->dhl_express_pickup = $args['dhl_express_pickup'];
		$this->consignee = $args['consignee'];
		$this->wr_id = $args['wr_id'];
		$this->is_return_label = $args['is_return_label'];
		$this->return_address = $args['return_address'];
		$this->print_without_api = $args['print_without_api'];
		$this->estimate = $args['estimate'];
		$this->email_return_label_to_consignee = $args['email_return_label_to_consignee'];
		$this->email_notification_to_consignee = $args['email_notification_to_consignee'];
		$this->booking_request = $args['booking_request'];
		$this->consignee_message = $args['consignee_message'];
		$this->carrier_message = $args['carrier_message'];
		$this->print_time = $args['print_time'];
		$this->print_interval = $args['print_interval'] ?? "";
		$this->print_timestamp = $args['print_timestamp'];
		$this->transfer_time = $args['transfer_time'];
		$this->transfer_now = $args['transfer_now'];
		$this->transfer_interval = $args['transfer_interval'];
		$this->transfer_timestamp = $args['transfer_timestamp'];
		$this->services = $args['services'];
		$this->printer = $args['printer'];
		$this->country = $args['country'];
		$this->city = $args['city'];
		$this->postcode = $args['postcode'];
		$this->terms_of_delivery = $args['terms_of_delivery'];
		$this->customs_value = $args['customs_value'];
		$this->electronic_invoice = $args['electronic_invoice'];
		$this->packages = $args['packages'];
		$this->choosen_service_point_id = $args['choosen_service_point_id'];
		$this->terms_of_delivery_customer_number = $args['terms_of_delivery_customer_number'];
		$this->currency = $args['currency'];
		$this->total_with_tax = $args['total_with_tax'];
		$this->total_without_tax = $args['total_without_tax'];
		$this->hash = md5(serialize($this->to_args()));
	}

	private function normalize_weight_and_dimensions()
	{
		$dimenson_unit = get_option('woocommerce_dimension_unit');
		$weight_unit = get_option('woocommerce_weight_unit');
		$packages_as_attributes = [];
		foreach ($this->packages as $key => &$package) {
			if ($dimenson_unit !== "cm") {
				if (!empty($package['height'])) {
					$package['height'] = wc_get_dimension($package["height"], "cm", $dimenson_unit);
				}
				if (!empty($package['length'])) {
					$package['length'] = wc_get_dimension($package["length"], "cm", $dimenson_unit);
				}
				if (!empty($package['width'])) {
					$package['width'] = wc_get_dimension($package["width"], "cm", $dimenson_unit);
				}
				if (!empty($package['volume'])) {
					if (empty($package["height"]) || empty($package["length"]) || empty($package["width"])) {
						throw new Exception("Cannot convert volume to freight required dimension with without width, height or length.");
					}
					// calculate volume as centimeters
					$package['volume'] = $package['height'] * $package['length'] * $package['width'];
				}
			}
			if ($weight_unit !== "kg") {
				if (!empty($package['weight'])) {
					$package['weight'] = wc_get_weight($package['weight'], "kg", $weight_unit);
				}
			}
			// remove dimensions if it should not be calculated by Logistra.
			if ($package["override_dimensions"] !== true && get_option('wildrobot_logistra_calculate_dimensions') !== "yes") {
				unset($package["length"]);
				unset($package["width"]);
				unset($package["height"]);
			}
			// remove volume if it should not be calculated by Logistra.
			if ($package["override_volume"] !== true && get_option('wildrobot_logistra_calculate_volume') !== "yes") {
				unset($package["volume"]);
			}
			// format values as strings
			if (isset($package['length'])) {
				$package['length'] = strval($package['length']);
			}
			if (isset($package['width'])) {
				$package['width'] = strval($package['width']);
			}
			if (isset($package['height'])) {
				$package['height'] = strval($package['height']);
			}
			if (isset($package['weight'])) {
				$package['weight'] = strval($package['weight']);
			}
			if (isset($package['volume'])) {
				$package['volume'] = round($package['volume'] / 1000, 3, PHP_ROUND_HALF_DOWN); // convert to dm3 for Logistra
				$package['volume'] = strval($package['volume']);
			}
			unset($package["override_dimensions"]);
			unset($package["override_volume"]);
			unset($package["override_weight"]);
			array_push($packages_as_attributes, [
				'_attributes' => $package
			]);
		}
		unset($package);
		$this->packages = $packages_as_attributes;
	}


	private function customs_value($choosen_customs_value, $order_id = null, $total_amount = 0, $currency = null, $terms_of_delivery_customer_number = "")
	{
		if (!empty($choosen_customs_value)) {
			$customs_value = $choosen_customs_value;
		} else {
			$customs_value =  [
				'amount' =>  $total_amount,
				'currency' => !empty($currency) ? $currency : strtolower(get_woocommerce_currency()),
				'paid_by_custno' => $terms_of_delivery_customer_number
			];
		}
		return apply_filters('wildrobot_logistra_consignment_customs_value', $customs_value, $order_id);
	}
	private function return_address($choosen_return_address, $order_id = null)
	{
		if (!empty($choosen_return_address)) {
			$return_address = $choosen_return_address;
		} else {
			$name = esc_html(get_option('wildrobot_logistra_return_address_name'));
			if (empty($name)) {
				$name = get_bloginfo("name");
			}
			$return_address =  [
				"name"   => apply_filters('logistra_robots_parts_return_address_name', $name),
				"address1"     =>  esc_html(get_option('wildrobot_logistra_return_address_address1')),
				"address2" => esc_html(get_option('wildrobot_logistra_return_address_address2')),
				"postcode"  => esc_html(get_option('wildrobot_logistra_return_address_postcode')),
				"city" => esc_html(get_option('wildrobot_logistra_return_address_city')),
				"country"     => esc_html(get_option('wildrobot_logistra_return_address_country')),
				"phone" => esc_html(get_option('wildrobot_logistra_return_address_phone')),
				"mobile" => esc_html(get_option('wildrobot_logistra_return_address_mobile')),
			];
		}
		return apply_filters('wildrobot_logistra_consignment_return_address', $return_address, $order_id);
	}

	private function transport_description($choosen_transport_description, $order_id = null)
	{
		if ($choosen_transport_description) {
			$transport_description = $choosen_transport_description;
		} else {
			$transport_description = __('Varer fra e-handel', 'wildrobot-logistra');
		}
		return apply_filters('wildrobot_logistra_consignment_transport_description', esc_html($transport_description), $order_id);
	}

	private function dhl_express_pickup($choosen_dhl_express_pickup, $order_id = null)
	{
		if (!empty($choosen_dhl_express_pickup)) {
			$dhl_express_pickup = $choosen_dhl_express_pickup;
		} else {
			$dhl_express_pickup = [
				'id' => "dhl_express_pickup",
				'pickup_time' => "14:00",
				'pickup_location' =>  "",
				'pickup_location_close_time' =>  "16:00",
				'pickup_instruction' =>  "",
			];
		}
		return apply_filters('wildrobot_logistra_consignment_dhl_express_pickup', $dhl_express_pickup, $order_id);
	}

	private function printer($choosen_printer, $order_id = null)
	{
		/* 
        1. if user has set an printer, use that.
        2. If choosen_printer is set, use that. It has either been set by freightmethod or override
        4. else choose default printer
		*/
		$printer = "";
		// 1
		$user_printer = get_user_option("wildrobot_logistra_user_printer");
		if (!empty($user_printer)) {
			// 2
			$printer = $user_printer;
		} else {
			if (!empty($choosen_printer)) {
				$printer = $choosen_printer;
			} else {
				// 3
				$printer = get_option("wildrobot_logistra_printer_default");
			}
		}
		return apply_filters("wildrobot_logistra_consignment_printer", $printer, $order_id);
	}

	private function weight($choosen_weight, $order_id = null)
	{
		$weight = 0;
		if (!empty($choosen_weight)) {
			$weight = $choosen_weight;
		} else if (get_option('wildrobot_logistra_static_weight_on_orders') === 'yes' && get_option('wildrobot_logistra_static_weight_amount', 0) !== 0) {
			$weight = get_option('wildrobot_logistra_static_weight_amount', 0);
		}
		return apply_filters('wildrobot_logistra_consignment_weight', $weight, $order_id);
	}

	private function packages($choosen_packages, $amount, $transport_description, $weight, $order_id = null)
	{
		$packages = [];
		$has_packages = !empty($choosen_packages) && count($choosen_packages) > 0;
		if ($has_packages) {
			foreach ($choosen_packages as $key => $package) {
				$has_dimensions = !empty($package["length"]) && !empty($package["height"]) && !empty($package["width"]);
				if ($has_dimensions) {
					$dimensions = [
						"length" => intval($package["length"]),
						"height" => intval($package["height"]),
						"width" => intval($package["width"]),
					];
					if (empty($package["volume"])) {
						$packages[$key]["volume"] =  Wildrobot_Logistra_Order_Utils::calculate_volume($dimensions["length"], $dimensions["height"], $dimensions["width"]);
					} else {
						$packages[$key]["volume"] = intval($package["volume"]);
					}
					$packages[$key]["length"] = $dimensions["length"];
					$packages[$key]["height"] = $dimensions["height"];
					$packages[$key]["width"] = $dimensions["width"];
				}
				$packages[$key]["amount"] =  !empty($package["amount"]) ? (string) $package["amount"]  : "1";
				$packages[$key]["description"] = !empty($package["description"]) ? $package["description"] : $transport_description;
				$packages[$key]["type"] = !empty($package["type"]) ? $package["type"] : "package";
				$packages[$key]["weight"] = ($package["override_weight"] ?? false) == true || !empty($package["weight"]) ? $package["weight"] ?? 0 : round($weight / count($choosen_packages), 3, PHP_ROUND_HALF_DOWN);
				$packages[$key]["override_dimensions"] = $package["override_dimensions"] ?? false;
				$packages[$key]["override_volume"] = $package["override_volume"] ?? false;
				$packages[$key]["override_weight"] = $package["override_weight"] ?? false;
			}
		} else {
			$packages[0]["amount"] = !empty($amount) ? $amount : 1;
			$packages[0]["description"] = $transport_description;
			$packages[0]["type"] = "package";
			$packages[0]["weight"] = $weight;
		}
		return apply_filters('wildrobot_logistra_consignment_packages', $packages, $order_id);
	}


	private function terms_of_delivery($choosen_terms_of_delivery, $choosen_terms_of_delivery_code, $country, $postcode, $city, $order_id = null)
	{
		if (!empty($choosen_terms_of_delivery)) {
			$terms_of_delivery = $choosen_terms_of_delivery;
		} else {
			$terms_of_delivery = [
				'code' => !empty($choosen_terms_of_delivery_code) ? $choosen_terms_of_delivery_code : "",
				'country' => $country,
				'postcode' => $postcode,
				'city' => $city,
			];
		}

		return apply_filters('wildrobot_logistra_consignment_terms_of_delivery', $terms_of_delivery, $order_id);
	}


	private function electronic_invoice($choosen_electronic_invoice, $choosen_export_reason, $choosen_export_type, $order_id = null)
	{
		if (!empty($choosen_electronic_invoice)) {
			$electronic_invoice = $choosen_electronic_invoice;
		} else {
			$electronic_invoice = [
				"reason_for_export" => $choosen_export_reason,
				"type_of_export" => $choosen_export_type,
				"other_remarks" => "",
				"freight_cost" => 0,
				"terms_of_payment" => "card",
				"type_of_invoice" => "commercial",
				"items" => [
					[
						"description" => "",
						"commodity_code" => "",
						"quantity" => 0,
						"unit_value" => 0,
						"sub_total_value" => 0,
						"net_weight" => 0,
						"gross_weight" => 0,
						"country_of_origin" => "",
					]
				],
			];
		}
		return apply_filters('wildrobot_logistra_consignment_electronic_invoice', $electronic_invoice, $order_id);
	}



	/**
	 * Product, postcode, country and carrier should be set before running this one.
	 *
	 * @param [type] $args
	 * @return void
	 */
	private function service_partner($postcode, $country, $carrier, $wr_id, $choosen_service_point_id, $order_id = null, $address = null)
	{

		// cant calculate service partner without these. Then check for required and go fallback.
		// if (empty($postcode) || empty($country) || empty($carrier)) {
		// 	// TODO: fallback to default service partner
		// } 
		// Only send with address with choosen_service_point_id is not set and address is set
		$send_with_address = empty($choosen_service_point_id) && !empty($address);
		$service_partner_data = Wildrobot_Logistra_Cargonizer::get_service_partners($country, $postcode, $carrier, $wr_id, $send_with_address ? $address : null);
		$bestMatch = $service_partner_data["service_partners"][0];
		if (!empty($choosen_service_point_id)) {
			$found_match = false;
			foreach ($service_partner_data["service_partners"] as  $servicePartner) {
				if (intval($choosen_service_point_id) == intval($servicePartner['number'])) {
					$bestMatch = $servicePartner;
					$found_match = true;
				}
			}
			if (!$found_match) {
				$settings = implode(", ", [$postcode, $country, $carrier, $choosen_service_point_id]);
				$this->add_error("Forespurt utleveringssted kunne ikke velges lenger. " . $settings);
			}
		}
		$service_partner_response = [
			"match" => $bestMatch,
			"data" => $service_partner_data
		];
		return apply_filters('wildrobot_logistra_consignment_service_partner', $service_partner_response, $order_id);
	}

	private function carrier_message($choosen_carrier_message, $order_id = null)
	{
		if (!empty($choosen_carrier_message)) {
			$message = $choosen_carrier_message;
		} else {
			$message = "";
		}
		return apply_filters("wildrobot_logistra_consignment_carrier_message", self::trim_customer_messages($message), $order_id);
	}

	private function consignee_message($choosen_consignee_message, $order_id = null)
	{
		if (!empty($choosen_consignee_message)) {
			$message = $choosen_consignee_message;
		} else if (!empty(get_option("wildrobot_logistra_consignee_message"))) {
			$message = get_option("wildrobot_logistra_consignee_message");
		} else {
			$message = __('Avsender: ', 'wildrobot-logistra') . esc_html(get_bloginfo("name"));
		}
		return apply_filters("wildrobot_logistra_consignment_consignee_message", self::trim_customer_messages($message), $order_id);
	}

	private function consignee($consignee, $order_id = null)
	{
		// Override flow
		// 1. args
		// 2. order
		// 3. empty
		$args = wp_parse_args($consignee, [
			"name"     => "",
			"address1" => "",
			"country"  => "",
			"postcode" => "",
			"city"     => "",
			"phone"    => "",
			"mobile"   => "",
			"email"    => "",
			"contact-person"    => "",
			"address2"    => "",
		]);
		// TODO - Convert filters to wildrobot_logistra_consignment_consignee_message with fallback to old filters.
		return [
			"name"     			=> apply_filters('logistra_robots_parts_cosignee_name', substr($args["name"], 0, 35), $order_id),
			"address1" 			=> apply_filters('logistra_robots_parts_cosignee_address1', $args["address1"], $order_id),
			"country"  			=> apply_filters('logistra_robots_parts_cosignee_country', $args["country"], $order_id),
			"postcode" 			=> apply_filters('logistra_robots_parts_cosignee_postcode', $args["postcode"], $order_id),
			"city"     			=> apply_filters('logistra_robots_parts_cosignee_city', $args["city"], $order_id),
			"phone"    			=> apply_filters('logistra_robots_parts_cosignee_phone', $args["phone"], $order_id),
			"mobile"   			=> apply_filters("logistra_robots_parts_cosignee_mobile", $args["phone"], $order_id),
			"email"    			=> apply_filters("logistra_robots_parts_cosignee_email", $args["email"], $order_id),
			"contact-person"    => apply_filters("logistra_robots_parts_cosignee_contact_person", $args["contact-person"], $order_id),
			"address2"    		=> apply_filters("logistra_robots_parts_cosignee_address2", $args["address2"], $order_id),
		];
	}

	private function print_time($choosen_print_interval, $choosen_print_time, $order_id = null)
	{
		if (empty($choosen_print_interval) && empty($choosen_print_time)) {
			$print_interval = get_option('wildrobot_logistra_print_interval');
			$print_time = get_option('wildrobot_logistra_print_interval_time', null);
		} else if (!empty($choosen_print_interval) && empty($choosen_print_time)) {
			if ($choosen_print_interval === "time") {
				$this->add_error("Kan ikke sette print interval til TID uten en spesifikk PRINT TID");
			}
			$print_interval = $choosen_print_interval;
			$print_time = null;
		} else if (empty($choosen_print_interval) && !empty($choosen_print_time)) {
			$print_interval = "time";
			$print_time = $choosen_print_time;
		} else if (!empty($choosen_print_interval) && !empty($choosen_print_time)) {
			$print_interval = $choosen_print_interval;
			$print_time = $choosen_print_time;
		} else {
			$this->add_error("Fant ikke innstillinger for print tid");
		}
		return apply_filters("wildrobot_logistra_consignment_print_time", self::get_print_time($print_interval, $print_time), $order_id);
	}

	private function transfer_time($choosen_transfer_interval, $choosen_transfer_time, $print_timestamp, $order_id = null)
	{
		if (empty($print_timestamp)) {
			$this->add_error("Print tid må settes før overførings tid");
		}
		$transfer_now = false;
		if (empty($choosen_transfer_interval) && empty($choosen_transfer_time)) {
			$transfer_interval = get_option('wildrobot_logistra_selected_transfer_method');
			$transfer_time = get_option('wildrobot_logistra_selected_transfer_time');
		} else if (!empty($choosen_transfer_interval) && empty($choosen_transfer_time)) {
			if ($choosen_transfer_interval === "time") {
				$this->add_error("Kan ikke sette overføring basert på TID uten et spesifikt overførings tidspunkt.");
			}
			$transfer_interval = $choosen_transfer_interval;
			$transfer_time = null;
		} else if (empty($choosen_transfer_interval) && !empty($choosen_transfer_time)) {
			$transfer_interval = "time";
			$transfer_time = $choosen_transfer_time;
		} else if (!empty($choosen_transfer_interval) && !empty($choosen_transfer_time)) {
			$transfer_interval = $choosen_transfer_interval;
			$transfer_time = $choosen_transfer_time;
		} else {
			$this->add_error("Fant ikke innstillinger for overførings tid");
		}
		if ($transfer_interval === "now") {
			$transfer_now = true;
		}
		return apply_filters("wildrobot_logistra_consignment_transfer_time", [
			"transfer_timestamp" => self::get_transfer_time($transfer_interval, $transfer_time, $print_timestamp),
			"transfer_now" =>  $transfer_now
		], $print_timestamp);
	}



	// Checks

	function check_required()
	{
		if (empty($this->packages)) throw new Exception("No packages");
		if (empty($this->transport_agreement_id)) throw new Exception("Fant ikke transport avtale");
	}

	// Transfers

	public function send_backend($args = [])
	{
		$args = wp_parse_args($args, [
			"title" => ""
		]);
		$this->normalize_weight_and_dimensions();
		$json =  [
			'consignment' => $this->to_json(),
			'print' => [
				'printerDefault' => $this->printer,
				'printTime' => $this->print_timestamp,
			],
			'transfer' => [
				'transferTime' =>   $this->transfer_timestamp,
				'auto' =>  $this->transfer_now,
			],
		];
		do_action('wildrobot_logistra_before_send_consignments', $json, $this->order_id);
		$shipment = Wildrobot_Logistra_Backend::create_consignments($json);

		/* no reponse */
		if ($shipment === null || $shipment === 404 || empty($shipment)) {
			// If there is no response, a consignment might still have been created. 
			// We should que an notification to admin to check that at a later time.
			// Schedule a recurring action
			$user = wp_get_current_user();
			$payload = array($this->order_id, $user->ID);
			if (!as_next_scheduled_action('wildrobot_check_order_no_consignment_response', $payload)) {
				// set this to 1 hour from now
				as_schedule_single_action(time() + 3600, "wildrobot_check_order_no_consignment_response", $payload, "wildrobot");
			}
			throw new Exception(__('Ingen kobling til fraktserver.', 'wildrobot-logistra'));
		}


		$new_line = '</br></br>';
		$order = null;
		if (!empty($this->order_id)) {
			$order = new WC_Order($this->order_id);
		}

		$delivery_type = wc_string_to_bool($this->is_return_label) ? __("Retur levering", "wildrobot-logistra") : __("Levering", "wildrobot-logistra");
		$orderNoteText = "";

		$orderNoteText .= empty($args["title"]) ? "<b>" . $delivery_type . " opprettet</b>" :  "<b>" . $args['title'] . "</b>";

		$orderNoteText .= $new_line;

		/* error */

		if (property_exists($shipment, 'error')) {
			if (is_string($shipment->error)) {
				$orderNoteText .=  substr($shipment->error, 0, 80) . '</br>';
				array_push($this->messages, substr($shipment->error, 0, 80));
			} else if (is_array($shipment->error)) {
				foreach ($shipment->error as $error) {
					$orderNoteText .= substr($error, 0, 80) . '</br>';
					array_push($this->messages, substr($error, 0, 80));
				}
			}
			if ($order) {
				$orderNoteText .= __('Det oppsto en feil ved sending av denne ordren til transportleverandør.', 'wildrobot-logistra');
				$orderNoteText .= $new_line;
				$order->add_order_note($orderNoteText);
			}
			$messagesAsString = implode(",", $this->messages);
			throw new Exception($messagesAsString ? $messagesAsString : "Fant ingen feilmelding.");
		}
		if (!property_exists($shipment, 'cargonizerShipment')) {
			$orderNoteText .= __('Responsen fra fraktserver hadde ikke informasjon fra transportleverandør. Noe gikk feil i opprettelsen.', 'wildrobot-logistra');
			$orderNoteText .= $new_line;
			$order->add_order_note($orderNoteText);
			throw new Exception("Responsen fra fraktserver hadde ikke informasjon fra transportleverandør. Noe gikk feil i opprettelsen.");
		}
		/* Log order created */
		if (property_exists($shipment->cargonizerShipment, 'id') && property_exists($shipment->cargonizerShipment, 'number')) {
			$orderNoteText .= "<b>Sendingsnummer</b>" . '</br>';
			$orderNoteText .= $shipment->cargonizerShipment->{'number'} . '</br>';
			$orderNoteText .= '</br>';
			if ($shipment->cargonizerShipment->productName !== null) {
				array_push($this->messages, sprintf(__($delivery_type . " %s opprettet med %s"), $shipment->cargonizerShipment->number, $shipment->cargonizerShipment->productName));
			} else {
				array_push($this->messages, sprintf(__($delivery_type . " %s opprettet"), $shipment->cargonizerShipment->number));
			}
		}
		/* Create a print label on printer again button */
		if (!empty($this->printer) && $this->printer !== "9999999") {
			$orderNoteText .= "<b>Skriv etikett på nytt</b>" . '</br>';

			// Add new button for print_consignment
			$print_url = add_query_arg(array(
				'action' => 'wildrobot_logistra_print_consignment',
				'printer_id' => $this->printer,
				'consignment_id' => $shipment->cargonizerShipment->id,
				'nonce' => wp_create_nonce('wildrobot_logistra_print_consignment')
			), admin_url('admin-ajax.php'));

			$orderNoteText .= '<a href="' . esc_url($print_url) . '" class="">' . __('Klikk her for å skrive ut etikett', 'wildrobot-logistra') . '</a>';
			$orderNoteText .= '</br>';
			$orderNoteText .= '</br>';
		}


		/* Cost estimate */
		if (property_exists($shipment->cargonizerShipment, 'costGross') && !empty($shipment->cargonizerShipment->costGross)) {
			$orderNoteText .= '<b>' . __('Estimert fraktkost(Brutto):', 'logistra-robots') . '</b>' . '</br>';
			$orderNoteText .= sprintf(__('Kr. %s eks/mva (brutto)', 'logistra-robots'), $shipment->cargonizerShipment->costGross) . '</br>';
			$orderNoteText .= '</br>';
			array_push($this->messages, sprintf(__("Estimert fraktkost brutto: %s (%s)"), $shipment->cargonizerShipment->costGross, $shipment->cargonizerShipment->productName));
		}

		if (property_exists($shipment->cargonizerShipment, 'costNet') && !empty($shipment->cargonizerShipment->costNet)) {
			$orderNoteText .= '<b>' . __('Estimert fraktkost(Netto):', 'logistra-robots') . '</b>' . '</br>';
			$orderNoteText .= sprintf(__('Kr. %s eks/mva (netto)', 'logistra-robots'), $shipment->cargonizerShipment->costNet) . '</br>';
			$orderNoteText .= '</br>';
			// array_push($this->messages, sprintf(__("Estimert fraktkost netto: %s (%s)"), $shipment->cargonizerShipment->costNet, $shipment->cargonizerShipment->productName));
		}
		/* Email label */
		if (property_exists($shipment->cargonizerShipment, 'emailLabel') && $shipment->cargonizerShipment->emailLabel === true) {
			$orderNoteText .= 'Etikett sent til kunde' . '</br>';
			$orderNoteText .= '</br>';
			array_push($this->messages, sprintf(__("Etikett sent til kunde")));
		}

		/* tracking-url */
		if (property_exists($shipment->cargonizerShipment, 'trackingUrl') && !empty($shipment->cargonizerShipment->trackingUrl)) {
			$orderNoteText .= '<b>' . __('Sporing:', 'logistra-robots') . '</b>' . '</br>';
			$orderNoteText .= sprintf('<a href="%2$s" target="_blank">%1$s</a> ', __('Klikk for å spore pakken:', 'logistra-robots'), $shipment->cargonizerShipment->trackingUrl) . '</br>';
			$orderNoteText .= '</br>';
			if ($order) {
				$order->update_meta_data('logistra-robots-tracking-url', $shipment->cargonizerShipment->trackingUrl);
			}
		}

		/* consignment-pdf */
		if (property_exists($shipment->cargonizerShipment, 'consignmentPdf') && !empty($shipment->cargonizerShipment->consignmentPdf)) {
			$orderNoteText .= '<b>' . __('Etikett:', 'logistra-robots') . '</b>' . '</br>';
			$orderNoteText .= sprintf('<a href="%2$s" target="_blank">%1$s</a> ', __('Klikk her for å laste ned:', 'logistra-robots'), $shipment->cargonizerShipment->consignmentPdf) . '</br>';
			$orderNoteText .= '</br>';
			if ($order) {
				$order->update_meta_data('logistra-robots-freight-label-url', $shipment->cargonizerShipment->consignmentPdf);
			}
		}

		/* Log if we have sent this order before */
		if (!wc_string_to_bool($this->is_return_label) && $order) {
			if (!$order->update_meta_data('logistra-robots-sent', 'yes')) {
				$orderNoteText .= '<b>Nb!</b>' . '</br>';
				$orderNoteText .= __('- Denne ordren har tidligere blitt sendt til transportleverandr.', 'logistra-robots') . '</br>';
			}
		} else {
			$order->update_meta_data('logistra-robots-sent-return', 'yes');
		}

		/* Log who send the order */
		$user = wp_get_current_user();
		if ($user) {
			$orderNoteText .= '<b>' . __('Sendt av:', 'logistra-robots') . '</b>' . '</br>';
			$orderNoteText .= sprintf('%2$s (%1$s)', $user->ID, $user->display_name) . '</br>';
			$orderNoteText .= '</br>';
		}

		/* Order related actions */
		if ($order) {
			do_action('wildrobot_logistra_order_derlivery_created', $order, $shipment->cargonizerShipment);
			/* Should we complete the order */
			if (!wc_string_to_bool($this->is_return_label)) {
				if (get_option('wildrobot_logistra_setting_complete_order') === 'yes') {
					$completeOrderToStatus = get_option('wildrobot_logistra_setting_complete_order_to_status');
					$new_status = !empty($completeOrderToStatus) ? $completeOrderToStatus : 'wc-completed';
					if (wc_is_order_status($new_status)) {
						$order->update_status($new_status);
						$orderNoteText .= __('- Ordren ble fullført ved opprettelse av levering.', 'logistra-robots') . '</br>';
					}
				}
			}
			/* Log info to order */
			$order->add_order_note($orderNoteText);
			$order->save();
		}
		return array_merge($this->warnings, $this->messages);
	}

	// Formats


	public function to_args()
	{
		return get_object_vars($this);
	}

	public function to_json()
	{
		$this->check_required();

		// local variables
		$added_customs_value = false;

		// Consignment
		$consignment = [
			'_attributes' => [
				'transport_agreement' =>  $this->transport_agreement_id,
				'print' => $this->print_without_api,
				'estimate' => $this->estimate,
			],
			'transfer' => $this->transfer_now,
			'email_label_to_consignee' => $this->email_return_label_to_consignee,
			'email-notification-to-consignee' => $this->email_notification_to_consignee,
			'values' => [
				'value' => [
					[
						'_attributes' => [
							'name' => 'provider',
							'value' => "WildRobot.app",
						],
					],
					[
						'_attributes' => [
							'name' => 'provider-email',
							'value' => "bipbop@wildrobot.app",
						],
					],
					[
						'_attributes' => [
							'name' => 'provider-version',
							'value' => WILDROBOT_LOGISTRA_VERSION,
						],
					],
					[
						'_attributes' => [
							'name' => 'ordre_id',
							'value' => $this->order_id,
						],
					],
				],
			],
			"booking_request" => $this->booking_request,
			'product' => $this->product,
			"parts"      => [
				"consignee" => $this->consignee,
			],
			"items"      => [
				"item" => $this->packages,
			],
			"messages" => [
				"carrier" => $this->carrier_message,
				"consignee" => $this->consignee_message,
			],
			"references" => [
				"consignor" => "Ordre: " . $this->order_number,
				"consignee" => "Ordre nr: " . $this->order_number,
			],
			"services" => [
				"service" => [],
			]
		];
		if (!empty($this->services)) {
			foreach ($this->services as $service) {
				// Service should be an string
				if (!is_string($service)) {
					continue;
				}
				// booking request
				if ($service === "wildrobot_booking_request") {
					$consignment["booking_request"] = true;
					continue;
				}
				// bring priority
				if ($service === "bring_priority") {
					if (!empty($this->bring_priority)) {
						array_push($consignment["services"]["service"], [
							'_attributes' => [
								'id' => $service,
								'type' =>  $this->bring_priority,
							],
						]);
					} else {
						$this->add_error("Bring priotets services er lagt til krever angitt servicegrad");
					}
					continue;
				}
				// dhl express pickup
				if ($service === "dhl_express_pickup") {
					if (empty($this->dhl_express_pickup)) {
						$this->add_error("DHL express opphenting tjeneste er lagt til. Krever opphentingsinformasjon.");
					} else {
						array_push($consignment["services"]["service"], array_merge(
							[
								'_attributes' =>  [
									"id" => $service,
								],
							],
							$this->dhl_express_pickup
						));
					}
					continue;
				}
				// electronic invoice
				if ($service === 'dhl_express_electronic_invoice') {
					if (empty($this->electronic_invoice)) {
						$this->add_error("DHL express faktura tjeneste er lagt til. Krever fakturainformasjon.");
					}
					// format data object
					$data_object_with_items_array = (object) ["items" =>  $this->electronic_invoice["items"]];
					$this->electronic_invoice["data"] = json_encode($data_object_with_items_array);
					unset($this->electronic_invoice["items"]);
					array_push(
						$consignment["services"]["service"],
						array_merge(
							[
								'_attributes' => [
									"id" => $service,
								]
							],
							$this->electronic_invoice
						)
					);
					// if eletronic invoice remarks contains word lithium or PI967-II, add it as a service
					if (stripos($this->electronic_invoice["other_remarks"], "lithium") !== false || stripos($this->electronic_invoice["other_remarks"], "PI967-II") !== false) {
						array_push($consignment["services"]["service"], [
							'_attributes' => [
								"id" => "dhl_express_lithium_ion_pi967ii",
							]
						]);
					}
					continue;
				}
				// Customs value
				if ($service === "dhl_express_customs_value") {
					if (empty($this->customs_value)) {
						$this->add_error("DHL express tollverdi tjeneste er lagt til. Krever toll informasjon.");
					}
					if (empty($this->customs_value["paid_by_custno"])) {
						$this->add_error("DHL express tollverdi krever at du setter kundenummer som skal betale.");
					}
					array_push($consignment["services"]["service"], array_merge(
						[
							'_attributes' =>  [
								"id" => $service,
							],
						],
						$this->customs_value
					));
					$added_customs_value = true;
					continue;
				}
				// tg_etterkrav
				if ($service === "tg_etterkrav") {
					if (empty($this->total_with_tax)) {
						$this->add_error("Etterkrav tjeneste krever at ordre totaler er satt.");
					}
					array_push($consignment["services"]["service"], array_merge(
						[
							'_attributes' =>  [
								"id" => $service,
							],
						],
						[
							"amount" => intval($this->total_with_tax),
							"currency" => strtoupper($this->currency),
						]
					));
					continue;
				}

				// END - Add all other services as identifier
				array_push($consignment["services"]["service"], [
					'_attributes' =>  [
						"id" => $service,
					],
				]);
			}
		}
		// service partner
		if (!empty($this->service_partner_match)) {
			// TODO - Add verfication if we need to add this.
			$consignment["parts"]["service_partner"] = [
				"number" => $this->service_partner_match["number"],
				"name" => $this->service_partner_match["name"],
				"address1" => !empty($this->service_partner_match["address1"]) ? $this->service_partner_match["address1"] : "",
				"postcode" => $this->service_partner_match["postcode"],
				"city" => $this->service_partner_match["city"],
				"country" => $this->service_partner_match["country"],
			];
		}
		// return address
		if (empty($this->return_address)) {
			$this->add_error("Fant ikke retur addresse. Sett dette opp.");
		} else {
			$consignment["parts"]["return_address"] = $this->return_address;
		}

		// DHL spesific rule about not adding consignee on dhl_domestic_express
		if ($consignment["product"] === "dhl_domestic_express") {
			unset($consignment["references"]["consignee"]);
		}
		// DHL terms_of_delivery
		if ($consignment["product"] === "dhl_express_worldwide_doc" || $consignment["product"] === "dhl_express_worldwide_nondoc"  || $consignment["product"] === "dhl_express_economy_select") {
			if (empty($this->customs_value)) {
				$this->add_error("Leveringsbetingelser tjeneste er lagt til. Krever leveringsbetingelser informasjon.");
			}
			$consignment["tod"]["_attributes"] = $this->terms_of_delivery;
		}
		// DHL Check if customs value is added
		if ($consignment["product"] === "dhl_express_worldwide_nondoc") {
			if (!$added_customs_value) {
				$this->add_error('Leveranser med DHL Express Worldwide (nondoc) krever at tjenesten Tollverdi er lagt til.');
			}
		}
		if (count($this->errors) > 0) {
			throw new Exception(implode("\n", $this->errors));
		}
		return $consignment;
	}

	public function to_estimate()
	{
		$this->normalize_weight_and_dimensions();
		$consignments = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><consignments></consignments>', 0, false);
		$consignment = $consignments->addChild('consignment');
		$consignment->addAttribute("transport_agreement", $this->transport_agreement_id);
		// $consignment->addAttribute("estimate", wc_bool_to_string($this->estimate)); // "estimate" skal ikke være med i den webservicen, og om den skulle det, så skulle den ha vært true eller false
		$product = $consignment->addChild('product', $this->product);
		$parts = $consignment->addChild('parts');

		if (!empty($this->service_partner_match)) {
			$service_partner = $parts->addChild('service_partner');
			$service_partner->addChild('number', $this->service_partner_match["number"]);
			$service_partner->addChild('name', $this->service_partner_match["name"]);
			$service_partner->addChild('address1', $this->service_partner_match["address1"]);
			$service_partner->addChild('postcode', $this->service_partner_match["postcode"]);
			$service_partner->addChild('city', $this->service_partner_match["city"]);
			$service_partner->addChild('country', $this->service_partner_match["country"]);
		}
		$consignee = $parts->addChild('consignee');
		foreach ($this->consignee as $key => $value) {
			if (!empty($value)) {
				$consignee->addChild($key, $value);
			}
		}

		$items = $consignment->addChild('items');
		foreach ($this->packages as $package) {
			$item = $items->addChild('item');
			foreach ($package["_attributes"] as $key => $value) {
				if (!empty($value)) {
					$item->addAttribute($key, $value);
				}
			}
		}

		if (!empty($this->services)) {
			$services = $consignment->addChild('services');
			foreach ($this->services as $service) {
				if ($service === "wildrobot_booking_request") {
					// $consignment->addChild('booking_request', true);
					continue;
				}
				if ($service === "bring_priority") {
					if (!empty($this->bring_priority)) {
						$some_service = $services->addChild('service');
						$some_service->addAttribute("id", $service);
						$some_service->addAttribute("type", $this->bring_priority);
					} else {
						$this->add_error("Bring prioritet tjeneste er lagt til. Det krever angitt servicegrad i frakemetode relasjons innstillinger.");
					}
					continue;
				}
				if ($service === "dhl_express_pickup") {
					if (empty($this->dhl_express_pickup)) {
						$this->add_error("DHL express opphenting tjeneste er lagt til. Krever opphentingsinformasjon.");
					} else {
						$some_service = $services->addChild('service');
						$some_service->addAttribute("id", $service);
						foreach ($this->dhl_express_pickup as $key => $value) {
							if (!empty($value)) {
								$some_service->addChild($key, $value);
							}
						}
					}
					continue;
				}
				if ($service === 'dhl_express_electronic_invoice') {
					if (empty($this->electronic_invoice)) {
						$this->add_error("DHL express faktura tjeneste er lagt til. Krever fakturainformasjon.");
					}
					// format data object
					$data_object_with_items_array = (object) ["items" =>  $this->electronic_invoice["items"]];
					$this->electronic_invoice["data"] = json_encode($data_object_with_items_array);
					unset($this->electronic_invoice["items"]);
					$some_service = $services->addChild('service');
					$some_service->addAttribute("id", $service);
					foreach ($this->electronic_invoice as $key => $value) {
						if (!empty($value)) {
							$some_service->addChild($key, $value);
						}
					}
					continue;
				}
				if ($service === "dhl_express_customs_value") {
					if (empty($this->customs_value)) {
						$this->add_error("DHL express tollverdi tjeneste er lagt til. Krever toll informasjon.");
					}
					if (empty($this->customs_value["paid_by_custno"])) {
						$this->add_error("DHL express tollverdi krever at du setter kundenummer som skal betale.");
					}
					$some_service = $services->addChild('service');
					$some_service->addAttribute("id", $service);
					foreach ($this->customs_value as $key => $value) {
						if (!empty($value)) {
							$some_service->addChild($key, $value);
						}
					}
					$added_customs_value = true;
					continue;
				}
				$some_service = $services->addChild('service');
				$some_service->addAttribute("id", $service);
				// END - Add all other services as identifiers
			}
		}


		// DHL terms_of_delivery
		if ($this->product === "dhl_express_worldwide_doc" || $this->product === "dhl_express_worldwide_nondoc" || $this->product === "dhl_express_economy_select") {
			$tod = $consignment->addChild('tod');
			if (empty($this->customs_value)) {
				$this->add_error("Leveringsbetingelser tjeneste er lagt til. Krever leveringsbetingelser informasjon.");
			}
			foreach ($this->terms_of_delivery as $key => $value) {
				if (!empty($value)) {
					$tod->addChild($key, $value);
				}
			}
		}

		$output = $consignments->asXML();
		return $output;
	}
	// Helpers
	private function add_error($text)
	{
		array_push($this->errors, $text);
	}

	private function add_warning($text)
	{
		array_push($this->warnings, $text);
	}

	private function add_message($text)
	{
		array_push($this->messages, $text);
	}

	public static function goods_letter($args)
	{
		$args = wp_parse_args($args, [
			"booking"     => "no",
		]);
		$transport_agreement = Wildrobot_Logistra_DB::get_transport_agreement_for_product_unsafe("varebrev_split");
		if (empty($transport_agreement)) {
			throw new Exception("Fant ikke hovedsending i dine fraktavtaler. Sjekk med Logistra at dette er en del av dine transportavtaler. ");
		}
		$consignment = [
			'_attributes' => [
				'transport_agreement' =>  $transport_agreement["ta_id"],
				'print' => false,
			],
			'transfer' => true,
			'email_label_to_consignee' => false,
			'values' => [
				'value' => [
					[
						'_attributes' => [
							'name' => 'provider',
							'value' => "WildRobot.app",
						],
					],
					[
						'_attributes' => [
							'name' => 'provider-email',
							'value' => "bipbop@wildrobot.app",
						],
					],
					[
						'_attributes' => [
							'name' => 'provider-version',
							'value' => WILDROBOT_LOGISTRA_VERSION,
						],
					]
				],
			],
			"booking_request" => $args["booking"] === "yes",
			'product' => "varebrev_split",
			"parts"      => [
				"consignee" => [
					"freight_payer"     => true,
					"customer-number" => 3552692,
					"number"  => 0,
					"name" => "Varebrev splittes Alfaset 105",
					"address1"     => "Alfaset 3. Industrivei 25",
					"postcode"    => "0668",
					"city"   => "Oslo",
					"country"    => "NO",
				],
			],
			"items"      => [
				"item" => [
					[
						"_attributes" => [
							"amount"      => 1,
							"type"        => "package",
							"weight"      => 5,
						],
					]
				],
			]
		];

		$json =  [
			'consignment' => [
				apply_filters('wildrobot-logistra-consignment-goods-letter', $consignment)
			],
			'print' => [
				'printerDefault' => get_option("wildrobot_logistra_printer_default"),
				'printTime' => self::toLocalTime(strtotime(current_time('mysql'))),
			],
			'transfer' => [
				'transferTime' =>   self::toLocalTime(strtotime(current_time('mysql'))),
				'auto' =>  true,
			],
		];
		$shipment = Wildrobot_Logistra_Backend::create_consignments($json);

		/* no reponse */
		if ($shipment === null || $shipment === 404 || empty($shipment)) {
			throw new Exception(__('Ingen kobling til fraktserver.', 'wildrobot-logistra'));
		}

		return $args["booking"] === "yes" ? "Hovesending med opphenting opprettet" : "Hovesending opprettet";
	}

	private static function get_print_time($printerInterval, $printTimeString)
	{
		// Calculate local time
		$nowLocal = self::toLocalTime(strtotime(current_time('mysql')));
		switch ($printerInterval) {
			case 'time':
				$printTimeDate = self::toLocalTime(strtotime($printTimeString));
				$printTimeInFuture = $printTimeDate - $nowLocal > 0 ? true : false;
				if ($printTimeInFuture) {
					$printTime = $printTimeDate;
				} else {
					$printTime = strtotime(' +1day', $printTimeDate);
				}
				break;
			case 'manual':
				$printTime = strtotime('+59 year');
				break;
			default:
				$printTime = $nowLocal;
				break;
		}
		// Print time must be UTC 
		return $printTime;/* + $diffUTC, */
	}

	private static function get_transfer_time($transferInterval, $transferTimeString, $printTime)
	{
		$nowLocal = self::toLocalTime(strtotime(current_time('mysql')));
		switch ($transferInterval) {
			case 'time':
				$transferTimeDate = self::toLocalTime(strtotime($transferTimeString));
				$transferTimeAfterPrintTime = $transferTimeDate - $printTime > 0 ? true : false;
				if ($transferTimeAfterPrintTime) {
					$transferTime = $transferTimeDate;
				} else {
					$transferTime = strtotime(' +1day', $transferTimeDate);
				}
				break;
			case 'manual':
				$transferTime = strtotime('+59 year');
				break;
			case 'none':
				$transferTime = strToTime('+59 year');
				break;
			default:
				$transferTime = $nowLocal;
				break;
		}
		// Transfer time must be UTC 
		return $transferTime;
	}

	private static function toLocalTime($local)
	{
		$nowLOCAL = strToTime(current_time('mysql'));
		$tz = date_default_timezone_get();
		date_default_timezone_set('UTC');

		$nowUTC = time();

		date_default_timezone_set($tz);
		$diff = $nowUTC - $nowLOCAL;
		$utc = $local + $diff;
		return $utc;
	}
	private static function trim_customer_messages($message)
	{
		return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', mb_convert_encoding($message, "UTF-8")));
	}
}
