<?php

class Wildrobot_Logistra_Order_Utils
{

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function bulk_actions_send_order_transport($actions)
	{
		$actions['send_order_transport'] = __('Send via Wildrobot frakt', 'wildrobot-logistra');
		return $actions;
	}


	public function send_order($order_id)
	{
		$error_orders = [];
		$success_orders = [];
		try {
			$send_order_on_complete_order = get_option('wildrobot_logistra_setting_send_consignment_on_complete_order') === 'yes';

			if ($send_order_on_complete_order) {
				$order = wc_get_order($order_id);
				$sent_before = $order->get_meta("logistra-robots-sent", true) === "yes";
				$sent_recently = get_transient("wildrobot-logistra-sent-recently-" . $order_id);
				if (!$sent_before && !$sent_recently) {
					set_transient('wildrobot-logistra-sent-recently-' . $order_id, 'yes', 100);
					$messages = [];
					$errors = [];
					$logger = new WC_Logger();
					$context = ['source' => 'wildrobot-logistra-send-order-on-complete'];
					foreach (Wildrobot_Logistra_Consignment_Order::get_consignment_args_from_order_id($order_id) as $consignment_arg) {
						try { // consignment tryCatch
							$consignment = new Wildrobot_Logistra_Consignment($consignment_arg);
							$messages_from_consignment = $consignment->send_backend();
							$messages = array_merge($messages, $messages_from_consignment);
						} catch (\Throwable $error) {
							array_push($errors, $error->getMessage());
						}
					}
					if (!empty($errors)) {
						throw new Exception(join("\n", $errors));
					}
					$logger->info("Sent ordre id " . $order_id . " ved fullføring av ordre. " . wc_print_r($messages, true), $context);
					$success_orders[$order_id] = wc_print_r(join(" | ", $messages), true);
					set_transient('wildrobot_send_order_notices_success', $success_orders, 5);
				} else {
					$error_orders[$order_id] = "Ordren er sendt tidligere eller nylig sendt.";
					set_transient('wildrobot_send_order_notices_error', $error_orders, 5);
				}
			}
		} catch (\Throwable $error) {
			$logger->error("Feil orderId: " . $order_id . " ved fullføring av ordre. " . $error->getMessage(), $context);
			$error_orders[$order_id] = $error->getMessage();
			set_transient('wildrobot_send_order_notices_error', $error_orders, 5);
			echo 'Caught exception: ', $error->getMessage(), "\n";
		}
	}


	public function bulk_send_order($redirect_to, $action, $post_ids)
	{
		if ($action !== 'send_order_transport') {
			return $redirect_to;
		} // Exit
		$logger = new WC_Logger();
		$context = ['source' => 'wildrobot-logistra-bulk'];
		$logger->info('_________Startet bulk send ordre for transport______________', $context);
		$logger->info('_________Ordre: ' . join(", ", $post_ids), $context);

		$processed_ids = [];
		$error_ids = [];

		$success_orders = [];
		$error_orders = [];

		foreach ($post_ids as $orderId) {
			try { // order id tryCatch
				$order = wc_get_order($orderId);
				$order_sent = $order->get_meta("logistra-robots-sent", true) === "yes";
				if ($order_sent) {
					$logger->info("Ordre ID: " . $orderId . " er allerede sendt. ", $context);
					$error_ids[] = $orderId;
					$error_orders[$orderId] = "Ordre er allerede sendt.";
					continue;
				}

				$messages = [];
				$errors = [];
				foreach (Wildrobot_Logistra_Consignment_Order::get_consignment_args_from_order_id($orderId) as $consignment_arg) {
					try { // consignment tryCatch
						$consignment = new Wildrobot_Logistra_Consignment($consignment_arg);
						$messages_from_consignment = $consignment->send_backend();
						$messages = array_merge($messages, $messages_from_consignment);
					} catch (\Throwable $error) {
						array_push($errors, $error->getMessage());
					}
				}
				if (!empty($errors)) {
					throw new Exception(join("\n", $errors));
				}
				$logger->info("Sent ordre " . $orderId . " med bulk. Message: " . wc_print_r(join(" | ", $messages), true), $context);
				$processed_ids[] = $orderId;
				$success_orders[$orderId] = wc_print_r(join(" | ", $messages), true);
			} catch (\Throwable $error) {
				$logger->error("Feil orderId: " . $orderId . ". " . $error->getMessage(), $context);
				$error_ids[] = $orderId;
				$error_orders[$orderId] = $error->getMessage();
				continue;
			}
		}

		$logger->info('_________Sendt: ' . join(", ", $processed_ids), $context);
		$logger->info('_________Feil: ' . join(", ", $error_ids), $context);
		$logger->info('_________Slutt bulk send ordre for transport______________', $context);

		set_transient('wildrobot_send_order_notices_success', $success_orders, 5);
		set_transient('wildrobot_send_order_notices_error', $error_orders, 5);
		return $redirect_to = add_query_arg(['orders_sent_transport' => 1, 'processed_count' => count($processed_ids), 'errors_count' => count($error_ids), 'processed_ids' => implode(',', $processed_ids),], $redirect_to);
	}

