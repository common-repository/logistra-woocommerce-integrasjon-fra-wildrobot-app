<?php


defined('ABSPATH') || exit;

/**
 * Wildrobot_Logistra_Ajax class.
 */
class Wildrobot_Logistra_Cargonizer
{

    // helthjem_standard is just here for testing
    private static $helthjem_products = array("helthjem_ekspress_standard_mypack", "helthjem_hentepakke", "helthjem_standard_mypack", "helthjem_mypack", "helthjem_hentepakke_return");

    public static function init()
    {
        // nothing here yet
    }

    public static function get_endpoint($endpoint, $cargonizer_provider)
    {
        if ($cargonizer_provider == "PROFRAKT") {
            $profrakt_endspoints = array(
                "service_partners" => "/pickup_points.xml",
                "consignment_cost" => "/cost_estimation.xml",
                "printers" => "/cloudprinters.xml",
            );
            return $profrakt_endspoints[$endpoint];
        } else {
            $logistra_endpoints = array(
                "service_partners" => "/service_partners.xml",
                "consignment_cost" => "/consignment_costs.xml",
                "printers" => "/printers.xml",
            );
            return $logistra_endpoints[$endpoint];
        }
    }


    public static function get_headers($api_key, $sender_id, $cargonizer_provider)
    {
        if ($cargonizer_provider == "PROFRAKT") {
            return array(
                'X-Profrakt-Key' => $api_key,
                'X-Profrakt-Sender' => $sender_id,
            );
        }
        // default to Logistra
        return array(
            'X-Cargonizer-Key' => $api_key,
            'X-Cargonizer-Sender' => $sender_id,
        );
    }
    public static function get_url($cargonizer_provider)
    {
        if ($cargonizer_provider == "PROFRAKT") {
            return get_option("wildrobot_logistra_cargonizer_backend_url_profrakt");
        }
        // default to Logistra
        return get_option("wildrobot_logistra_cargonizer_backend_url");
    }

    public static function get_printers()
    {
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");
        $sendersCommaDelimted = get_option('wildrobot_logistra_senders', false);
        $senders = explode(',', $sendersCommaDelimted);
        $printers = [
            [
                "name" =>  "Manuell printing",
                "id" => '9999999',
            ],
            [
                "name" =>  "Ingen printer (fjerner valgt printer)",
                "id" => '',
            ]
        ];
        foreach ($senders as $senderId) {
            $response = wp_remote_get(self::get_url($cargonizer_provider) . self::get_endpoint("printers", $cargonizer_provider), array(
                "headers" => self::get_headers(get_option('wildrobot_logistra_cargonizer_apikey', ""), $senderId, $cargonizer_provider),
            ));
            $repsponseBody = wp_remote_retrieve_body($response);
            $xml = simplexml_load_string($repsponseBody);
            $json = json_encode($xml);
            $array = json_decode($json, TRUE);
            // If we get an empty array we must handle this like this
            if (!array_key_exists("printer", $array)) {
                continue;
            }
            if (array_key_exists('id', $array["printer"])) {
                $printers = array_merge($printers, array(0 => $array["printer"]));
            } else {
                $printers = array_merge($printers, $array["printer"]);
            }
        }
        foreach ($printers as $key => $printer) {
            if (is_array($printer["id"])) {
                $printers[$key]['id'] = $printer["id"][0];
            }
        }
        $unique_printers = self::better_array_unique($printers);
        return $unique_printers;
    }

