<?php

class Wildrobot_Logistra_Order_Automation
{

	private $plugin_name;
	private $version;
	private $context;
	private $context_error;

	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->context = array('source' => 'wildrobot-order-automation');
		$this->context_error = array('source' => 'wildrobot-order-automation-error');
	}

	public function setup_wildrobot_order_automation()
	{
		if (get_option("wildrobot_order_automation_active" === "yes")) {
			// $run_count = get_option("wildrobot_logistra_scheduler_run_count", 0);
			// $action = as_get_scheduled_actions([
			// 	"hook" => "run_order_automation",
			// 	"group" => "wildrobot",
			// 	// "args" => ["run_count" => $run_count],
			// ]);
			// if ($run_count > 500) {
			// 	// If the scheduler has run before, we need to remove the action and reschedule it
			// 	return;
			// }
			// if (empty($action)) {
			// 	// Get the current local time
			// 	$time_utc = Wildrobot_Logistra_Utils::get_local_utc_time();
			// 	as_schedule_recurring_action($time_utc, 10, "wildrobot_order_automation", ["run_count" => $run_count], "wildrobot", false);
			// }
		}
	}

	public function run_wildrobot_order_automation($order_id, WC_Order $order)
	{
		if (get_option("wildrobot_order_automation_active", "no") === "yes") {
			// TODO
		}
	}

	private function should_run($rule, $order, $logger)
	{

		// Code to process each rule goes here
		$logger->log("Rule: " . $rule["name"], $this->context);
		// check filters
		if (empty($rule["active"])) {
			$logger->log("Rule[" . $rule["name"] . "]  filtered out. Rule is not active", $this->context);
			return false;
		}
		if (!empty($rule["filter_status"])) {
			if ($order->get_status() !== substr($rule["filter_status"], 3)) {
				$logger->log("Rule[" . $rule["name"] . "]  filtered out. " . $order->get_status() . " is not equal " . $rule["filter_status"], $this->context);
				return false;
			}
		}
		if (!empty($rule["filter_shipping_method"])) {
			foreach ($order->get_shipping_methods() as $shipping_method) {
				$shipping_method_identifier = Wildrobot_Logistra_DB::normalize_some_shipping_method_identifier($shipping_method);
				if ($shipping_method_identifier !== $rule["filter_shipping_method"]) {
					$logger->log("Rule[" . $rule["name"] . "]  filtered out. " . $shipping_method_identifier . " is not equal " . $rule["filter_shipping_method"], $this->context);
					return false;
				}
			}
		}
		if (!empty($rule["filter_transport_product"])) {
			foreach ($order->get_shipping_methods() as $shipping_method) {
				$shipping_method_identifier = 	Wildrobot_Logistra_DB::normalize_some_shipping_method_identifier($shipping_method);
				if (empty($shipping_method_identifier)) {
					$logger->log("Rule[" . $rule["name"] . "]  filtered out. " . $shipping_method_identifier . " is empty", $this->context);
					return false;
				}
				$delivery_relation = Wildrobot_Logistra_DB::get_delivery_relation($shipping_method_identifier);
				if (empty($delivery_relation)) {
					$logger->log("Rule[" . $rule["name"] . "]  filtered out. " . $shipping_method_identifier . " has no delivery relation", $this->context);
					return false;
				}
				if ($delivery_relation["wr_id"] !== $rule["filter_transport_product"]) {
					$logger->log("Rule[" . $rule["name"] . "]  filtered out. " . $delivery_relation["wr_id"] . " is not equal " . $rule["filter_transport_product"], $this->context);
					return false;
				}
			}
		}

		if (!empty($rule["filter_stock_status"])) {
			// $stock_reduced = $order->get_order_stock_reduced();
			// instock status requires that every item is in stock
			// outofstock or backorder status requires only that one item has it
			$has_item_without_instock = false;
			$has_one_outofstock = false;
			$has_one_on_backorder = false;
			$desired_status = $rule["filter_stock_status"];

			foreach ($order->get_items() as $item_id => $item) {
				/** @var WC_Product $product */
				$product = is_callable(array($item, 'get_product')) ? $item->get_product() : false;
				$stock_status = $product->get_stock_status(); // Get the stock status
				if ($desired_status === "outofstock") {
					if ($stock_status === $desired_status) {
						$has_one_outofstock = true;
						$logger->log("Rule[" . $rule["name"] . "]  accepted as " . $item->get_name() . " ordered amount: " . $item->get_quantity() . " has stock status: " . $stock_status . " and only one required status: " . $desired_status, $this->context);
						break;
					}
				} else if ($desired_status === "onbackorder") {
					if ($stock_status !== $desired_status) {
						$has_one_on_backorder = true;
						$logger->log("Rule[" . $rule["name"] . "]  accepted as " . $item->get_name() . " ordered amount: " . $item->get_quantity() . " has stock status: " . $stock_status . " and only one required status: " . $desired_status, $this->context);
						break;
					}
				} else if ($desired_status === "instock") {
					if ($stock_status !== $desired_status) {
						$has_item_without_instock = true;
						$logger->log("Rule[" . $rule["name"] . "]  filtered out. " . $item->get_name() . " ordered amount: " . $item->get_quantity() . " has stock status: " . $stock_status . " and not " . $desired_status, $this->context);
						break;
					}
				}
			}
			if ($has_one_outofstock) {
				// accepted
				$logger->log("Rule[" . $rule["name"] . "]  accepted as one item has stock status: " . $desired_status, $this->context);
			} else if ($has_one_on_backorder) {
				// accepted
				$logger->log("Rule[" . $rule["name"] . "]  accepted as one item has stock status: " . $desired_status, $this->context);
			} else if ($has_item_without_instock) {
				// filtered out so break foreach
				$logger->log("Rule[" . $rule["name"] . "]  filtered out as one item did NOT have stock status: " . $desired_status, $this->context);
				return false;
			} else {
				// accepted
				$logger->log("Rule[" . $rule["name"] . "]  accepted as all items has stock status: " . $desired_status, $this->context);
			}
		}
		return true;
	}
	private function filter_rules($rules, $order, $logger)
	{
		if (!is_array($rules)) {
			return [];
		}
		$filtered_rules = [];
		foreach ($rules as $rule) {
			if ($this->should_run($rule, $order, $logger)) {
				$filtered_rules[] = $rule;
			}
		}
		return $filtered_rules;
	}

	private function run_rule_action($rule, WC_Order $order, WC_Logger $logger)
	{
		$had_error = false;
		$hash = md5(serialize($rule));
		$allready_runned_rule_on_order = $order->get_meta('wildrobot-rules-runned-' . $hash, true);
		// check if this hash is in the array
		if ($allready_runned_rule_on_order) {
			$logger->log("Rule[" . $rule["name"] . "] stopped from running actions. Rule has allready runned on order. ", $this->context);
			return;
		}
		$marked_runned = $order->update_meta_data('wildrobot-rules-runned-' . $hash, $rule["name"]);
		$order->save();
		if (!$marked_runned) {
			$logger->warning("Rule[" . $rule["name"] . "] stopped from running actions. Could not mark rule as runned on order. ", $this->context);
			return;
		}

		if (!empty($rule["action_print_picklist"])) {
			try {
				$logger->log("Rule[" . $rule["name"] . "]  running action: PRINT_PICKLIST", $this->context);
				$picklist_response_message = Wildrobot_Logistra_Picklist::create_picklist($order->get_id());
				$logger->log("Rule[" . $rule["name"] . "]  action: PRINT_PICKLIST response: " . wc_print_r($picklist_response_message, true), $this->context);
				$note = "Ordre automatiserings regel: <b>" . $rule["name"] . "</b>. Printet plukkliste.";
				$order->add_order_note($note);
			} catch (\Throwable $error) {
				$logger->warning("Rule[" . $rule["name"] . "]  action: PRINT_PICKLIST error: " . $error->getMessage(), $this->context);
				$logger->error("Rule[" . $rule["name"] . "]  action: PRINT_PICKLIST error: " . $error->getMessage(), $this->context_error);
				$logger->error(wc_print_r($error, true), $this->context_error);
				$had_error = true;
			}
		}

		if ($had_error) {
			$logger->log("Rule[" . $rule["name"] . "]  action: PRINT_PICKLIST had error. Stopping further actions", $this->context);
			return;
		}
		if (!empty($rule["action_print_shipping_label"])) {
			try {
				$messages = [];
				$errors = [];
				foreach (Wildrobot_Logistra_Consignment_Order::get_consignment_args_from_order_id($order->get_id()) as $consignment_arg) {
					try {
						$consignment = new Wildrobot_Logistra_Consignment($consignment_arg);
						$messages_from_consignment = $consignment->send_backend([
							"title" => "Opprettet av ordre automatisering regel: <b>" . $rule["name"] . "</b>",
						]);
						$messages = array_merge($messages, $messages_from_consignment);
					} catch (\Throwable $error) {
						array_push($errors, $error->getMessage());
					}
				}
				$logger->log("Rule[" . $rule["name"] . "]  action: PRINT_SHIPPING_LABEL response: " . wc_print_r($messages, true), $this->context);
				if (!empty($errors)) {
					throw new Exception(implode("\n", $errors));
				}
			} catch (\Throwable $error) {
				$logger->warning("Rule[" . $rule["name"] . "]  action: PRINT_SHIPPING_LABEL error: " . $error->getMessage(), $this->context);
				$logger->error("Rule[" . $rule["name"] . "]  action: PRINT_SHIPPING_LABEL error: " . $error->getMessage(), $this->context_error);
				$logger->error(wc_print_r($error, true), $this->context_error);
				$had_error = true;
			}
		}

		if ($had_error) {
			$logger->log("Rule[" . $rule["name"] . "]  action: PRINT_SHIPPING_LABEL had error. Stopping further actions", $this->context);
			return;
		}
		if (!empty($rule["action_set_status"])) {
			if (wc_is_order_status($rule["action_set_status"])) {
				$update_status_response = $order->update_status($rule["action_set_status"]);
				if (empty($update_status_response)) {
					$logger->warning("Rule[" . $rule["name"] . "] could not set status action: SET_STATUS response: " . wc_print_r($update_status_response, true), $this->context);
				}
				$note = "Ordre automatiserings regel: <b>" . $rule["name"] . "</b> endret status til: " . wc_get_order_status_name($rule["action_set_status"]);
				$order->add_order_note($note);
			}
		}
		if ($had_error) {
			$logger->log("Rule[" . $rule["name"] . "]  action: SET_STATUS had error. Stopping further actions", $this->context);
			return;
		}
	}
	public function run_wildrobot_order_automation_trigger_status_changed($order_id, $old_status, $new_status)
	{
		$logger = new WC_Logger();
		$this->context = array('source' => 'wildrobot-order-automation');

		if (get_option("wildrobot_order_automation_active", "no") === "yes") {
			if (empty($order_id)) {
				return;
			}
			$order = wc_get_order($order_id);

			if (empty($order)) {
				return;
			}

			$logger->log("Order ID: " . $order_id, $this->context);
			$rules = get_option("wildrobot_order_automation_rules", []);
			$filtered_rules = $this->filter_rules($rules, $order, $logger);
			foreach ($filtered_rules as $rule) {
				$this->run_rule_action($rule, $order, $logger);
			}
		}
	}
}
// Wildrobot_Logistra_Order_Automation::init();