	public function display_bulk_action_notices()
	{
		// Display success_orders
		$bulk_success_orders = get_transient('wildrobot_send_order_notices_success');
		if ($bulk_success_orders) {
			echo '<div class="notice notice-success is-dismissible">
					<p>Levering opprettet med Wildrobot:</p>
					<ul>';
			foreach ($bulk_success_orders as $orderId => $message) {
				echo "<li>Ordre $orderId: $message</li>";
			}
			echo '</ul></div>';

			// Don't forget to delete the transient
			delete_transient('wildrobot_send_order_notices_success');
		}

		// Display error_orders
		$bulk_error_orders = get_transient('wildrobot_send_order_notices_error');
		if ($bulk_error_orders) {
			echo '<div class="notice notice-error is-dismissible">
					<p>Levering ikke opprettet med Wildrobot:</p>
					<ul>';
			foreach ($bulk_error_orders as $orderId => $message) {
				echo "<li>Ordre $orderId: $message</li>";
			}
			echo '</ul></div>';

			// Don't forget to delete the transient
			delete_transient('wildrobot_send_order_notices_error');
		}
	}


	public static function get_dimensions_from_container($product_map, $package_group_to_count = false)
	{
		$box_dimensions = [
			"length" => 0,
			"width" => 0,
			"height" => 0,
		];

		foreach ($product_map as $product_id => $product_data) {
			$quantity = $product_data['quantity'];
			$variation_id = $product_data['variation_id'];

			$product = wc_get_product($product_id);
			if ($product) {
				if (!self::should_count($product_id, $package_group_to_count)) {
					continue;
				}

				$product_dimensions = [];

				if ($variation_id) {
					$variation = wc_get_product($variation_id);
					if ($variation && $variation->has_dimensions()) {
						$product_dimensions = [
							"length" => (float)$variation->get_length(),
							"width" => (float)$variation->get_width(),
							"height" => (float)$variation->get_height(),
						];
					}
				}

				if (empty($product_dimensions) && $product->has_dimensions()) {
					$product_dimensions = [
						"length" => (float)$product->get_length(),
						"width" => (float)$product->get_width(),
						"height" => (float)$product->get_height(),
					];
				}

				if (!empty($product_dimensions)) {
					$box_dimensions = self::add_product_box($box_dimensions, $product_dimensions, $quantity);
				}
			}
		}

		$box_dimensions["volume"] = self::calculate_volume($box_dimensions["length"], $box_dimensions["width"], $box_dimensions["height"]);
		return $box_dimensions;
	}
	// Static
	public static function get_dimensions_from_order($order, $package_group_to_count = false)
	{
		$_pf = new WC_Product_Factory();

		$box_dimensions = [
			"length" => 0,
			"width" => 0,
			"height" => 0,
		];
		if (sizeof($order->get_items(), false) > 0) {
			/** @var WC_Order_Item $item */
			foreach ($order->get_items() as $item) {
				// Dont count items that are marked to be shipped separately
				if (!self::should_count($item['product_id'], $package_group_to_count)) {
					continue;
				}
				// START bundle spesific 
				$is_bundled = false;

				if ($item['product_id'] > 0) {
					$_product = $_pf->get_product($item['product_id']);
					if (function_exists('wc_pb_is_bundled_order_item')) {
						// Call the function here
						$is_bundled = wc_pb_is_bundled_order_item($item, $order);
						if ($is_bundled) {
							$bundle_container_item = wc_pb_get_bundled_order_item_container($item, $order);
							$use_bundle_container_dimensions  = true;
							/** @var WC_Product_Bundle $bundle_product */
							$bundle_product = $_pf->get_product($bundle_container_item['product_id']);
							$use_bundle_container_dimensions = !$bundle_product->get_virtual();
							// if we dont use the bundle container dimensions, then we should just go forward and use the product as a simple product.
							if ($use_bundle_container_dimensions) {
								$product_dimensions = [
									"length" => intval($bundle_product->get_length()),
									"width" => intval($bundle_product->get_width()),
									"height" => intval($bundle_product->get_height()),
								];
								$box_dimensions = self::add_product_box($box_dimensions, $product_dimensions, $bundle_container_item['quantity']);
								continue;
							}
						}
					}
					if (!is_object($_product)) continue; // skip virtual products
					if ($_product->is_virtual()) continue;
					if ($_product->is_type('variable')) {
						$variation = wc_get_product($item["variation_id"]);
						if ($variation instanceof WC_Product_Variation) {
							$product_dimensions = [
								"length" => intval($variation->get_length()),
								"width" => intval($variation->get_width()),
								"height" => intval($variation->get_height()),
							];
						}
					}
					if ($_product->is_type('bundle')) {
						// bundled dimensions are handled above.
						continue;
					} else {
						$product_dimensions = [
							"length" => intval($_product->get_length()),
							"width" => intval($_product->get_width()),
							"height" => intval($_product->get_height()),
						];
					}
					$box_dimensions = self::add_product_box($box_dimensions, $product_dimensions, $item['quantity']);
				}
			}
		}
		$box_dimensions["volume"] = self::calculate_volume($box_dimensions["length"], $box_dimensions["width"], $box_dimensions["height"]);
		return $box_dimensions;
	}

