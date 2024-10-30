<?php

class Wildrobot_Logistra_Deprecated_V6
{
    /**
     * @deprecated deprecated since version 6.0
     */
    public static function migrate_shipping_method_relation_from_below_version_5()
    {

        if (!wc_shipping_enabled()) {
            throw new Exception("Woocommerce shipping is not enabled.");
        }
        $legacy_data = [];

        $shipping_zones = new WC_Shipping_Zones();
        $zones = $shipping_zones::get_zones();
        if (!empty($zones)) {
            foreach ($zones as $zone) {
                foreach ($zone['shipping_methods'] as $shipping_method) {
                    $legacy_data = array_merge(
                        $legacy_data,
                        self::get_shipping_method_data(
                            $shipping_method,
                            $zone['zone_name']
                        )
                    );
                }
            } // end foreach zone
        } // end zone

        // handle shipping methods without zone
        $zero_zone = $shipping_zones::get_zone_by();
        $zero_zone_shipping_methods = $zero_zone->get_shipping_methods(true);
        foreach ($zero_zone_shipping_methods as $zero_zone_shipping_method) {
            if ($zero_zone_shipping_method->enabled === "yes") {
                $legacy_data = array_merge(
                    $legacy_data,
                    self::get_shipping_method_data(
                        $zero_zone_shipping_method,
                        $zero_zone->get_zone_name()
                    )
                );
            }
        } // end no zone 

        // fallbackFreightProduct special case
        if (get_option('logistra-robots-fallback-freight-product', 'no') === "yes") {
            $zone_name = "Alle";
            $title = "Tilbakefallende Leveranse";
            $id = "fallbackFreightProduct";
            $instance_id = "0";
            $legacy_data = array_merge(
                $legacy_data,
                self::get_shipping_method_data_without_shipping_method(
                    $zone_name,
                    $title,
                    $id,
                    $instance_id
                )
            );
        } // end fallback

        $updateable_data = [];
        foreach ($legacy_data as $legacy_data_item) {
            if (!empty($legacy_data_item["rel"]) && !empty($legacy_data_item["transportAgreementId"]) && !empty($legacy_data_item["id"])) {
                // $return_label_settings = Logistra_Robots_Options::get_shipping_method_return_label($legacy_data_item["id"]);
                $export_settings = self::get_shipping_method_export_settings($legacy_data_item["id"]);
                $priority_settings = self::get_shipping_method_priority_settings($legacy_data_item["id"]);
                $shipping = Wildrobot_Logistra_DB::get_method_and_instance_from_shipping_method($legacy_data_item["id"]);
                $electronic_invoice_settings = [
                    'reasonForExport' =>  get_option("logistra-robots-shipping-relation-dhl-eletronic-invoice-settings-reason-for-export-" . $shipping['method_id'] . ':' . $shipping['instance_id'], null),
                    'typeOfExport' =>  get_option("logistra-robots-shipping-relation-dhl-eletronic-invoice-settings-type-of-export-" . $shipping['method_id'] . ':' . $shipping['instance_id'], null),
                ];
                // FIX to avoid invalid data from old formatting to get into new structure.
                $services = [];
                if (!is_array($legacy_data_item["services"])) {
                    $legacy_data_item["services"] = [];
                }
                foreach ($legacy_data_item["services"] as $service_key => $service_value) {
                    if (is_object($service_value)) {
                        if (property_exists($service_value, "identifier") &&  is_string($service_value->identifier)) {
                            array_push($services,  $service_value->identifier);
                        }
                    }
                    if (is_array($service_value)) {
                        if (array_key_exists("identifier", $service_value) &&  is_string($service_value["identifier"])) {
                            array_push($services,  $service_value["identifier"]);
                        }
                    }
                    if (is_string($service_value)) {
                        array_push($services,  $service_value);
                    }
                }
                array_push($updateable_data, [
                    "shipping_method_identifier" => $legacy_data_item["id"],
                    "wr_id" => $legacy_data_item["transportAgreementId"] . ":" . $legacy_data_item["rel"],
                    "services" => $services,
                    "printer" => $legacy_data_item["printer"] ?? "",
                    "print_time" => $legacy_data_item["printTime"] ?? "",
                    "transfer_time" => $legacy_data_item["transferTime"] ?? "",
                    "terms_of_delivery_code" => $export_settings["termsOfDelivery"]["code"] ?? "",
                    "terms_of_delivery_name" => $export_settings["termsOfDelivery"]["name"] ?? "",
                    "terms_of_delivery_customer_number" => $export_settings["customerNumber"] ?? "",
                    "export_reason" => $electronic_invoice_settings["reasonForExport"] ?? "",
                    "export_type" => $electronic_invoice_settings["typeOfExport"] ?? "",
                    "bring_priority" => $priority_settings["priority"] ?? "",
                ]);
            }
        }

        $errors = [];
        $updated = 0;
        foreach ($updateable_data as $updateable_data_item) {
            try {
                Wildrobot_Logistra_DB::update_delivery_relation($updateable_data_item);
                $updated++;
            } catch (\Throwable $error) {
                array_push($errors, $error->getMessage());
            }
        }
        return [
            "errors" => $errors,
            "updated" => $updated,
        ];
    }