    public static function get_service_partners($country, $postcode, $carrier, $wr_id = null, $address = null)
    {
        if (empty($address)) {
            $transient_name = $country . '-' . $postcode . '-' . $carrier . '-wildrobot-logistra';
        } else {
            $formatted_address =  preg_replace("/[^a-zA-Z0-9]+/", "", $address);
            $transient_name = $country . '-' . $postcode . '-' . $carrier . '-' . $formatted_address . '-wildrobot-logistra';
        }
        $transient = get_transient($transient_name);
        if (empty($transient) || (array_key_exists('service_partners', $transient) && empty($transient["service_partners"]))) {
            $logger = new WC_Logger();
            $context = ['source' => 'wildrobot-logistra-service-partners'];
            $api_key = get_option('wildrobot_logistra_cargonizer_apikey', "");
            $sender_id = get_option('wildrobot_logistra_sender_id', "");
            $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");

            $params = array(
                'country'  => esc_html($country),
                'postcode' => esc_html($postcode),
                'carrier' => esc_html(strtolower($carrier)),
            );
            if (!empty($address)) {
                $params = array_merge($params, array(
                    'address' => esc_html($address),
                ));
            }
            if ($cargonizer_provider === "PROFRAKT" && get_option("wildrobot_logistra_filter_out_pakkeautomat") === "yes") {
                $params = array_merge($params, array(
                    'type' => "manned",
                ));
            }
            // Handle helthjem spesific
            if (!empty($wr_id)) {
                $wr_data = Wildrobot_Logistra_DB::get_transport_product_and_agreement_id($wr_id);
                if ($wr_data['product'] === "postnord_mypack_small") {
                    $params = array_merge($params, array(
                        'product' => "postnord_mypack_small",
                    ));
                }
                if (in_array($wr_data['product'], self::$helthjem_products)) {
                    $helthjem_customer_consignee_number = get_transient('wildrobot_logistra_helthjem_customer_consignee_number');
                    if (empty($helthjem_customer_consignee_number)) {
                        $transport_agreement = Wildrobot_Logistra_DB::get_transport_agreement_for_logistra_identifier($wr_id);
                        if (empty($transport_agreement)) {
                            throw new Exception("Helthjem transport avtale ikke funnet, prøv å oppdatere dine transportavtaler.");
                        }
                        $helthjem_customer_consignee_number =  $transport_agreement["ta_number"];
                        set_transient('wildrobot_logistra_helthjem_customer_consignee_number', $helthjem_customer_consignee_number, 20 * HOUR_IN_SECONDS);
                    }
                    if (empty($helthjem_customer_consignee_number)) {
                        throw new Exception("Fra 1 September krever Helthjem kunde nummer ved utlveringssted forespørsel. Klarte ikke å finne ditt HeltHjem kundenummer.");
                    }
                    // TODO - Check if this helthjem product requires service partner
                    $params = array_merge($params, array(
                        'product' => esc_html($wr_data['product']),
                        'shop_id' => esc_html($helthjem_customer_consignee_number)
                    ));
                }
            }
            $query = add_query_arg($params, self::get_url($cargonizer_provider) . self::get_endpoint("service_partners", $cargonizer_provider));
            $logger->info("URL => " . $query, $context);
            $response = wp_remote_get($query, array(
                'headers' => self::get_headers($api_key, $sender_id, $cargonizer_provider),
            ));

            $repsponseBody = wp_remote_retrieve_body($response);
            $xml = simplexml_load_string($repsponseBody);
            $json = json_encode($xml);
            $data = json_decode($json, TRUE);

            if (is_array($data) && array_key_exists('service-partners', $data) && array_key_exists('service-partner', $data['service-partners'])) {
                if (array_key_exists('number', $data["service-partners"]["service-partner"])) {
                    $data["service_partners"] = array(0 => $data["service-partners"]["service-partner"]);
                } else {
                    $data["service_partners"] = $data["service-partners"]["service-partner"];
                }
            }

            if (array_key_exists("errors", $data) && !empty($data["errors"])) {
                $logger->error(wc_print_r($data["errors"], true), $context);
            }
            unset($data["service-partners"]);

            if (!empty($data["service_partners"])) {
                if (get_option("wildrobot_logistra_filter_out_pakkeautomat") === "yes") {

                    $data["service_partners"] = array_values(array_filter($data["service_partners"], function ($service_partner) {
                        foreach (["Pakkeautomat", "Pakkeboks"] as $name) {
                            if (str_contains($service_partner["name"], $name)) {
                                return false;
                            }
                        }
                        return true;
                    }));
                }
                $logger->info("Number of service partners: " . count($data["service_partners"]), $context);
                set_transient($transient_name, apply_filters("wildrobot_logistra_avaiable_service_partners", $data), 7 * DAY_IN_SECONDS);
            } else {
                // Should only fallback for Postnord
                if (in_array($carrier, ["tollpost_globe", "postnord"])) {
                    $data["service_partners"] = [
                        0 => [
                            "customer-number" => esc_html('00098287279'),
                            'number'          => esc_html('0413898'),
                            'name'            => esc_html('Postens Godssenter'),
                            'country'         => esc_html('NO'),
                            'postcode'        => esc_html('0068'),
                            'city'            => esc_html('Oslo'),
                        ]
                    ];
                } else {
                    throw new Exception("Fant ingen utleveringssteder.");
                }
            }
        } else {
            $data = $transient;
        }
        return apply_filters('wildrobot_logistra_service_partner_request', $data);
    }

