<?php

class Wildrobot_Logistra_Generate
{
    const CARRIER_POSTNORD = 'postnord';
    const CARRIER_BRING = 'bring2';
    const METHOD_EXISTS_MESSAGE = "Metoden %s finnes allerede for %s. ";
    const NO_AGREEMENT_MESSAGE = "Ingen transportavtale funnet for %s for %s.";
    const METHOD_CREATED_MESSAGE = "Opprettet %s for %s. ";
    const ERROR_MESSAGE = "Feil ved oppretting av shipping metode: %s. ";

    // Delivery types
    const DELIVERY_MAILBOX = 'Pakke til postkassen';
    const DELIVERY_HOME = 'Hjemlevering';
    const DELIVERY_PICKUP = 'Pakke til hentested';
    const DELIVERY_BUSINESS = 'Pakke til bedrift';

    // New constant for "GENERERT QUIKSTART: "
    const GENERATED_QUICKSTART = "(Generert fraktmetode)";

    public static function init() {}

    public static function postnord_postkasse($zone, $add_carrier_to_title = false)
    {
        $carrier = self::CARRIER_POSTNORD;
        $title = self::DELIVERY_MAILBOX . ($add_carrier_to_title ? " - " . ucfirst($carrier) : "");
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");

        return self::add_shipping_method($zone, [
            'title' => $title,
            'method_description' => self::DELIVERY_MAILBOX . ", Postnord, maks 2 kg. " . self::GENERATED_QUICKSTART,
            'estimate_freight_cost' => "yes",
            'estimate_freight_cost_base' => $cargonizer_provider === "PROFRAKT" ? "estimated" : "net",
            'weight_controlled' => "yes",
            'from_weight' => "0",
            'too_weight' => "2",
            'estimate_freight_cost_required' => "yes",
        ], [
            'agreement_identifier' => 'home_small',
            'carrier' => $carrier,
            'services' => ['postnord_ZPD_pose_pa_doren']
        ]);
    }

    public static function postnord_hjemlevering($zone, $add_carrier_to_title = false)
    {
        $carrier = self::CARRIER_POSTNORD;
        $title = self::DELIVERY_HOME . ($add_carrier_to_title ? " - " . ucfirst($carrier) : "");
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");

        return self::add_shipping_method($zone, [
            'title' => $title,
            'method_description' => self::DELIVERY_HOME . ", Postnord maks 35 kg. " . self::GENERATED_QUICKSTART,
            'estimate_freight_cost' => "yes",
            'estimate_freight_cost_base' => $cargonizer_provider === "PROFRAKT" ? "estimated" : "net",
            'weight_controlled' => "yes",
            'from_weight' => "0",
            'too_weight' => "35",
            'estimate_freight_cost_required' => "yes",
            "estimate_freight_cost_percentage" => $cargonizer_provider === "PROFRAKT" ? 0 : 12  // 12 %
        ], [
            'agreement_identifier' => 'home_attended',
            'carrier' => $carrier,
            'services' => []
        ]);
    }

    public static function postnord_hentested($zone, $add_carrier_to_title = false)
    {
        $carrier = self::CARRIER_POSTNORD;
        $title = self::DELIVERY_PICKUP . ($add_carrier_to_title ? " - " . ucfirst($carrier) : "");
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");

        return self::add_shipping_method($zone, [
            'title' => $title,
            'method_description' => self::DELIVERY_PICKUP . ", Postnord maks 35 kg. " . self::GENERATED_QUICKSTART,
            'estimate_freight_cost' => "yes",
            'estimate_freight_cost_base' => $cargonizer_provider === "PROFRAKT" ? "estimated" : "net",
            'weight_controlled' => "yes",
            'from_weight' => "0",
            'too_weight' => "35",
            'estimate_freight_cost_required' => "yes",
            "estimate_freight_cost_percentage" => $cargonizer_provider === "PROFRAKT" ? 0 : 12  // 12 %
        ], [
            'agreement_identifier' => 'collect',
            'carrier' => $carrier,
            "services" => []
        ]);
    }

    public static function postnord_bedrift($zone, $add_carrier_to_title = false)
    {
        $carrier = self::CARRIER_POSTNORD;
        $title = self::DELIVERY_BUSINESS . ($add_carrier_to_title ? " - " . ucfirst($carrier) : "");
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");

        return self::add_shipping_method($zone, [
            'title' => $title,
            'method_description' => self::DELIVERY_BUSINESS . ", Postnord maks 35 kg. " . self::GENERATED_QUICKSTART,
            'estimate_freight_cost' => "yes",
            'estimate_freight_cost_base' => $cargonizer_provider === "PROFRAKT" ? "estimated" : "net",
            'weight_controlled' => "yes",
            'from_weight' => "0",
            'too_weight' => "999",
            'estimate_freight_cost_required' => "yes",
            "estimate_freight_cost_percentage" => $cargonizer_provider === "PROFRAKT" ? 0 : 12  // 12 %
        ], [
            'agreement_identifier' => 'parcel',
            'carrier' => $carrier,
            "services" => []
        ]);
    }