    /**
     * @deprecated deprecated since version 6.0
     */
    private static function get_shipping_method_data($shipping_method, $zone_name)
    {
        $shippingMethodDTO = array();
        // Only table-rate
        if (get_option('logistra-robots-use-table-rate-integration') == "yes" && $shipping_method->id === "table_rate") {
            $rates = $shipping_method->get_shipping_rates();
            foreach ($rates as $rate) {
                if ($rate->rate_label == "") {
                    break;
                }
                $instance_id = $shipping_method->instance_id . '#' . $rate->rate_label;
                array_push($shippingMethodDTO, array(
                    'zone' => $zone_name,
                    'name' => $zone_name . ' - ' . $shipping_method->title . ' # ' . $rate->rate_label,
                    'id' => $shipping_method->id . ':' . $instance_id,
                    'rel' => get_option('logistra-robots-shipping-relation-identifier' . $shipping_method->id . ':' . $instance_id, null),
                    'transportAgreementId' => get_option('logistra-robots-shipping-relation-transport-agreement-' . $shipping_method->id . ':' . $instance_id, null),
                    'services' => get_option('logistra-robots-shipping-relation-services-' . $shipping_method->id . ':' . $instance_id, array()),
                    'printer' => get_option('logistra-robots-shipping-relation-selected-printer-' . $shipping_method->id . ':' . $instance_id, null),
                    'printTime' => get_option('logistra-robots-shipping-relation-selected-print-time-' . $shipping_method->id . ':' . $instance_id, null),
                    'transferTime' => get_option('logistra-robots-shipping-relation-selected-transfer-time-' . $shipping_method->id . ':' . $instance_id, null),
                    'returnLabel' => get_option('logistra-robots-shipping-relation-return-label-' . $shipping_method->id . ':' . $shipping_method->instance_id, null),
                ));
            }
        } else {
            array_push($shippingMethodDTO, array(
                'zone' => $zone_name,
                'name' => $zone_name . ' - ' . $shipping_method->title,
                'id' => $shipping_method->id . ':' . $shipping_method->instance_id,
                'rel' => get_option('logistra-robots-shipping-relation-identifier' . $shipping_method->id . ':' . $shipping_method->instance_id, null),
                'transportAgreementId' => get_option('logistra-robots-shipping-relation-transport-agreement-' . $shipping_method->id . ':' . $shipping_method->instance_id, null),
                'services' => get_option('logistra-robots-shipping-relation-services-' . $shipping_method->id . ':' . $shipping_method->instance_id, null),
                'printer' => get_option('logistra-robots-shipping-relation-selected-printer-' . $shipping_method->id . ':' . $shipping_method->instance_id, null),
                'printTime' => get_option('logistra-robots-shipping-relation-selected-print-time-' . $shipping_method->id . ':' . $shipping_method->instance_id, null),
                'transferTime' => get_option('logistra-robots-shipping-relation-selected-transfer-time-' . $shipping_method->id . ':' . $shipping_method->instance_id, null),
                'returnLabel' => get_option('logistra-robots-shipping-relation-return-label-' . $shipping_method->id . ':' . $shipping_method->instance_id, null),
            ));
        }
        return $shippingMethodDTO;
    }
    /**
     * @deprecated deprecated since version 5.0
     */
    private static function get_shipping_method_data_without_shipping_method($zone_name, $title, $id, $instance_id)
    {
        $shippingMethodDTO = array();
        // Only table-rate

        array_push($shippingMethodDTO, array(
            'zone' => $zone_name,
            'name' => $zone_name . ' - ' . $title,
            'id' => $id . ':' . $instance_id,
            'rel' => get_option('logistra-robots-shipping-relation-identifier' . $id . ':' . $instance_id, null),
            'transportAgreementId' => get_option('logistra-robots-shipping-relation-transport-agreement-' . $id . ':' . $instance_id, null),
            'services' => get_option('logistra-robots-shipping-relation-services-' . $id . ':' . $instance_id, null),
            'printer' => get_option('logistra-robots-shipping-relation-selected-printer-' . $id . ':' . $instance_id, null),
            'printTime' => get_option('logistra-robots-shipping-relation-selected-print-time-' . $id . ':' . $instance_id, null),
            'transferTime' => get_option('logistra-robots-shipping-relation-selected-transfer-time-' . $id . ':' . $instance_id, null),
            'returnLabel' => get_option('logistra-robots-shipping-relation-return-label-' . $id . ':' . $instance_id, null),
        ));

        return $shippingMethodDTO;
    }