	public static function get_dimensions_from_package($package)
	{

		$box_dimensions = [
			"length" => 0,
			"width" => 0,
			"height" => 0,
		];
		/** @var WC_Order_Item $item */
		foreach ($package['contents'] as $item) {
			$product = wc_get_product($item['product_id']);
			if (!self::should_count($item['product_id'], false)) {
				// Then this product has a separate package option set.
				continue;
			}
			if ($item['product_id'] > 0) {
				if (function_exists('wc_pb_is_bundled_cart_item')) {
					// Call the function here
					$is_bundled = wc_pb_is_bundled_cart_item($item);
					if ($is_bundled) {
						$bundle_container_item = wc_pb_get_bundled_cart_item_container($item);
						$use_bundle_container_dimensions  = true;
						// bundle reference https://woocommerce.com/document/bundles/bundles-functions-reference/
						/** @var WC_Product_Bundle $bundle_product */
						$bundle_product = wc_get_product($bundle_container_item['product_id']);
						$use_bundle_container_dimensions = !$bundle_product->get_virtual();
						// if we dont use the bundle container dimensions, then we should just go forward and use the product as a simple product.
						if ($use_bundle_container_dimensions) {
							$product_dimensions = [
								"length" => intval($bundle_product->get_length()),
								"width" => intval($bundle_product->get_width()),
								"height" => intval($bundle_product->get_height()),
							];
							$box_dimensions = self::add_product_box($box_dimensions, $product_dimensions, $bundle_container_item['quantity']);
							continue;
						}
					}
				}
				if (!is_object($product)) continue; // skip virtual products
				if ($product->is_virtual()) continue;
				if ($product->is_type('variable')) {
					$variation = wc_get_product($item["variation_id"]);
					if ($variation instanceof WC_Product_Variation) {
						$product_dimensions = [
							"length" => intval($variation->get_length()),
							"width" => intval($variation->get_width()),
							"height" => intval($variation->get_height()),
						];
					}
				} else if ($product->is_type('bundle')) {
					$product_dimensions = [
						"length" => intval($product->get_length()),
						"width" => intval($product->get_width()),
						"height" => intval($product->get_height()),
					];
				} else {
					$product_dimensions = [
						"length" => intval($product->get_length()),
						"width" => intval($product->get_width()),
						"height" => intval($product->get_height()),
					];
				}
				$box_dimensions = self::add_product_box($box_dimensions, $product_dimensions, $item['quantity']);
			}
		}
		$box_dimensions["volume"] = self::calculate_volume($box_dimensions["length"], $box_dimensions["width"], $box_dimensions["height"]);
		return $box_dimensions;
	}