    public static function bring_postkassen($zone, $add_carrier_to_title = false)
    {
        $carrier = self::CARRIER_BRING;
        $title = self::DELIVERY_MAILBOX . ($add_carrier_to_title ? " - " . ucfirst("bring") : "");
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");

        return self::add_shipping_method($zone, [
            'title' => $title,
            'method_description' => self::DELIVERY_MAILBOX . ", Bring maks 2 kg. " . self::GENERATED_QUICKSTART,
            'estimate_freight_cost' => "yes",
            'estimate_freight_cost_base' => $cargonizer_provider === "PROFRAKT" ? "estimated" : "net",
            'weight_controlled' => "yes",
            'from_weight' => "0",
            'too_weight' => "2",
            'estimate_freight_cost_required' => "yes",
            "estimate_freight_cost_fixed" => $cargonizer_provider === "PROFRAKT" ? 0 : 2, // for bompenger
        ], [
            'agreement_identifier' => 'small_parcel',
            'carrier' => $carrier,
            "services" => ["bring2_delivery_to_door_handle"]
        ]);
    }

    public static function bring_hentested($zone, $add_carrier_to_title = false)
    {
        $carrier = self::CARRIER_BRING;
        $title = self::DELIVERY_PICKUP . ($add_carrier_to_title ? " - " . ucfirst("bring") : "");
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");

        return self::add_shipping_method($zone, [
            'title' => $title,
            'method_description' => self::DELIVERY_PICKUP . ", Bring maks 35 kg. " . self::GENERATED_QUICKSTART,
            'estimate_freight_cost' => "yes",
            'estimate_freight_cost_base' => $cargonizer_provider === "PROFRAKT" ? "estimated" : "net",
            'weight_controlled' => "yes",
            'from_weight' => "0",
            'too_weight' => "35",
            'estimate_freight_cost_required' => "yes",
            "estimate_freight_cost_percentage" => $cargonizer_provider === "PROFRAKT" ? 0 : 10  // 10% på prisen for å ta høyde for drivstofftillegg og bompenger på Bring Pakke til bedrift
        ], [
            'agreement_identifier' => 'parcel_pickup',
            'carrier' => $carrier,
            "services" => ["bring2_choice_of_pickup_point"]
        ]);
    }

    public static function bring_hjemlevering($zone, $add_carrier_to_title = false)
    {
        $carrier = self::CARRIER_BRING;
        $title = self::DELIVERY_HOME . ($add_carrier_to_title ? " - " . ucfirst("bring") : "");
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");

        return self::add_shipping_method($zone, [
            'title' => $title,
            'method_description' => self::DELIVERY_HOME . ", Bring maks 35 kg. " . self::GENERATED_QUICKSTART,
            'estimate_freight_cost' => "yes",
            'estimate_freight_cost_base' => $cargonizer_provider === "PROFRAKT" ? "estimated" : "net",
            'weight_controlled' => "yes",
            'from_weight' => "0",
            'too_weight' => "35",
            'estimate_freight_cost_required' => "yes",
            "estimate_freight_cost_percentage" => $cargonizer_provider === "PROFRAKT" ? 0 : 10  // 10% på prisen for å ta høyde for drivstofftillegg og bompenger på Bring Pakke til bedrift
        ], [
            'agreement_identifier' => 'home_delivery',
            'carrier' => $carrier,
            "services" => ["bring2_one_delivery_attempt_then_pickup_point"]
        ]);
    }

    public static function bring_bedrift($zone, $add_carrier_to_title = false)
    {
        $carrier = self::CARRIER_BRING;
        $title = self::DELIVERY_BUSINESS . ($add_carrier_to_title ? " - " . ucfirst("bring") : "");
        $cargonizer_provider = get_option("wildrobot_logistra_cargonizer_provider", "LOGISTRA");

        return self::add_shipping_method($zone, [
            'title' => $title,
            'method_description' => self::DELIVERY_BUSINESS . ", Bring maks 35 kg. " . self::GENERATED_QUICKSTART,
            'estimate_freight_cost' => "yes",
            'estimate_freight_cost_base' => $cargonizer_provider === "PROFRAKT" ? "estimated" : "net",
            'weight_controlled' => "yes",
            'from_weight' => "0",
            'too_weight' => "999",
            'estimate_freight_cost_required' => "yes",
            "estimate_freight_cost_percentage" => $cargonizer_provider === "PROFRAKT" ? 0 : 10  // 10% på prisen for å ta høyde for drivstofftillegg og bompenger på Bring Pakke til bedrift
        ], [
            'agreement_identifier' => 'business_parcel',
            'carrier' => $carrier,
            "services" => []
        ]);
    }