    /**
     * @deprecated deprecated since version 5.0
     */
    private static function get_shipping_method_export_settings($shipping_method)
    {
        $shipping  = self::get_method_and_instance_from_shipping_method($shipping_method);
        $shippingMethodValues = array(
            'termsOfDelivery' =>  get_option("logistra-robots-shipping-relation-export-setings-terms-of-delivery-" . $shipping['method_id'] . ':' . $shipping['instance_id'], null),
            'customerNumber' =>  get_option("logistra-robots-shipping-relation-export-setings-customer-number-" . $shipping['method_id'] . ':' . $shipping['instance_id'], null),
        );
        return $shippingMethodValues;
    }
    /**
     * @deprecated deprecated since version 5.0
     */
    private static function get_method_and_instance_from_shipping_method($shipping_method)
    {
        // if $shipping_method is of type shipping method instance
        if ($shipping_method instanceof WC_Order_Item_Shipping) {
            $method_id = $shipping_method->get_method_id();
            if (get_option('logistra-robots-use-table-rate-integration') == "yes" && $method_id === "table_rate") {
                $instance_id = $shipping_method->get_instance_id() . "#" . $shipping_method->get_method_title();
            } else {
                $instance_id = $shipping_method->get_instance_id();
            }
        } else {
            $shipping_method_strings = explode(':', $shipping_method);
            $method_id = $shipping_method_strings[0];
            $instance_id = $shipping_method_strings[1];
        }
        return array(
            'method_id' => $method_id,
            'instance_id' => $instance_id,
        );
    }

    /**
     * @deprecated deprecated since version 5.0
     */
    private static function get_shipping_method_priority_settings($shipping_method)
    {
        $shipping  = self::get_method_and_instance_from_shipping_method($shipping_method);
        $shippingMethodValues = array(
            'priority' =>  get_option("logistra-robots-shipping-relation-priority-" . $shipping['method_id'] . ':' . $shipping['instance_id'], null),
        );
        return $shippingMethodValues;
    }
}