	public static function calculate_package_amount(&$packages, &$counter, &$counted_groups, $product_id, $quantity, $product_ids_to_quantity_map_for_container)
	{
		$product = wc_get_product($product_id);

		if (!$product) {
			// Handle invalid product
			return;
		}

		$package_amount = (int) $product->get_meta('_wildrobot_package_amount', true);

		for ($i = 0; $i < $package_amount; $i++) {
			// If this is the only item and package_amount is 1, do not increment counter
			if (!(count($product_ids_to_quantity_map_for_container) === 1 && $i === 0)) {
				$counter++;
			}

			$package = $product->get_meta("_wildrobot_package_$i", true);

			// if (!$package || !isset($package["max_amount"], $package["length"], $package["width"], $package["height"], $package["name"], $package["weight"])) {
			// 	// Handle missing package data
			// 	continue;
			// }

			$max_amount = (int) $package["max_amount"];
			$packages_needed = 1;

			if ($max_amount > 0 && $max_amount < $quantity) {
				$packages_needed = ceil($quantity / $max_amount);
			} else {
				$max_amount = $quantity;
			}

			$adjusted_quantity = $quantity;
			$quantity_left = $quantity;

			for ($j = 0; $j < $packages_needed; $j++) {
				if ($j !== 0) {
					$counter++;
				}

				if ($quantity_left < $max_amount) {
					$adjusted_quantity = $quantity_left;
				} else {
					$adjusted_quantity = $max_amount;
				}

				// Validate package dimensions
				$length = isset($package["length"]) && is_numeric($package["length"]) ? (int) $package["length"] : 0;
				$width = isset($package["width"]) && is_numeric($package["width"]) ? (int) $package["width"] : 0;
				$height = isset($package["height"]) && is_numeric($package["height"]) ? (int) $package["height"] : 0;

				$box_dimensions = [
					"length" => 0,
					"width" => 0,
					"height" => 0,
				];

				$final_dimensions = Wildrobot_Logistra_Order_Utils::add_product_box($box_dimensions, [
					"length" => $length,
					"width" => $width,
					"height" => $height,
				], $adjusted_quantity);

				$packages[$counter] = [
					"length"      => $final_dimensions["length"],
					"height"      => $final_dimensions["height"],
					"width"       => $final_dimensions["width"],
					"volume"      => Wildrobot_Logistra_Order_Utils::calculate_volume($final_dimensions["length"], $final_dimensions["height"], $final_dimensions["width"]),
					"amount"      => 1,
					"description" => $package["name"],
					"type"        => "package",
					"weight"      => isset($package["weight"]) && is_numeric($package["weight"]) ? $package["weight"] * $adjusted_quantity : 0,
				];

				$quantity_left -= $adjusted_quantity;

				// Prevent negative quantity
				if ($quantity_left < 0) {
					$quantity_left = 0;
				}

				// If no more quantity left, break the loop
				if ($quantity_left === 0) {
					break;
				}
			}
		}

		// Handle separate packages if required
		if ($product->get_meta('_wildrobot_separate_package_for_product', true) === "yes") {
			$option = $product->get_meta('_wildrobot_separate_package_for_product_name', true);
			$separate_package_for_product_name = !empty($option) ? $option : $product->get_name();

			if (!in_array($separate_package_for_product_name, $counted_groups, true)) {
				$dimension_result = self::get_dimensions_from_container($product_ids_to_quantity_map_for_container, $separate_package_for_product_name);
				$counter++;
				$counted_groups[] = $separate_package_for_product_name;

				$packages[$counter] = [
					"length"      => $dimension_result["length"],
					"height"      => $dimension_result["height"],
					"width"       => $dimension_result["width"],
					"volume"      => $dimension_result["volume"],
					"amount"      => 1,
					"description" => $separate_package_for_product_name,
					"type"        => "package",
					"weight"      => self::get_weight_from_container($product_ids_to_quantity_map_for_container, $separate_package_for_product_name),
				];
			}
		}
	}