    /* HELPER FUNCTIONS */

    private static function get_service_message($service)
    {
        $messages = [
            'postnord_ZPD_pose_pa_doren' => '| Tilleggstjeneste aktivert: "Pose på døren". Denne sørger for at pakken henges på døren i stedet for å gå til hentested dersom postkassen er full. Dette er rimeligere enn om den sendes til hentested.',
            'bring2_delivery_to_door_handle' => '| Tilleggstjeneste aktivert: "Pose på døren". Denne sørger for at pakken henges på døren i stedet for å gå til hentested dersom postkassen er full. Dette er rimeligere enn om den sendes til hentested.',
            'bring2_choice_of_pickup_point' => '| Tilleggstjeneste aktivert: "Valgfritt hentested". Denne sørger for at dersom du aktiverer for valg av hentested, så vil Bring akseptere det valgte hentestedet. Merk: Enkelte tema kan overstyre Wildrobots muligheter for å presentere valgfrie hentesteder i utsjekken.',
            'bring2_one_delivery_attempt_then_pickup_point' => '| Tilleggstjeneste aktivert: "1 utleveringsforsøk, så hentested". Denne sørger for at ikke pakken går i retur til dere dersom mottaker ikke er tilstede ved leveringstidspunktet.',
            // Add more service messages here as needed
        ];

        return isset($messages[$service]) ? $messages[$service] : '';
    }

    private static function add_shipping_method($zone, $params, $carrier_params)
    {
        try {
            if (self::method_exists($zone, $params['method_description'])) {
                return sprintf(self::METHOD_EXISTS_MESSAGE, $params['title'], $zone->get_zone_name());
            }
            $transport_agreement = self::find_transport_agreement($carrier_params['carrier'], $carrier_params['agreement_identifier']);

            if (!$transport_agreement) {
                return sprintf(self::NO_AGREEMENT_MESSAGE, $params['title'], $zone->get_zone_name());
            }

            $new_method = self::create_shipping_method($zone, $params);

            self::update_delivery_relation($new_method, $transport_agreement, $carrier_params['services'] ?? []);

            $created_message = sprintf(self::METHOD_CREATED_MESSAGE, $params['title'], $zone->get_zone_name());

            // Get service messages
            $service_messages = [];
            if (isset($carrier_params['services']) && is_array($carrier_params['services'])) {
                foreach ($carrier_params['services'] as $service) {
                    $service_message = self::get_service_message($service);
                    if ($service_message) {
                        $service_messages[] = $service_message;
                    }
                }
            }

            // Combine created message and service messages
            $full_message = $created_message;
            if (!empty($service_messages)) {
                $full_message .= "\n\n" . implode("\n\n", $service_messages);
            }

            return $full_message;
        } catch (\Throwable $error) {
            return sprintf(self::ERROR_MESSAGE, $error->getMessage());
        }
    }

    private static function method_exists($zone, $description)
    {
        foreach ($zone->get_shipping_methods(true) as $shipping_method) {
            $current_description = wp_strip_all_tags($shipping_method->get_method_description());
            if ($current_description === $description) {
                return true;
            }
        }
        return false;
    }

    private static function create_shipping_method($zone, $params,)
    {
        $new_shipping_instance = $zone->add_shipping_method("logistra_robots_shipping_method");
        $new_method = new WC_Logistra_Robots_Shipping_Method($new_shipping_instance);
        $new_method->init_instance_settings();

        // Merge all params
        $new_settings = array_merge($new_method->instance_settings, $params);

        update_option($new_method->get_instance_option_key(), apply_filters('woocommerce_shipping_' . $new_method->id . '_instance_settings_values', $new_settings, $new_method), 'yes');
        do_action('woocommerce_update_options');
        return $new_method;
    }

    private static function find_transport_agreement($carrier, $identifier)
    {
        foreach (get_option('wildrobot_logistra_transport_agreements', []) as $transport_agreement) {
            if ($transport_agreement["ta_carrier"]["identifier"] === $carrier) {
                if (strpos($transport_agreement["identifier"], $identifier) !== false) {
                    return $transport_agreement;
                }
            }
        }
        return null;
    }

    private static function update_delivery_relation($method, $agreement, $services)
    {
        Wildrobot_Logistra_DB::update_delivery_relation([
            "shipping_method_identifier" => $method->id . ":" . $method->get_instance_id(),
            "carrier" => $agreement["ta_carrier"]["identifier"],
            "wr_id" => $agreement["wr_id"],
            "services" => $services
        ]);
    }
}
// Wildrobot_Logistra_Generate::init();
