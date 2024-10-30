<?php

class Wildrobot_Logistra_DB
{

    public static function init()
    {
    }

    public static function delete_delivery_relation($shipping_method_identifier)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wildrobot_logistra_delivery_relations';
        $delivery_relation = self::get_delivery_relation($shipping_method_identifier);
        if (empty($delivery_relation)) {
            throw new Exception("No relation found for shipping method " . $shipping_method_identifier);
        }
        $wpdb->delete($table, ['id' => $delivery_relation["id"]]);
        $delivery_relation_return = self::get_delivery_relation($shipping_method_identifier, true);
        if (!empty($delivery_relation_return)) {
            $wpdb->delete($table, ['id' => $delivery_relation_return["id"]]);
        }
        return true;
    }

    public static function get_delivery_relations()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wildrobot_logistra_delivery_relations';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table"), ARRAY_A);
        foreach ($rows as $key => $row) {
            $rows[$key] = self::decode_data($rows[$key]);
        }
        return $rows;
    }

    public static function  get_delivery_relation($shipping_method_identifier, $is_return_label_relation = false)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wildrobot_logistra_delivery_relations';
        $shipping_method_identifier = self::normalize_some_shipping_method_identifier($shipping_method_identifier);
        if ($is_return_label_relation) {
            $shipping_method_identifier .= ":return";
        }

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE shipping_method_identifier = %s", $shipping_method_identifier), ARRAY_A);
        if (empty($row)) {
            return null;
        }
        $row = self::decode_data($row);
        return $row;
    }
    public static function get_delivery_relation_with_transport_agreement($shipping_method_identifier)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wildrobot_logistra_delivery_relations';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE shipping_method_identifier = %s", $shipping_method_identifier), ARRAY_A);
        if (empty($row)) {
            return null;
        }
        $row = self::decode_data($row);
        $row["transport_agreement"] = self::get_transport_agreement_for_logistra_identifier($row["wr_id"]);
        return $row;
    }

    public static function  get_transport_agreement_for_logistra_identifier($wr_id)
    {
        $transport_agreements = get_option('wildrobot_logistra_transport_agreements', []);
        $requested_transport_agreement = null;
        foreach ($transport_agreements as $transport_agreement) {
            if ($transport_agreement["wr_id"] === $wr_id) {
                $requested_transport_agreement = $transport_agreement;
                // Make sure services exist
                if (empty($requested_transport_agreement["services"])) {
                    $requested_transport_agreement["services"] = ["service" => []];
                }
                break;
            }
        }

        return (array) $requested_transport_agreement;
    }

    public static function get_transport_agreement_for_product_unsafe($product)
    {
        $transport_agreements = get_option('wildrobot_logistra_transport_agreements', []);
        $requested_transport_agreement = null;
        foreach ($transport_agreements as $transport_agreement) {
            if ($product === $transport_agreement["identifier"]) {
                $requested_transport_agreement = $transport_agreement;
                // Make sure services exist
                if (empty($requested_transport_agreement["services"])) {
                    $requested_transport_agreement["services"] = ["service" => []];
                }
                break;
            }
        }
        return (array)  $requested_transport_agreement;
    }

    private static function encode_data($data)
    {
        if ($data["services"]) {
            $data["services"] = json_encode($data["services"]);
        }
        // if ($data["return_label"]) {
        //     $data["return_label"] = $data["return_label"] === "yes" ? true : false;
        // }
        return $data;
    }

    private static function decode_data($data)
    {
        $data["services"] = json_decode($data["services"], true) ?? [];
        // $data["return_label"] = wc_bool_to_string($data["return_label"]);
        return $data;
    }

    public static function update_delivery_relation($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wildrobot_logistra_delivery_relations';
        // make sure ID is not updated
        unset($data["id"]);
        $data = self::encode_data($data);
        $data = wp_parse_args($data, ["last_updated" => current_time("mysql")]);
        $shipping_method_identifier = $data["shipping_method_identifier"];



        if (strpos($shipping_method_identifier, ":") === false) {
            throw new Exception("shipping_method_identifier should contain :");
        }


        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE shipping_method_identifier = %s", $shipping_method_identifier), ARRAY_A);
        if (empty($row)) {
            $data = wp_parse_args($data, ["shipping_method_identifier" => $shipping_method_identifier]);
            $created = $wpdb->insert($table, $data);
            if (!$created) {
                throw new Exception($wpdb->last_error);
            }
        } else {
            $where = ["shipping_method_identifier" => $shipping_method_identifier];
            $updated = $wpdb->update($table, $data, $where);
            if (!$updated) {
                throw new Exception($wpdb->last_error);
            }
        }
        $updated_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE shipping_method_identifier = %s", $shipping_method_identifier), ARRAY_A);
        if (empty($updated_row)) {
            throw new Exception($wpdb->last_error);
        }
        $updated_row = self::decode_data($updated_row);

        return $updated_row;
    }

    public static function get_transport_product_and_agreement_id($wr_id)
    {
        if (strpos($wr_id, ":") === FALSE) {
            throw new Exception("Leveranse relasjonen må ha en transport produkt og en transport avtale. Fnat ingen : i " .  $wr_id);
        }
        $wr_id_as_array = explode(':', $wr_id);
        return [
            "transport_agreement_id" => $wr_id_as_array[0],
            "product" => $wr_id_as_array[1],
        ];
    }

    public static function normalize_some_shipping_method_identifier($some_shipping_method_identifier)
    {
        if ($some_shipping_method_identifier instanceof WC_Order_Item_Shipping) {
            $method_id = $some_shipping_method_identifier->get_method_id();
            if (get_option('wildrobot_logistra_use_table_rate_integration') == "yes" && $method_id === "table_rate") {
                $instance_id = $some_shipping_method_identifier->get_instance_id() . "#" . $some_shipping_method_identifier->get_method_title();
            } else {
                $instance_id = $some_shipping_method_identifier->get_instance_id();
            }
            return apply_filters("wildrobot-logistra-shipping-method-identifier-custom", $method_id . ":" . $instance_id);
        }
        if (strpos($some_shipping_method_identifier, ":") !== FALSE) {
            return apply_filters("wildrobot-logistra-shipping-method-identifier-custom", $some_shipping_method_identifier);
        }
    }

    public static function get_method_and_instance_from_shipping_method($shipping_method)
    {
        // if $shipping_method is of type shipping method instance
        if ($shipping_method instanceof WC_Order_Item_Shipping) {
            $method_id = $shipping_method->get_method_id();
            if (get_option('wildrobot_logistra_use_table_rate_integration') == "yes" && $method_id === "table_rate") {
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
}
// Wildrobot_Logistra_DB::init();