	/**
	 * Adds a product box to an existing box, ensuring dimensions are not swapped.
	 *
	 * @param array $boxDimensions The dimensions of the existing box.
	 * @param array $productDimensions The dimensions of the product box.
	 * @param int $quantity The quantity of the product.
	 *
	 * @return array The updated dimensions of the box.
	 *
	 * @throws InvalidArgumentException if the dimensions or quantity are invalid.
	 */
	public static function add_product_box($boxDimensions, $productDimensions, $quantity)
	{
		// Check that all dimensions are positive and that the quantity is greater than zero.
		foreach (['length', 'width', 'height'] as $dimension) {
			if ($productDimensions[$dimension] <= 0) {
				// throw new InvalidArgumentException("The $dimension must be positive.");
				return $boxDimensions;
			}
		}
		if ($quantity <= 0) {
			// throw new InvalidArgumentException('The quantity must be greater than zero.');
			return $boxDimensions;
		}

		// Calculate box of product with quantity
		// for the smallest dimension, we multiple the value by the quantity. i.e. stacking
		$productBoxDimensions = [
			"length" => $productDimensions["length"],
			"width" => $productDimensions["width"],
			"height" => $productDimensions["height"],
		];
		$smallest_dimesion = self::get_smallest_dimension($productBoxDimensions);
		$productBoxDimensions[$smallest_dimesion] = $productBoxDimensions[$smallest_dimesion] * $quantity;

		// Sort product_box_dimensions . Make sure we have largest to smallest dimensions which could change after stacking quantity.
		arsort($productBoxDimensions);

		// On the smallest value we want to stack. Therefor we add them together.
		$smallest_dimesion = self::get_smallest_dimension($productBoxDimensions);
		foreach ($boxDimensions as $dimension => $value) {
			if ($dimension == $smallest_dimesion) {
				$boxDimensions[$dimension] = $boxDimensions[$dimension] + $productBoxDimensions[$dimension];
			} else {
				$boxDimensions[$dimension] = max($boxDimensions[$dimension], $productBoxDimensions[$dimension]);
			}
		}
		return $boxDimensions;
	}

	private static function get_smallest_dimension($dimensions)
	{
		asort($dimensions); // Sort dimensions to find the smallest
		return key($dimensions); // Return the key of the smallest dimension
	}

	public static function calculate_volume($length, $height, $width)
	{
		if ($length && $height && $width) {
			return round($length * $height * $width, 3, PHP_ROUND_HALF_DOWN);
		}
		return 0;
	}

	private static function should_count($product_id, $package_group_to_count = false)
	{
		$product = wc_get_product($product_id);
		$separate_package_for_product = $product->get_meta('_wildrobot_separate_package_for_product', true) === "yes";
		$package_amount = $product->get_meta('_wildrobot_package_amount', true);

		// Count all items that are not marked to be shipped separately.
		// If no specific package group to count is specified, count the item only if it is not marked to be shipped separately.
		if (empty($package_group_to_count)) {
			return !$separate_package_for_product;
		}

		// If a specific package group to count is specified, count the item only if it is marked to be shipped separately and belongs to the specified package group.
		if ($separate_package_for_product) {
			$option = $product->get_meta('_wildrobot_separate_package_for_product_name', true);
			$separate_package_for_product_name = !empty($option) ? $option :  $product->get_name();
			return $package_group_to_count === $separate_package_for_product_name;
		}
		if (!empty($package_amount) && $package_amount > 0) {
			return false;
		}

		return false;
	}

