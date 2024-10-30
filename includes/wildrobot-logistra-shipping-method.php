<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('woocommerce_shipping_init', 'logistra_robots_shipping_method_init');
add_action('woocommerce_shipping_methods', 'add_logistra_robots_shipping_method');


function add_logistra_robots_shipping_method($methods)
{
    $methods['logistra_robots_shipping_method'] = 'WC_Logistra_Robots_Shipping_Method';
    return $methods;
}

function logistra_robots_shipping_method_init()
{
    if (!class_exists('WC_Logistra_Robots_Shipping_Method')) {
        class WC_Logistra_Robots_Shipping_Method extends WC_Shipping_Method
        {

            // public $title;
            // public $description;
            // public $tax_status;
            // public $method_description;
            public $cost;
            public $weight_controlled;
            public $from_weight;
            public $too_weight;
            public $weight_free_treshhold;
            public $free_treshhold;
            public $free_value;
            public $max_value_treshhold;
            public $max_value;
            public $min_value;
            public $min_value_treshhold;
            public $estimate_freight_cost;
            public $estimate_freight_cost_required;
            public $estimate_freight_cost_fixed;
            public $estimate_freight_cost_percentage;
            public $estimate_freight_cost_rouding;
            public $estimate_freight_cost_rouding_base;
            public $estimate_freight_cost_dimensions;
            public $estimate_freight_cost_volume;
            public $debug;
            public $coupon_free_freight;
            public $exclude_classes;
            public $require_classes;
            public $some_classes;
            public $max_length;
            public $max_width;
            public $max_height;
            public $min_length;
            public $min_width;
            public $min_height;
            public $cart_total_based_dimensions;
            public $estimate_freight_cost_base;




            /**
             * Constructor for your shipping class
             *
             * @access public
             * @return void
             */
            public function __construct($instance_id = 0)
            {
                $this->id                       = 'logistra_robots_shipping_method';
                $this->instance_id              = absint($instance_id);
                $this->method_title             = __('Wildrobot fraktmetode', 'logistra_robots');
                $this->method_description       = __('Lag fraktmetoder med parametre for vekt, gratis frakt og estimert kost.', 'logistra_robots'); // 
                // $this->enabled                  = "yes"; // This can be added as an setting but for this example its forced enabled
                // $this->title                    = "Wildrobot fraktmetode";
                // $this->description              = "Lag fraktmetoder med parametre for vekt, gratis frakt og estimert kost.";
                // $this->tax_status               = "taxable";

                $this->supports           = array(
                    'shipping-zones',
                    'instance-settings',
                    'instance-settings-modal',
                );

                $this->init();
            }

            /**
             * Init your settings
             *
             * @access public
             * @return void
             */
            function init()
            {
                // Load the settings API
                $this->instance_form_fields = $this->settings(); // This is part of the settings API. Override the method to add your own settings
                // $this->init_settings(); // This is part of the settings API. Loads settings you previously init.
                $this->method_description       = $this->get_option('method_description');
                $this->title        = $this->get_option('title');
                // $this->description        = $this->get_option('description');
                $this->tax_status        = $this->get_option('tax_status');
                $this->cost        = $this->get_option('cost');
                $this->weight_controlled        = $this->get_option('weight_controlled');
                $this->from_weight        = $this->get_option('from_weight');
                $this->too_weight        = $this->get_option('too_weight');
                $this->weight_free_treshhold        = $this->get_option('weight_free_treshhold');
                $this->free_treshhold        = $this->get_option('free_treshhold');
                $this->free_value        = $this->get_option('free_value');
                $this->max_value_treshhold        = $this->get_option('max_value_treshhold');
                $this->max_value        = $this->get_option('max_value');
                $this->min_value        = $this->get_option('min_value');
                $this->min_value_treshhold        = $this->get_option('min_value_treshhold');
                $this->estimate_freight_cost        = $this->get_option('estimate_freight_cost');
                $this->estimate_freight_cost_required        = $this->get_option('estimate_freight_cost_required');
                $this->estimate_freight_cost_fixed        = $this->get_option('estimate_freight_cost_fixed');
                $this->estimate_freight_cost_percentage        = $this->get_option('estimate_freight_cost_percentage');
                // Migrate
                if ($this->get_option('estimate_freight_cost_rouding') === "yes") {
                    $this->estimate_freight_cost_rouding_base = "nine";
                } else {
                    $this->estimate_freight_cost_rouding_base = $this->get_option('estimate_freight_cost_rouding_base');
                }
                $this->estimate_freight_cost_rouding        = $this->get_option('estimate_freight_cost_rouding'); // DEPRECATED 10.05.24
                $this->estimate_freight_cost_dimensions       = $this->get_option('estimate_freight_cost_dimensions');
                $this->estimate_freight_cost_volume        = $this->get_option('estimate_freight_cost_volume');
                $this->debug        = $this->get_option('debug');
                $this->coupon_free_freight        = $this->get_option('coupon_free_freight');
                $this->exclude_classes        = $this->get_option('exclude_classes');
                $this->require_classes        = $this->get_option('require_classes');
                $this->some_classes        = $this->get_option('some_classes');
                $this->max_length        = $this->get_option('max_length');
                $this->max_width        = $this->get_option('max_width');
                $this->max_height        = $this->get_option('max_height');
                $this->min_length        = $this->get_option('min_length');
                $this->min_width        = $this->get_option('min_width');
                $this->min_height        = $this->get_option('min_height');
                $this->cart_total_based_dimensions        = $this->get_option('cart_total_based_dimensions');
                $this->estimate_freight_cost_base        = $this->get_option('estimate_freight_cost_base');

                // Save settings in admin if you have any defined
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            function calculate_rounded_cost($estimated_cost_with_tax, $rounding_base)
            {
                switch ($rounding_base) {
                    case 'nine':
                        $int_part = intval($estimated_cost_with_tax + 1); # Increment the number and get integer part
                        $increment = 10 - $int_part % 10; # Find the amount needed to increment to next multiple of 10
                        return $int_part + $increment - 1; # Add the increment and subtract 1
                    case 'ten':
                        return ceil($estimated_cost_with_tax / 10) * 10;
                    case 'one':
                        return round($estimated_cost_with_tax);
                    case 'none':
                        return $estimated_cost_with_tax;
                    default:
                        return $estimated_cost_with_tax; // Default to no rounding if an unrecognized value is passed
                }
            }
            /**
             * calculate_shipping function.
             *
             * @access public
             * @param mixed $package
             * @return void
             */
            public function calculate_shipping($package = array())
            {
                if ($this->debug === "yes") {
                    $logger = new WC_Logger();
                    $context = ['source' => 'wildrobot-freight-method'];
                };
                $has_cost = false;
                $valid = true;
                $rate = array(
                    'id'      => $this->get_rate_id(),
                    'label'   => $this->title,
                    'cost'    => 0,
                    'package' => $package,
                );

                if ($this->cost !== '') {
                    $has_cost    = true;
                    $rate['cost'] = $this->cost;
                }

                $product_factory = new WC_Product_Factory();

                // Per product based calculations
                // First we do the permissive checks
                if ($this->some_classes) {
                    $valid = false;
                    foreach ($package['contents'] as $product) {
                        $_product = $product_factory->get_product($product['product_id']);
                        $shipping_class_id = $_product->get_shipping_class_id();
                        if (!empty($shipping_class_id) && in_array($shipping_class_id, $this->some_classes)) {
                            $valid = true;
                        }
                    }
                }
                // Restrictive checks
                $need_product_based_calculations = $this->exclude_classes || $this->require_classes || !empty($this->max_length) || !empty($this->max_width) || !empty($this->max_height) || !empty($this->min_length) || !empty($this->min_width) || !empty($this->min_height);
                if ($need_product_based_calculations) {
                    foreach ($package['contents'] as $product) {
                        $_product = $product_factory->get_product($product['product_id']);
                        if ($this->exclude_classes) {
                            $shipping_class_id = $_product->get_shipping_class_id();
                            if (!empty($shipping_class_id)) {
                                if (in_array($shipping_class_id, $this->exclude_classes)) {
                                    $valid = false;
                                }
                            }
                        }
                        if ($this->require_classes) {
                            $shipping_class_id = $_product->get_shipping_class_id();
                            if (empty($shipping_class_id)) {
                                $valid = false;
                            } else if (!in_array($shipping_class_id, $this->require_classes)) {
                                $valid = false;
                            }
                        }
                        // only do dimension based calculations if it should be based on individual products
                        if ($this->cart_total_based_dimensions === "no") {
                            if (!empty($this->max_length)) {
                                $length = $_product->get_length();
                                if ((int) $length > (int) $this->max_length) {
                                    $valid = false;
                                }
                            }
                            if (!empty($this->max_width)) {
                                $width = $_product->get_width();
                                if ((int) $width > (int) $this->max_width) {
                                    $valid = false;
                                }
                            }
                            if (!empty($this->max_height)) {
                                $height = $_product->get_height();
                                if ((int) $height > (int) $this->max_height) {
                                    $valid = false;
                                }
                            }
                            if (!empty($this->min_length)) {
                                $length = $_product->get_length();
                                if ((int) $length < (int) $this->min_length) {
                                    $valid = false;
                                }
                            }
                            if (!empty($this->min_width)) {
                                $width = $_product->get_width();
                                if ((int) $width < (int) $this->min_width) {
                                    $valid = false;
                                }
                            }
                            if (!empty($this->min_height)) {
                                $height = $_product->get_height();
                                if ((int) $height < (int) $this->min_height) {
                                    $valid = false;
                                }
                            }
                        }
                    }
                }

                // Per cart based calculations
                if ($this->cart_total_based_dimensions === "yes") {
                    $dimensions = Wildrobot_Logistra_Order_Utils::get_dimensions_from_package($package);
                    if ($this->debug === "yes") {
                        $logger->info("Calculated dimensions: " . wc_print_r($dimensions, true), $context);
                    };
                    if (!empty($this->max_length)) {
                        $length = $dimensions["length"];
                        if ((int) $length > (int) $this->max_length) {
                            $valid = false;
                        }
                    }
                    if (!empty($this->max_width)) {
                        $width = $dimensions["width"];
                        if ((int) $width > (int) $this->max_width) {
                            $valid = false;
                        }
                    }
                    if (!empty($this->max_height)) {
                        $height =  $dimensions["height"];
                        if ((int) $height > (int) $this->max_height) {
                            $valid = false;
                        }
                    }
                    if (!empty($this->min_length)) {
                        $length = $dimensions["length"];
                        if ((int) $length < (int) $this->min_length) {
                            $valid = false;
                        }
                    }
                    if (!empty($this->min_width)) {
                        $width = $dimensions["width"];
                        if ((int) $width < (int) $this->min_width) {
                            $valid = false;
                        }
                    }
                    if (!empty($this->min_height)) {
                        $height = $dimensions["height"];
                        if ((int) $height < (int) $this->min_height) {
                            $valid = false;
                        }
                    }
                }

                $weight = Wildrobot_Logistra_Order_Utils::get_weight_for_cart($package);
                if ($this->weight_controlled === "yes") {
                    if ($weight >= $this->from_weight && $weight < $this->too_weight) {
                        $has_cost = true;
                    } else {
                        $valid = false;
                    }
                }


                // If shp still valid lets calc value of order
                if ($valid) {
                    // calculate order total
                    $order_total = 0;
                    $order_total_without_discount = 0;
                    $order_total_without_free_freight = 0;
                    foreach ($package['contents'] as $product) {
                        $order_total = $order_total + $product['line_subtotal'];
                        $order_total = $order_total + $product['line_subtotal_tax'];
                        // Remove products from order total that should not contribute to the order total used for free freight
                        $product_id = $product['product_id'];
                        $_product = wc_get_product($product_id);
                        $no_free_freight = $_product->get_meta('_wildrobot_no_free_freight');
                        if ($no_free_freight === "yes") {
                            $order_total_without_free_freight = $order_total_without_free_freight + $product['line_subtotal'];
                            $order_total_without_free_freight = $order_total_without_free_freight + $product['line_subtotal_tax'];
                        }
                    }
                    // calculate order total without discount
                    $order_total_without_discount = $order_total - $order_total_without_free_freight;
                    $coupons = WC()->cart->get_coupon_discount_totals();
                    foreach ($coupons as $coupon_amount) {
                        if (is_numeric($coupon_amount)) {
                            $order_total_without_discount = $order_total_without_discount - $coupon_amount;
                        }
                    }

                    // Calculate freight cost
                    $should_calculate_freight_cost = $rate["id"] !== null && $this->estimate_freight_cost === "yes";
                    // $current_page_id = intval($_REQUEST["page_id"]);
                    // $is_page_good_for_estimate = $current_page_id === wc_get_page_id('cart') || $current_page_id === wc_get_page_id('checkout');
                    if ($should_calculate_freight_cost) {
                        try {
                            $country = $package["destination"]["country"];
                            $postcode = $package["destination"]["postcode"];
                            $city = $package["destination"]["city"];
                            $address_1 = $package["destination"]["address"];
                            if (empty($country)) {
                                if ($this->estimate_freight_cost_required === "yes") {
                                    $valid = false;
                                }
                                throw new Exception("No country to estimate freight cost");
                            }
                            if (empty($postcode)) {
                                if ($this->estimate_freight_cost_required === "yes") {
                                    $valid = false;
                                }
                                throw new Exception("No postcode to estimate freight cost");
                            }

                            $delivery_relation = Wildrobot_Logistra_DB::get_delivery_relation_with_transport_agreement($rate["id"]);
                            if (empty($delivery_relation)) {
                                if ($this->estimate_freight_cost_required === "yes") {
                                    $valid = false;
                                }
                                if ($this->debug === "yes") {
                                    $logger->info("Du må koble et leveranse produkt til fraktmetode " . $this->title . " for å gjør frakt estimering på den.", $context);
                                }
                                throw new Exception("Shipping method has not been properly setup to estimate freight cost");
                            }

                            if ($this->estimate_freight_cost_volume === "yes" || $this->estimate_freight_cost_dimensions === "yes") {
                                if (empty($dimensions)) {
                                    $dimensions = Wildrobot_Logistra_Order_Utils::get_dimensions_from_package($package);
                                }
                            }
                            $packages = [];
                            $counter = 0;
                            $counted_groups = [];
                            $items = $package['contents'];
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
                            // Add base package
                            $base_package_dimensions = Wildrobot_Logistra_Order_Utils::get_dimensions_from_container($product_map);
                            $packages[$counter] = [
                                "length"      => $base_package_dimensions["length"],
                                "height"      => $base_package_dimensions["height"],
                                "width"       => $base_package_dimensions["width"],
                                "volume"      => Wildrobot_Logistra_Order_Utils::calculate_volume($base_package_dimensions["length"], $base_package_dimensions["height"], $base_package_dimensions["width"]),
                                "amount"      => 1,
                                "description" => "",
                                "type"        => "package",
                                "weight"      => Wildrobot_Logistra_Order_Utils::get_weight_from_container($product_map)
                            ];

                            // Add Wildrobot packages based on products
                            foreach ($package['contents'] as $product) {
                                $pid = isset($product['variation_id']) && $product['variation_id'] > 0 ? $product['variation_id'] : $product['product_id'];
                                Wildrobot_Logistra_Order_Utils::calculate_package_amount($packages, $counter, $counted_groups, $pid, $product['quantity'], $product_map);
                            }
                            foreach ($packages as $package) {
                                if ($this->estimate_freight_cost_volume !== "yes") {
                                    unset($package["volume"]);
                                }
                                if ($this->estimate_freight_cost_dimensions !== "yes") {
                                    unset($package["length"]);
                                    unset($package["width"]);
                                    unset($package["height"]);
                                }
                            }
                            $consignment_args = [
                                "estimate" => true,
                                "country" => $country,
                                "postcode" => $postcode,
                                "city" => $city,
                                "wr_id" => $delivery_relation["wr_id"],
                                "services" => $delivery_relation["services"],
                                "carrier" => $delivery_relation["carrier"],
                                "terms_of_delivery_code" => $delivery_relation["terms_of_delivery_code"],
                                "terms_of_delivery_name" => $delivery_relation["terms_of_delivery_name"],
                                "terms_of_delivery_customer_number" => $delivery_relation["terms_of_delivery_customer_number"],
                                "export_reason" => $delivery_relation["export_reason"],
                                "export_type" => $delivery_relation["export_type"],
                                "bring_priority" => $delivery_relation["bring_priority"],
                                "consignee" => [
                                    "name" => "estimate freight cost",
                                    "country" => $country,
                                    "postcode" => $postcode,
                                    "phone" => strval("12345678"),
                                    "contact-person" => strval("Only for estimation"),
                                    "address1" => $address_1,
                                ],
                                "packages" => $packages,
                                "total_amount" => $order_total,
                            ];


                            $consignment = new Wildrobot_Logistra_Consignment($consignment_args);
                            $xml = $consignment->to_estimate();
                            if ($this->debug === "yes") {
                                $logger->info("Freight method: " . $this->title . "-" . strip_tags($this->method_description), $context);
                                $logger->info("Freight cost REQUST: " . wc_print_r($xml, true), $context);
                            }
                            $estimated_costs = Wildrobot_Logistra_Cargonizer::estimate_consignment($xml);
                            $cost_keys = [
                                'net' => 'net-amount',
                                'gross' => 'gross-amount',
                                'estimated' => 'estimated-cost'
                            ];
                            $estimatated_cost_key = $cost_keys[$this->estimate_freight_cost_base] ?? 'net-amount';

                            if ($this->debug === "yes") {
                                $logger->info("Freight cost RESPONSE: " . wc_print_r($estimated_costs, true), $context);
                                $logger->info("Will estimate cost from: " . $estimatated_cost_key, $context);
                            };

                            if (!array_key_exists($estimatated_cost_key, $estimated_costs)) {
                                if ($this->estimate_freight_cost_required === "yes") {
                                    $valid = false;
                                }
                                throw new Exception("Not valid respons from Logistra when estimating freight cost");
                            }
                            $estimated_cost_value = $estimated_costs[$estimatated_cost_key];
                            if (!is_numeric($estimated_cost_value) || $estimated_cost_value <= 0) {
                                if ($this->estimate_freight_cost_required === "yes") {
                                    $valid = false;
                                }
                                if ($this->debug === "yes") {
                                    $logger->info('Du har kanskje ikke avtalepris (netto) tilgjengelig i API. Vurder å bytt til listepriser (brutto) på fraktmetoden.', $context);
                                };
                                throw new Exception("Not valid response from Logistra when estimating freight cost, cost was <= 0 or not a number");
                            }
                            $estimated_cost = floatval($estimated_cost_value); // Parse as decimal
                            if ($this->debug === "yes") {
                                $logger->info('Estimated cost before fixed ' . $estimated_cost, $context);
                            };
                            $estimated_cost = floatval($this->estimate_freight_cost_fixed) + $estimated_cost;
                            if ($this->debug === "yes") {
                                $logger->info('Estimated cost after fixed ' . $estimated_cost, $context);
                            };
                            if (intval($this->estimate_freight_cost_percentage) !== 0) {
                                $cost_value_from_percentage = ($estimated_cost * (absint($this->estimate_freight_cost_percentage) / 100));
                                if (intval($this->estimate_freight_cost_percentage) < 0) {
                                    $estimated_cost -= $cost_value_from_percentage;
                                } else {
                                    $estimated_cost += $cost_value_from_percentage;
                                }
                            }

                            if ($this->debug === "yes") {
                                $logger->info('Estimated cost after percentage ' . $estimated_cost, $context);
                            };

                            if ($this->estimate_freight_cost_rouding === "yes") {
                                $tax_rate = WC_Tax::get_shipping_tax_rates();
                                if (isset($tax_rate[1]) && isset($tax_rate[1]["rate"])) {
                                    $tax_rate_number = $tax_rate[1]["rate"];
                                } else {
                                    // Handle the case where $tax_rate[1]["rate"] does not exist
                                    $tax_rate_number = 25; // Default to 25% if no tax rate is found
                                }
                                $estimated_cost_with_tax = $estimated_cost * (1 + $tax_rate_number / 100); // Add tax before rounding
                                $estimated_cost_with_tax_after_rounding = $this->calculate_rounded_cost($estimated_cost_with_tax, "nine");
                                $estimated_cost =  $estimated_cost_with_tax_after_rounding / (1 + $tax_rate_number / 100); // Calculate cost without tax after rounding
                                if ($this->debug === "yes") {
                                    $logger->info('Estimated cost with tax before rounding:   ' . $estimated_cost_with_tax, $context);
                                    $logger->info('Estimated cost with tax after rounding:    ' . $estimated_cost_with_tax_after_rounding, $context);
                                    $logger->info('Estimated cost after rounding without tax: ' . $estimated_cost, $context);
                                };
                            } else if (!empty($this->estimate_freight_cost_rouding_base)) {
                                $tax_rate = WC_Tax::get_shipping_tax_rates();
                                if (isset($tax_rate[1]) && isset($tax_rate[1]["rate"])) {
                                    $tax_rate_number = $tax_rate[1]["rate"];
                                } else {
                                    // Handle the case where $tax_rate[1]["rate"] does not exist
                                    $tax_rate_number = 25; // Default to 25% if no tax rate is found
                                }
                                $estimated_cost_with_tax = $estimated_cost * (1 + $tax_rate_number / 100); // Add tax before rounding
                                $estimated_cost_with_tax_after_rounding = $this->calculate_rounded_cost($estimated_cost_with_tax, $this->estimate_freight_cost_rouding_base);
                                $estimated_cost =  $estimated_cost_with_tax_after_rounding / (1 + $tax_rate_number / 100); // Calculate cost without tax after rounding
                                if ($this->debug === "yes") {
                                    $logger->info('Estimated cost with tax before rounding:   ' . $estimated_cost_with_tax, $context);
                                    $logger->info('Estimated cost with tax after rounding:    ' . $estimated_cost_with_tax_after_rounding, $context);
                                    $logger->info('Estimated cost after rounding without tax: ' . $estimated_cost, $context);
                                };
                            }

                            $rate['cost'] = $estimated_cost;
                            $has_cost = true;
                        } catch (\Throwable $error) {
                            if ($this->estimate_freight_cost_required === "yes") {
                                $valid = false;
                            }
                            if ($this->debug === "yes") {
                                $logger->error($error->getMessage(), $context);
                            };
                        }
                    }
                    $weight_free_treshhold = intval($this->weight_free_treshhold);
                    if ($weight_free_treshhold  > 0) {
                        if ($this->debug === "yes") {
                            $logger->info("Vekt: " . $weight . " Vektfrigrense: " . $weight_free_treshhold, $context);
                        };
                        if ($weight < $weight_free_treshhold) {
                            $rate['cost'] = 0;
                            $rate['label'] = $rate['label'] . ' ' . apply_filters('logistra_robots_free_label_title_extended', __('(Gratis)', 'logistra-robots'));
                        }
                    }
                    if ($this->free_treshhold === "yes") {
                        $notice = get_option('wildrobot_logistra_free_freight_notice', null) === "yes";
                        $almost_value = get_option('wildrobot_logistra_free_freight_almost_value', 0);



                        if ($order_total_without_discount >= floatval($this->free_value)) {
                            if ($notice === "yes") {
                                wc_clear_notices();
                                $message = apply_filters('logistra_robots_free_freight_message', sprintf(__('Du har nå gratis frakt på %s', 'logistra-robots'), $rate['label']));
                                wc_add_notice($message, 'success');
                            }
                            $rate['cost'] = 0;
                            $rate['label'] = $rate['label'] . ' ' . apply_filters('logistra_robots_free_label_title_extended', __('(Gratis)', 'logistra-robots'));
                            $has_cost = true;
                        } else if ($notice && $almost_value > 0 &&  $order_total_without_discount >= floatval($this->free_value) - $almost_value) {
                            wc_clear_notices();
                            $message = apply_filters('logistra_robots_free_freight_message', sprintf(__('%s kr fra fraktfri levering. <a href="%s">Fortsett å handle</a>', 'logistra-robots'), round(floatval($this->free_value) - $order_total_without_discount, 0, PHP_ROUND_HALF_UP), wc_get_page_permalink("shop")));
                            wc_add_notice($message, 'notice');
                        }
                    }

                    // If customers attaches a coupon with free freight and store has put this option on, set cost to 0.
                    if ($this->coupon_free_freight === "yes") {
                        $coupons = WC()->cart->get_coupons();
                        if ($coupons) {
                            foreach ($coupons as $code => $coupon) {
                                if ($coupon->is_valid() && $coupon->get_free_shipping()) {
                                    if ($this->debug === "yes") {
                                        $logger->info("Fant fri frakt kupong kode", $context);
                                    };
                                    $rate['cost'] = 0;
                                    break;
                                }
                            }
                        }
                    }
                    if ($this->min_value_treshhold === "yes" && $order_total < floatval($this->min_value)) {
                        if ($this->debug === "yes") {
                            $logger->info("Fraktmetode fjernet fra valg fordi den er under verdi. Total " . $order_total . " Max: " . floatval($this->min_value), $context);
                        };
                        $valid = false;
                    }

                    if ($this->max_value_treshhold === "yes" && $order_total > floatval($this->max_value)) {
                        if ($this->debug === "yes") {
                            $logger->info("Fraktmetode fjernet fra valg fordi den er over max verdi. Total " . $order_total . " Max: " . floatval($this->max_value), $context);
                        };
                        $valid = false;
                    }
                }


                if ($has_cost && $valid) {
                    $this->add_rate($rate);
                    do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
                }
            }

            function settings()
            {
                $shipping_classes = WC()->shipping()->get_shipping_classes();
                $shipping_classes_options = [];
                foreach ($shipping_classes as $shipping_class) {
                    $shipping_classes_options[$shipping_class->term_id] = $shipping_class->name;
                }
                return array(
                    'title' => array(
                        'title' => __('Tittel', 'logistra-robots'),
                        'type' => 'text',
                        'description' => __('Dette er hva kunden ser i utsjekkingsprosessen.', 'logistra-robots'),
                        'default' => __('Wildrobot fraktmetode (husk å endre tittel).', 'logistra-robots')
                    ),
                    'method_description' => array(
                        'title' => __('Description', 'logistra-robots'),
                        'type' => 'text',
                        'description' => __('Forklaring på fraktmetoden. Dette ser IKKE kunden.', 'logistra-robots'),
                        'default' => __("Lag fraktmetoder med parametre for vekt, gratis frakt og estimert kost.", 'logistra-robots')
                    ),
                    'tax_status' => array(
                        'title'                 => __('Merverdiavgift (MVA)', 'logistra-robots'),
                        'type'                  => 'select',
                        'class'                 => 'wc-enhanced-select',
                        'default'               => 'taxable',
                        'options'               => array(
                            'taxable'               => __('Avgiftspliktig', 'logistra-robots'),
                            'none'                  => _x('Ingen', 'Tax status', 'logistra-robots')
                        )
                    ),
                    'cost' => array(
                        'title'                 => __('Fraktpris', 'logistra-robots'),
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Pris for fraktmetoden.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'exclude_classes' => array(
                        'title'                 => __('Ekskluder fraktklasser', 'logistra-robots'),
                        'type'                  => 'multiselect',
                        'description'           => __('Hvis du velger klasser her, vil fraktmetoden bli fjernet i handlekurven og utsjekk dersom et av produktene har denne klassen. For å fjerne markering, hold inne CTRL (Windows/Linux) / Command (MacOS) og klikk med musen.', 'logistra-robots'),
                        'class'                 => 'wc-enhanced-select',
                        'default'               => [],
                        'options'               => $shipping_classes_options,
                        'desc_tip'              => true
                    ),
                    'require_classes' => array(
                        'title'                 => __('Påkrev fraktklasser', 'logistra-robots'),
                        'type'                  => 'multiselect',
                        'description'           => __('Hvis du velger klasser her vil fraktmetoden kun bli synlig dersom ALLE produktene i handlekurven har en av disse klassene. For å fjerne markering, hold inne CTRL (Windows/Linux) / Command (MacOS) og klikk med musen.', 'logistra-robots'),
                        'class'                 => 'wc-enhanced-select',
                        'default'               => [],
                        'options'               => $shipping_classes_options,
                        'desc_tip'              => true
                    ),
                    'some_classes' => array(
                        'title'                 => __('Noen fraktklasser', 'logistra-robots'),
                        'type'                  => 'multiselect',
                        'description'           => __('Hvis du velger klasser her vil fraktmetoden kun bli synlig dersom en av produktene i handlekurven har denne klassen. For å fjerne markering, hold inne CTRL (Windows/Linux) / Command (MacOS) og klikk med musen.', 'logistra-robots'),
                        'class'                 => 'wc-enhanced-select',
                        'default'               => [],
                        'options'               => $shipping_classes_options,
                        'desc_tip'              => true
                    ),
                    'weight_controlled' => array(
                        'title'                 => __('Vektstyrt', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Hvis valgt, vises fraktmetoden kun for ordrer hvor samlet vekt er innenfor angitt vekt-intervall. Dette krever at du har angitt vekt på dine produkter i nettbutikken.', 'logistra-robots'),
                        // 'description'           => __('Skal frakmetoden styres av vekt på ordren?', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => false,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'from_weight' => array(
                        'title'                 => 'Fra vekt(' . get_option('woocommerce_weight_unit') . ')',
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => 'Fra vekt er inklusiv. Skriver du 1, vil den altså gjelde for et produkt på akkurat 1 ' . get_option('woocommerce_weight_unit') . '. Vekt styrt må være aktivert.',
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'too_weight' => array(
                        'title'                 => 'Til vekt (' . get_option('woocommerce_weight_unit') . ')',
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Til vekt er eksklusiv. Skriver du 2, vil den gjelde opp til 2 og IKKE for et produkt på akkurat 2 " . get_option('woocommerce_weight_unit') . ". Vekt styrt må være aktivert.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'weight_free_treshhold' => array(
                        'title'                 => 'Fraktfri levering under vekt (' . get_option('woocommerce_weight_unit') . ')',
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => '0 verdi her vil deaktivere dette valget. Dersom vekten er under dette tallet vil kostnaden på ordren gå til 0. Oppgis i nettbutikkens vektenhet(' . get_option('woocommerce_weight_unit') . ').',
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'free_treshhold' => array(
                        'title'                 => __('Fraktfri levering over orderverdi', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Hvis valgt, vil pakken sendes fraktfritt om ordren overstiger angitt ordreverdi. Sett verdi i neste felt.', 'logistra-robots'),
                        'description'           => __('Inkludert MVA dersom du har valgt avgift på frakt. Ekskludert MVA dersom du har ikke-avgift.', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'free_value' => array(
                        'title'                 => __('Fraktfritt levert om ordren overstiger (' . get_option('woocommerce_currency') . ')', 'logistra-robots'),
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Dersom verdien av ordren blir lik eller over dette tallet vil kostnaden på ordren gå til 0.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'min_value_treshhold' => array(
                        'title'                 => __('Skjul fraktmetode under beløp', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Hvis valgt, skjules fraktmetoden dersom verdien av er under angitt beløp.', 'logistra-robots'),
                        'description'           => __('Med denne instillingen kan du skjule fraktmetoden om ordren er under angitt verdi.', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'min_value' => array(
                        'title'                 => __('Skjul fraktmetoden om ordren er under (' . get_option('woocommerce_currency') . ')', 'logistra-robots'),
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Dersom verdien av ordren er under dette tallet vil ikke fraktmetoden bli tilgjengelig for kunde.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'max_value_treshhold' => array(
                        'title'                 => __('Skjul fraktmetode over beløp', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Hvis valgt, skjules fraktmetoden dersom verdien av ordren overstiger angitt beløp.', 'logistra-robots'),
                        'description'           => __('Noen transportprodukter som f eks "Pakke i postkassen", "Pose på døren",  "Mypack Home Small" eller "helthjem" er ikke beregenet for kostbare forsendelser. Med denne instillingen kan du skjule fraktmetoden for ordre over hvis verdi.', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'max_value' => array(
                        'title'                 => __('Skjul fraktmetoden om ordren overstiger (' . get_option('woocommerce_currency') . ')', 'logistra-robots'),
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Dersom verdien av ordren blir over dette tallet vil ikke fraktmetoden bli tilgjengelig for kunde.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'coupon_free_freight' => array(
                        'title'                 => __('Mulighet for kupong', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Hvis valgt, leveres ordren fraktfritt dersom kupong med gratis frakt legges til av kunden.', 'logistra-robots'),
                        // 'description'           => __('', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'estimate_freight_cost' => array(
                        'title'                 => __('Benytt reel estimert kostnad', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Ved valg vil reelle fraktkostnader bli brukt for denne fraktmetoden. Dette krever støtte fra transportøren basert på mottakers postnummer og produktvekt/volum. Merk at dette kan påvirke nettbutikkens hastighet grunnet eksterne forespørsler. MVA legges til på estimert fraktpris i de fleste tilfeller.', 'logistra-robots'),
                        'description'           => __('Krever at fraktmetoden er relatert til en leveranse i Wildrobot innstillinger. Gjør et API-kall til Logistra når kunden legger til varer for å prøve å få en estimert pris på leveransen som puttes inn i kostnaden til fraktmetoden.', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'estimate_freight_cost_required' => array(
                        'title'                 => __('Estimer kostnad: Påkrev estimat', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Skjuler fraktmetoden dersom det ikke finnes et estimat.', 'logistra-robots'),
                        'description'           => __('Hvis api-kallet ikke kan gjøre et kost estimat f eks pga manglende postnummer, innstillinger, for høy vekt, volum eller andre årsaker. Så skjuler den denne fraktmetoden fra å bli valgt.', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'estimate_freight_cost_base' => array(
                        'title'                 => __('Estimer fraktkost basert på', 'logistra-robots'),
                        'type'                  => 'select',
                        'class'                 => 'wc-enhanced-select',
                        'default'               => 'net',
                        'description'           => __('Verifiser at dine avtalepriser kan presenteres med Logistra/Profrakt før avtalepris legges til grunn i checkout. Benytt eventuelt transportørens listepriser, eventuelt med negativ verdi i prosentendingen dersom du har en kjent rabatt.', 'logistra-robots'),
                        'options'               => array(
                            'net'                  => __('Din avtalepris (netto)', 'logistra-robots'),
                            'gross'               => __('Transportrens listepris (brutto)', 'logistra-robots'),
                            'estimated'            => __('Din avtalepris inkludert transportørenes faste tillegg (kun Profrakt)', 'logistra-robots')
                        )
                    ),
                    'estimate_freight_cost_fixed' => array(
                        'title'                 => __('Estimer kostnad: Manuell endring på estimat, angitt i ' . get_option('woocommerce_currency') . ' (Kan være negativ)', 'logistra-robots'),
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => 'Angis eksklusive MVA (i de fleste tilfeller). Dersom du vil legge til 100 ' . get_option('woocommerce_currency') . ', så skriver du 80 her som tilsvarer 100 med 25% MVA. Kan være negativ om du ønsker å trekke ifra.',
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'estimate_freight_cost_percentage' => array(
                        'title'                 => __('Estimer kostnad: Manuell endring på estimat, angitt i prosent (Kan være negativ)', 'logistra-robots'),
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Legges til før MVA kalkuleres. Dersom du vil legge til 30%, så skriver du 30 her. Dersom du vil trekke fra 30%, så skriver du -30 her.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'estimate_freight_cost_rouding' => array(
                        'title'                 => __('UTGÅR: Estimer kostnad: Avrund estimat', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Fjern avhukning på denne for å bruke alternativet under. Skal estimert kostnad bli opprundet til nærmeste 9.', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => false,
                        'custom_attributes'     => array(),
                        'disabled'              => ($this->get_option('estimate_freight_cost_rouding') === "yes") ? false : true,
                    ),
                    'estimate_freight_cost_rouding_base' => array(
                        'title'                 => __('NY: Estimer kostnad: Avrund estimat', 'logistra-robots'),
                        'type'                  => 'select',
                        'class'                 => 'wc-enhanced-select',
                        'default'               => 'one',
                        'description'           => __('Dette antar at du viser priser inklusive mva. og har satt en spesifikk MVA-kode for frakt.', 'logistra-robots'),
                        'options'               => array(
                            'nine'                  => "Opp til nærmeste 9",
                            'ten'                   => "Opp til nærmeste 10",
                            'one'                   => "Nærmeste 1",
                            'none'                  => __('Ingen avrunding', 'logistra-robots'),
                        )
                    ),
                    'estimate_freight_cost_dimensions' => array(
                        'title'                 => __('Estimer kostnad: Beregn dimensjoner', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Bruker estimerte dimensjoner av alle produkter i handlekurven for å bestemme fraktkostnad. Dette tar hensyn til produktets lengde, bredde, og høyde. Vær oppmerksom på at dette kan være unøyaktig hvis flere produkter sendes i samme eske.', 'logistra-robots'),
                        'description'           => __('Vil sende inn kalkulerte dimensjoner av alle produkter i handlekurven for å få et estimat på pris. OBS: Bruker kun dimensjoner fra produkter hvor det er angitt lengde, bredde og høyde. Merk at det er pakningsstørrelse som tas hensyn til i fraktsammenheng. Dersom flere produkter sendes i en annen eske, er det denne eskens størrelse som er den korrekte å benytte i fraktsammenheng. Woocommerce har imidlertid ingen egen register for å kunne angi slike esker. Du bør derfor være oppmerksom på at denne beregningen i enkelte tilfeller kan være feil hvis flere produkter sendes i samme eske. Les mer om fraktberegningens vekt på transportørens hjemmeside hvis du er usikker på noe i denne sammenheng', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'estimate_freight_cost_volume' => array(
                        'title'                 => __('Estimer kostnad: Beregn volum', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Bruker estimert volum av alle produkter i handlekurven for å bestemme fraktkostnad. Dette tar hensyn til produktets volum basert på lengde, bredde, og høyde. Vær oppmerksom på at dette kan være unøyaktig hvis flere produkter sendes i samme eske.', 'logistra-robots'),
                        'description'           => __('Vil sende inn kalkulerte volumer av alle produkter i handlekurven for å få et estimat på pris. OBS: Bruker kun volum fra produkter hvor det er angitt lengde, bredde og høyde. Merk at det er pakningsstørrelse som tas hensyn til i fraktsammenheng. Dersom flere produkter sendes i en annen eske, er det denne eskens størrelse som er den korrekte å benytte i fraktsammenheng. Woocommerce har imidlertid ingen egen register for å kunne angi slike esker. Du bør derfor være oppmerksom på at denne beregningen i enkelte tilfeller kan være feil hvis flere produkter sendes i samme eske. Les mer om fraktberegningens vekt på transportørens hjemmeside hvis du er usikker på noe i denne sammenheng.', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'cart_total_based_dimensions' => array(
                        'title'                 => __('Bruk estimert dimensjon av hele handlekurven i maksimal og minimums krav.', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Vil bruke et kalkulert estimat av alle produkter i handlekurven for maksimal og minimums dimensjons innstillingene nedenfor.', 'logistra-robots'),
                        // 'description'           => __('', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),
                    'max_length' => array(
                        'title'                 => 'Maksimal lengde (' . get_option('woocommerce_dimension_unit') . ')',
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Dersom et av produktene i handlekurven overstiger denne lengden, vil fraktmetoden bli fjernet fra valg. Ved 0 gjøres det ingen sjekk.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'max_width' => array(
                        'title'                 => 'Maksimal bredde(' . get_option('woocommerce_dimension_unit') . ')',
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Dersom et av produktene i handlekurven overstiger denne bredden, vil fraktmetoden bli fjernet fra valg. Ved 0 gjøres det ingen sjekk.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'max_height' => array(
                        'title'                 => 'Maksimal høyde(' . get_option('woocommerce_dimension_unit') . ')',
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Dersom et av produktene i handlekurven overstiger denne høyden, vil fraktmetoden bli fjernet fra valg. Ved 0 gjøres det ingen sjekk.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'min_length' => array(
                        'title'                 => 'Minimal lengde (' . get_option('woocommerce_dimension_unit') . ')',
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Dersom et av produktene i handlekurven understiger denne lengden, vil fraktmetoden bli fjernet fra valg. Ved 0 gjøres det ingen sjekk.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'min_width' => array(
                        'title'                 => 'Minimal bredde(' . get_option('woocommerce_dimension_unit') . ')',
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Dersom et av produktene i handlekurven understiger denne bredden, vil fraktmetoden bli fjernet fra valg. Ved 0 gjøres det ingen sjekk.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'min_height' => array(
                        'title'                 => 'Minimal høyde(' . get_option('woocommerce_dimension_unit') . ')',
                        'type'                  => 'number',
                        'placeholder'           => '',
                        'description'           => "Dersom et av produktene i handlekurven understiger denne høyden, vil fraktmetoden bli fjernet fra valg. Ved 0 gjøres det ingen sjekk.",
                        'default'               => '0',
                        'desc_tip'              => true
                    ),
                    'debug' => array(
                        'title'                 => __('Feilsøking', 'logistra-robots'),
                        'type'                  => 'checkbox',
                        'class'                 => '',
                        'css'                   => 'width: 16px; paddding-top: 10px;',
                        'placeholder'           => '',
                        'label'                 => __('Vil logge alle forspørsler i WC -> Status -> Logs -> wildrobot-freight-method', 'logistra-robots'),
                        // 'description'           => __('', 'logistra-robots'),
                        'default'               => 'no',
                        'desc_tip'              => true,
                        'custom_attributes'     => array(),
                        'disabled'              => false,
                    ),

                );
            } // End init_form_fields()
        }
    }
}