    public static function estimate_consignment($consignment_xml)
    {
        $hash = md5(wp_json_encode($consignment_xml));
        $transient = get_transient("wildrobot-logistra-freightcost-estimate-" . $hash);
        if (!empty($transient) && !Wildrobot_Logistra_Utils::is_test_enviroment()) {
            return $transient;
        }
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");
        $response = wp_remote_post(self::get_url($cargonizer_provider) . self::get_endpoint("consignment_cost", $cargonizer_provider), array(
            "headers" => self::get_headers(get_option('wildrobot_logistra_cargonizer_apikey', ""), get_option('wildrobot_logistra_sender_id', ""), $cargonizer_provider),
            'body' => $consignment_xml
        ));

        $responseBody = wp_remote_retrieve_body($response);
        $xml = simplexml_load_string($responseBody);
        $json = json_encode($xml);
        $data = json_decode($json, TRUE);
        if (array_key_exists("error", $data)) {
            throw new Exception($data["error"]);
        }
        if (array_key_exists("consignment", $data)) {
            if (array_key_exists("errors", $data["consignment"])) {
                if (array_key_exists("error", $data["consignment"]["errors"])) {
                    if (is_array($data["consignment"]["errors"]["error"])) {
                        throw new Exception(implode(", ", $data["consignment"]["errors"]["error"]));
                    } else {
                        throw new Exception($data["consignment"]["errors"]["error"]);
                    }
                }
            }
        }
        set_transient("wildrobot-logistra-freightcost-estimate-" . $hash, $data, 7 * DAY_IN_SECONDS);
        return $data;
    }

    public static function print_consignment($printer_id, $consignment_id)
    {
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");
        $api_key = get_option('wildrobot_logistra_cargonizer_apikey', "");
        $sender_id = get_option('wildrobot_logistra_sender_id', "");

        $url = self::get_url($cargonizer_provider) . '/consignments/label_direct';
        $params = array(
            'printer_id' => $printer_id,
            'consignment_ids[]' => $consignment_id,
        );

        $query = add_query_arg($params, $url);

        $response = wp_remote_post($query, array(
            'method'  => 'POST',
            'headers' => self::get_headers($api_key, $sender_id, $cargonizer_provider),
        ));

        if (is_wp_error($response)) {
            throw new Exception("Error printing consignment: " . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            error_log("Error printing consignment. Response code: " . $response_code . ". Response body: " . $response_body);
            throw new Exception("Error printing consignment. Please try again later.");
        }

        return json_decode($response_body, true);
    }

    function wildrobot_logistra_print_consignment_ajax()
    {
        check_ajax_referer('wildrobot_logistra_print_consignment', 'nonce');

        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'wildrobot-logistra'));
        }
        $printer_id    = isset($_GET['printer_id']) ? sanitize_text_field($_GET['printer_id']) : '';
        $consignment_id = isset($_GET['consignment_id']) ? sanitize_text_field($_GET['consignment_id']) : '';

        if (empty($printer_id) || empty($consignment_id)) {
            wp_send_json_error(__('Missing required parameters.', 'wildrobot-logistra'));
        }

        try {
            // $result = Wildrobot_Logistra_Cargonizer::print_consignment($printer_id, $consignment_id);
            // Redirect back to the referring page if available
            $referer = wp_get_referer();
            if ($referer) {
                wp_safe_redirect($referer);
                wp_send_json_success(array(
                    'message' => __('Consignment printed successfully.', 'wildrobot-logistra'),
                    'redirect' => $referer
                ));
            } else {
                wp_send_json_success(__('Consignment printed successfully.', 'wildrobot-logistra'));
            }
        } catch (Exception $e) {
        } catch (Exception $e) {
            error_log($e->getMessage());
            wp_send_json_error(__('An error occurred while printing the consignment.', 'wildrobot-logistra'));
        }
    }

    private static function better_array_unique($array, $keep_key_assoc = false)
    {
        $duplicate_keys = array();
        $tmp = array();

        foreach ($array as $key => $val) {
            // convert objects to arrays, in_array() does not support objects
            if (is_object($val))
                $val = (array) $val;

            if (!in_array($val, $tmp))
                $tmp[] = $val;
            else
                $duplicate_keys[] = $key;
        }

        foreach ($duplicate_keys as $key)
            unset($array[$key]);

        return $keep_key_assoc ? $array : array_values($array);
    }
}

Wildrobot_Logistra_Cargonizer::init();