	public static function get_weight_from_container($product_map, $package_group_to_count = false)
	{
		$weight = 0;
		foreach ($product_map as $product_id => $product_data) {
			$quantity = (int)$product_data['quantity'];
			$variation_id = $product_data['variation_id'];

			$product = wc_get_product($product_id);
			if ($product instanceof WC_Product) {
				if (!self::should_count($product_id, $package_group_to_count)) {
					continue;
				}

				$product_weight = 0;

				if ($variation_id) {
					$variation = wc_get_product($variation_id);
					if ($variation && $variation->has_weight()) {
						$product_weight = (float)$variation->get_weight();
					}
				}

				if ($product_weight === 0 && $product->has_weight()) {
					$product_weight = (float)$product->get_weight();
				}

				$weight += $product_weight * $quantity;
			}
		}
		return $weight;
	}

	public static function get_weight_for_order($order, $package_group_to_count = false)
	{
		$weight    = 0;
		if (sizeof($order->get_items(), false) > 0) {
			foreach ($order->get_items() as $item) {
				// Dont count items that are marked to be shipped separately
				if (!self::should_count($item['product_id'], $package_group_to_count)) {
					continue;
				}
				if ($item['product_id'] > 0) {
					$_product = $order->get_product_from_item($item);

					if (!is_object($_product)) continue; // skip virtual products
					if ($_product->is_virtual()) continue;
					if ($_product->has_weight()) {
						$weight += floatval($_product->get_weight()) * $item['quantity'];
					}
				}
			}
		}
		if ($weight > 0) $weight = $weight; // todo: implement weight packaging
		return apply_filters('logistra_robots_set_order_weight', round($weight, 3, PHP_ROUND_HALF_DOWN), $order->get_id());
	}
	public static function get_weight_for_cart()
	{
		$weight =  round(WC()->cart->get_cart_contents_weight(), 3, PHP_ROUND_HALF_DOWN);
		return $weight;
	}

	public static function get_deliverable_consignments_for_order($order_id)
	{

		$deliverable = [];
		$order = wc_get_order($order_id);
		$items = [];
		$product_ids = [];
		foreach ($order->get_items() as $item_id => $item) {
			$data = $item->get_data();
			$product_ids[] = $data["product_id"];
			$items[] =  [
				"name" => $item->get_name(),
				"quantity" => $item->get_quantity(),
			];
		}
		$products = wc_get_products([
			'include' => $product_ids,
		]);
		$order_shipping_class_ids = [];
		$order_category_ids = [];
		$order_tag_ids = [];
		foreach ($products as $product) {
			$product_data = $product->get_data();
			$order_category_ids = array_merge($order_category_ids, $product_data["category_ids"]);
			$order_tag_ids = array_merge($order_tag_ids, $product_data["tag_ids"]);
			if (!empty($product_data["shipping_class_id"]) && !in_array($product_data["shipping_class_id"], $order_shipping_class_ids)) {
				$order_shipping_class_ids[] = $product_data["shipping_class_id"];
			}
		}
		$deliverable =  [
			"order_id" => $order->get_id(),
			"parent_id" => $order->get_parent_id(),
			"status" => $order->get_status(),
			"date_created" => $order->get_date_created(),
			"date_paid" => $order->get_date_paid(),
			"logistra_robots_sent" => $order->get_meta("logistra-robots-sent", true) === "yes",
			"wildrobot_logistra_picklist_created" => $order->get_meta("wildrobot-logistra-picklist-created", true) === "yes",
			// "shipping_city" => $order->get_shipping_city(),
			// "shipping_postcode" => $order->get_shipping_postcode(),
			// "shipping_country" => $order->get_shipping_country(),
			"shipping_name" => $order->get_formatted_shipping_full_name(),
			"total" => $order->get_total(),
			"currency" => $order->get_currency(),
			"items" => $items,
			"product_ids" => $product_ids,
			"order_shipping_class_ids" => $order_shipping_class_ids,
			"order_category_ids" => array_values(array_unique($order_category_ids)),
			"order_tag_ids" => array_values(array_unique($order_tag_ids)),
		];
		$has_return_consignment = false;
		foreach (Wildrobot_Logistra_Consignment_Order::get_consignment_args_from_order_id($order_id) as $consignment_arg) {
			if ($consignment_arg["is_return_label"]) {
				$has_return_consignment = true;
				continue;
			}
			$deliverable_consignments[] = array_merge($deliverable, [
				"postcode" => $consignment_arg["postcode"],
				"country" => $consignment_arg["country"],
				"city" => $consignment_arg["city"],
				"carrier"   => $consignment_arg["carrier"],
				"first_package" => !empty($consignment_arg["packages"][0]) ? $consignment_arg["packages"][0] : [],
				"more_packages" => !empty($consignment_arg["packages"][1]) ? true : false,
				"printer" => $consignment_arg["printer"],
				"wr_id" => $consignment_arg["wr_id"],
			]);
		}
		foreach ($deliverable_consignments as &$deliverable_consignment) {
			$deliverable_consignment["has_return_consignment"] = $has_return_consignment;
			$hash = md5(serialize($deliverable_consignment));
			$deliverable_consignment["hash"] = $hash;
		}
		unset($deliverable_consignment);
		return $deliverable_consignments;
	}

