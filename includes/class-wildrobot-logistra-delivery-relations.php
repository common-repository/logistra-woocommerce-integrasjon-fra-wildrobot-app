<?php

class Wildrobot_Logistra_Delivery_Relations
{

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public static function delete_relation($instance_id, $method_id, $id)
    {
        Wildrobot_Logistra_DB::delete_delivery_relation($method_id . ":" . $instance_id);
    }
    public static function get_relation($shipping_method_identifier)
    {
        $shipping_method_relation = Wildrobot_Logistra_DB::get_delivery_relation($shipping_method_identifier);
        if (empty($shipping_method_relation)) {
            return [
                'zone' => "Return label for " . $shipping_method_identifier,
                'name' => "Return label for " . $shipping_method_identifier,
                'shipping_method_identifier' => $shipping_method_identifier,
            ];
        };
        return $shipping_method_relation;
    }

    public static function get_all_relations()
    {
        if (!wc_shipping_enabled()) {
            throw new Exception("Woocommerce shipping is not enabled.");
        }

        $shipping_method_relations = Wildrobot_Logistra_DB::get_delivery_relations();
        $data = [];

        $shipping_zones = new WC_Shipping_Zones();
        $zones = $shipping_zones::get_zones();

        if (!empty($zones)) {
            foreach ($zones as $zone) {
                foreach ($zone['shipping_methods'] as $shipping_method) {
                    if ($shipping_method->enabled === "yes") {

                        $shipping_method_data = [
                            'zone' => $zone['zone_name'],
                            'name' => $zone['zone_name'] . ' - ' . $shipping_method->title,
                            'shipping_method_identifier' => $shipping_method->id . ':' . $shipping_method->instance_id,
                            "description" => wp_strip_all_tags($shipping_method->method_description),
                        ];
                        // START handle possible table rate
                        if (get_option('wildrobot_logistra_use_table_rate_integration') == "yes" && $shipping_method->id === "table_rate") {
                            $rates = $shipping_method->get_shipping_rates();
                            foreach ($rates as $rate) {
                                if ($rate->rate_label == "") {
                                    break;
                                }
                                $shipping_method_data_for_rate = $shipping_method_data;
                                $shipping_method_data_for_rate['shipping_method_identifier'] = $shipping_method_data['shipping_method_identifier'] . '#' . $rate->rate_label;
                                $table_rate_relation = self::merge_with_relation($shipping_method_relations, $shipping_method_data_for_rate);
                                array_push($data, $table_rate_relation);
                            }
                        } // END handle possible table rate

                        $relation = self::merge_with_relation($shipping_method_relations, $shipping_method_data);
                        array_push($data, $relation);
                    }
                }
            } // end foreach zone
        }
        // Handle zero zone
        $zero_zone = $shipping_zones::get_zone_by();
        $zero_zone_shipping_methods = $zero_zone->get_shipping_methods(true);
        foreach ($zero_zone_shipping_methods as $zero_zone_shipping_method) {
            if ($zero_zone_shipping_method->enabled === "yes") {
                $shipping_method_data = [
                    'zone' => $zero_zone->get_zone_name(),
                    'name' => $zero_zone->get_zone_name() . ' - ' . $zero_zone_shipping_method->title,
                    'shipping_method_identifier' => $zero_zone_shipping_method->id . ':' . $zero_zone_shipping_method->instance_id,
                ];
                // START handle possible table rate
                if (get_option('wildrobot_logistra_use_table_rate_integration') == "yes" && $zero_zone_shipping_method->id === "table_rate") {
                    $rates = $zero_zone_shipping_method->get_shipping_rates();
                    foreach ($rates as $rate) {
                        if ($rate->rate_label == "") {
                            break;
                        }
                        $shipping_method_data_for_rate = $shipping_method_data;
                        $shipping_method_data_for_rate['shipping_method_identifier'] = $shipping_method_data['shipping_method_identifier'] . '#' . $rate->rate_label;
                        $table_rate_relation = self::merge_with_relation($shipping_method_relations, $shipping_method_data_for_rate);
                        array_push($data, $table_rate_relation);
                    }
                } // END handle possible table rate
                $relation = self::merge_with_relation($shipping_method_relations, $shipping_method_data);
                array_push($data, $relation);
            }
        } // end handle zero zone

        if (get_option('wildrobot_logistra_fallback_freight_product', 'no') === "yes") {
            $shipping_method_data = [
                'zone' => "Alle",
                'name' => "Alle" . ' - ' . "Tilbakefallende Leveranse",
                'shipping_method_identifier' => 'fallbackFreightProduct:0',
            ];
            $relation = self::merge_with_relation($shipping_method_relations, $shipping_method_data);
            array_push($data, $relation);
        }

        return $data;
    }

    private static function merge_with_relation($shipping_method_relations, $shipping_method_data)
    {
        foreach ($shipping_method_relations as $shipping_method_relation) {
            if ($shipping_method_relation["shipping_method_identifier"] === $shipping_method_data['shipping_method_identifier']) {
                return array_merge($shipping_method_relation, $shipping_method_data);
            }
        }
        return $shipping_method_data;
    }
}

// Wildrobot_Logistra_Delivery_Relations::init();