	public static function wildrobot_check_order_no_consignment_response_function($order_id, $user_id)
	{
		$order = wc_get_order($order_id);
		if ($order) {
			// Check if the order has already been sent for transport
			if ($order->get_meta('logistra-robots-sent') !== 'yes') {
				add_user_meta($user_id, 'wildrobot_check_order_no_consignment_response', $order_id, true);
			}
		}
	}

	public static function display_notice_if_user_has_orders_not_responded()
	{
		$order_id = get_user_meta(get_current_user_id(), 'wildrobot_check_order_no_consignment_response', true);
		if (!empty($order_id)) {
			echo '<div class="notice notice-warning">
					<p>Du har bestilt levering på en ordre som vi ikke har fått svar på fra fraktsystemet. Dette bør sjekkes opp.</p>
					<ul>';
			$order_url = add_query_arg(
				array(
					'wildrobot_dismiss_notice_check_order_no_consignment_response' => '1',
					'_wpnonce'                => wp_create_nonce('wildrobot_dismiss_notice_check_order_no_consignment_response'),
				),
				admin_url('post.php?post=' . $order_id . '&action=edit')
			);
			$dismiss_url = add_query_arg(
				array(
					'wildrobot_dismiss_notice_check_order_no_consignment_response' => '1',
					'_wpnonce'                => wp_create_nonce('wildrobot_dismiss_notice_check_order_no_consignment_response'),
				),
				wc_get_current_admin_url()
			);
			echo '<a href="' . esc_url($order_url) . '" class="button" style="margin-right: 10px;">Gå til ordre: ' . $order_id . '</a>';
			echo '<a href="' . esc_url($dismiss_url) . '" class="button" style="margin-right: 10px;">Fjern varsel</a>';
			echo '</div>';
		}
	}

	public static function wildrobot_logistra_handle_dismiss_notice()
	{
		// Check if the dismissal parameter is set
		if (isset($_GET['wildrobot_dismiss_notice_check_order_no_consignment_response']) && '1' === $_GET['wildrobot_dismiss_notice_check_order_no_consignment_response']) {
			// Verify the nonce for security
			if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'wildrobot_dismiss_notice_check_order_no_consignment_response')) {
				// Update user meta to indicate the notice has been dismissed
				delete_user_meta(get_current_user_id(), 'wildrobot_check_order_no_consignment_response');

				// Optionally, redirect to remove query args from the URL
				// This prevents the dismissal URL from being reused
				wp_redirect(remove_query_arg(array('wildrobot_dismiss_notice_check_order_no_consignment_response', '_wpnonce')));
				exit;
			}
		}
	}
}
// Wildrobot_Logistra_Order_Utils::init();
