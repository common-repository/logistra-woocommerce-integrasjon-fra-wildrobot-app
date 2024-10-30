<?php


defined('ABSPATH') || exit;

/**
 * Wildrobot_Logistra_Ajax class.
 */
class Wildrobot_Logistra_Ajax
{

    /**
     * Hook in ajax handlers.
     */
    public static function init()
    {
        add_action('init', array(__CLASS__, 'define_ajax'), 0);
        add_action('template_redirect', array(__CLASS__, 'do_wc_ajax'), 0);
        self::add_ajax_events();
    }

    /**
     * Get WC Ajax Endpoint.
     *
     * @param string $request Optional.
     *
     * @return string
     */
    public static function get_endpoint($request = '')
    {
        return esc_url_raw(apply_filters('woocommerce_ajax_get_endpoint', add_query_arg('wc-ajax', $request, remove_query_arg(array('remove_item', 'add-to-cart', 'added-to-cart', 'order_again', '_wpnonce'), home_url('/', 'relative'))), $request));
    }

    /**
     * Set WC AJAX constant and headers.
     */
    public static function define_ajax()
    {
        // phpcs:disable
        if (!empty($_GET['wc-ajax'])) {
            wc_maybe_define_constant('DOING_AJAX', true);
            wc_maybe_define_constant('WC_DOING_AJAX', true);
            if (!WP_DEBUG || (WP_DEBUG && !WP_DEBUG_DISPLAY)) {
                @ini_set('display_errors', 0); // Turn off display_errors during AJAX events to prevent malformed JSON.
            }
            $GLOBALS['wpdb']->hide_errors();
        }
        // phpcs:enable
    }

    /**
     * Send headers for WC Ajax Requests.
     *
     * @since 2.5.0
     */
    private static function wc_ajax_headers()
    {
        if (!headers_sent()) {
            send_origin_headers();
            send_nosniff_header();
            wc_nocache_headers();
            header('Content-Type: text/html; charset=' . get_option('blog_charset'));
            header('X-Robots-Tag: noindex');
            status_header(200);
        } elseif (WP_DEBUG) {
            headers_sent($file, $line);
            trigger_error("wc_ajax_headers cannot set headers - headers already sent by {$file} on line {$line}", E_USER_NOTICE); // @codingStandardsIgnoreLine
        }
    }

    /**
     * Check for WC Ajax request and fire action.
     */
    public static function do_wc_ajax()
    {
        global $wp_query;

        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (!empty($_GET['wc-ajax'])) {
            $wp_query->set('wc-ajax', sanitize_text_field(wp_unslash($_GET['wc-ajax'])));
        }

        $action = $wp_query->get('wc-ajax');

        if ($action) {
            self::wc_ajax_headers();
            $action = sanitize_text_field($action);
            do_action('wc_ajax_' . $action);
            wp_die();
        }
        // phpcs:enable
    }

    /**
     * Hook in methods - uses WordPress ajax handlers (admin-ajax).
     */
    public static function add_ajax_events()
    {
        $ajax_events_nopriv = array(
            "get_service_partners",
            "complete_picklist"
        );

        foreach ($ajax_events_nopriv as $ajax_event) {
            add_action('wp_ajax_woocommerce_wildrobot_logistra_' . $ajax_event, array(__CLASS__, $ajax_event));
            add_action('wp_ajax_nopriv_woocommerce_wildrobot_logistra_' . $ajax_event, array(__CLASS__, $ajax_event));

            // WC AJAX can be used for frontend ajax requests.
            add_action('wc_ajax_' . $ajax_event, array(__CLASS__, $ajax_event));
        }

        $ajax_events = array(
            'get_deliverable_order_options',
            'get_deliverable_orders',
            'get_deliverable_order',
            'get_options',
            'update_options',
            'get_printers',
            'get_pages',
            "set_prod",
            "set_dev",
            "get_delivery_relations",
            "get_delivery_relation",
            "update_delivery_relation",
            "create_consignment_for_order_id",
            "create_consignment_from_args",
            "get_consignment_args_from_order",
            "get_service_partners",
            "print_picklist",
            "create_goods_letter",
            "get_order_freight_label_url",
            "quickstart"
        );

        foreach ($ajax_events as $ajax_event) {
            add_action('wp_ajax_woocommerce_wildrobot_logistra_' . $ajax_event, array(__CLASS__, $ajax_event));
        }
    }

    public static function quickstart()
    {
        try {
            ob_start();
            self::check_security();
            $carrierMix = self::check_field('carrierMix');
            $sellToCustomers = self::check_field('sellToCustomers');

            $messages = [];

            if (get_option("wildrobot_logistra_static_weight_on_orders") !== "yes") {
                // Check product weights
                $products_with_weight = 0;
                $products_without_weight = 0;
                $args = array(
                    'post_type'      => 'product',
                    'posts_per_page' => -1,
                );
                $products = new WP_Query($args);

                if ($products->have_posts()) {
                    while ($products->have_posts()) {
                        $products->the_post();
                        $product = wc_get_product(get_the_ID());
                        if ($product->get_weight()) {
                            $products_with_weight++;
                        } else {
                            $products_without_weight++;
                        }
                    }
                }
                wp_reset_postdata();


                if ($products_without_weight / $products_with_weight > 0.2) {
                    $messages[] = sprintf(
                        __('Mer enn 20 %% av produktene mangler vekt, setter fast vekt til 1 kg. Produkter med vekt: %d, Produkter uten vekt: %d.', 'wildrobot-logistra'),
                        $products_with_weight,
                        $products_without_weight
                    );
                    Wildrobot_Logistra_Options::update_options(["wildrobot_logistra_static_weight_on_orders" => "yes", "wildrobot_logistra_static_weight_amount" => 1]);
                }
            }



            // Disable all existing shipping methods
            $shipping_zones = WC_Shipping_Zones::get_zones();
            // $zone_data is an instance of WC_Shipping_Zone
            foreach ($shipping_zones as $zone_id => $zone_data) {
                $zone = new WC_Shipping_Zone($zone_id);

                $is_norway = false;
                foreach ($zone->get_zone_locations() as $zone_location) {
                    if ($zone_location->type === "country" && $zone_location->code === "NO") {
                        $is_norway = true;
                    }
                }
                if (!$is_norway) {
                    continue;
                }
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-generate.php';
                $add_carrier_to_title = $carrierMix === "all";
                if ($carrierMix === "postnord" || $carrierMix === "all") {
                    if ($sellToCustomers === "private" || $sellToCustomers === "both") {
                        $messages[] = Wildrobot_Logistra_Generate::postnord_postkasse($zone, $add_carrier_to_title);
                        $messages[] = Wildrobot_Logistra_Generate::postnord_hentested($zone, $add_carrier_to_title);
                        $messages[] = Wildrobot_Logistra_Generate::postnord_hjemlevering($zone, $add_carrier_to_title);
                    }
                    if ($sellToCustomers === "business" || $sellToCustomers === "both") {
                        $messages[] = Wildrobot_Logistra_Generate::postnord_bedrift($zone, $add_carrier_to_title);
                    }
                }
                if ($carrierMix === "bring" || $carrierMix === "all") {
                    if ($sellToCustomers === "private" || $sellToCustomers === "both") {
                        $messages[] = Wildrobot_Logistra_Generate::bring_postkassen($zone, $add_carrier_to_title);
                        $messages[] = Wildrobot_Logistra_Generate::bring_hentested($zone, $add_carrier_to_title);
                        $messages[] = Wildrobot_Logistra_Generate::bring_hjemlevering($zone, $add_carrier_to_title);
                    }
                    if ($sellToCustomers === "business" || $sellToCustomers === "both") {
                        $messages[] = Wildrobot_Logistra_Generate::bring_bedrift($zone, $add_carrier_to_title);
                    }
                }
            }

            if (empty($messages)) {
                $messages[] = __("Ingen endringer trengs", "wildrobot-logistra");
            } else {
                $messages[] = __("Vi har aktivert estimerte fraktpriser i din kasse. Dette sørger for at dine kunder betaler omtrentlig det samme som du gjør for frakten, såfremt dere har registrert korrekte vekter på produktene deres i WooCommerce. Siden både Bring og PostNord opererer med noen variable faste tillegg (drivstoff og bompenger), er det lagt til tillegg for disse i estimeringen der det er relevant.", "wildrobot-logistra");
            }

            wp_send_json_success([
                "messages" => $messages
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    /**
     * Get a refreshed cart fragment, including the mini cart HTML.
     */
    public static function get_printers()
    {
        try {
            ob_start();
            self::check_security();
            $printers = Wildrobot_Logistra_Cargonizer::get_printers();
            $res = Wildrobot_Logistra_Options::update_options(["wildrobot_logistra_printers" => $printers]);

            wp_send_json_success($res);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function get_deliverable_order_options()
    {
        try {
            ob_start();
            self::check_security();
            $shipping_classes = WC()->shipping()->get_shipping_classes();
            $categories = get_terms([
                'taxonomy'   => "product_cat",
                'hide_empty' => true,
            ]);
            $tags = get_terms('product_tag');
            wp_send_json_success([
                "wc_shipping_classes" => $shipping_classes,
                "wc_categories" => is_array($categories) ? $categories : array_values(get_object_vars($categories)),
                "wc_tags" => is_array($tags) ? $tags :  array_values(get_object_vars($tags)),
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }
    public static function get_deliverable_order()
    {
        try {
            ob_start();
            self::check_security();
            $order_id = self::check_field('order_id');
            $deliverable_consignments = Wildrobot_Logistra_Order_Utils::get_deliverable_consignments_for_order($order_id);
            wp_send_json_success([
                "deliverableOrders" => $deliverable_consignments
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error($error->getMessage(), $error->getCode());
        }
    }
    public static function get_deliverable_orders()
    {
        try {
            ob_start();
            self::check_security();
            $args = self::check_array('args');
            $errors = [];
            $query_args = array(
                'limit' => $args['limit'],
                'offset' => $args['offset'],
                'status' => array($args['wc_status']),
                "logistra-robots-sent" => $args["sendt"] === "yes" ? "yes" : null,
                "wildrobot-logistra-picklist-created" => $args["picklistCreated"] === "yes" ? "yes" : null,
                'return' => 'ids',
            );
            $query = new WC_Order_Query($query_args);
            $order_ids = $query->get_orders();
            $deliverable_orders = [];
            $offset = $args['offset'];
            foreach ($order_ids as $order_id) {
                $offset += 1;
                try {
                    // Can only filter on yes, so need to remove on no
                    $order = wc_get_order($order_id);
                    if ($args["sendt"] === "no") {
                        if ($order->get_meta("logistra-robots-sent", true) === "yes") {
                            continue;
                        }
                    }
                    if ($args["picklistCreated"] === "no") {
                        if ($order->get_meta("wildrobot-logistra-picklist-created", true) === "yes") {
                            continue;
                        }
                    }
                    $deliverable_consignments = Wildrobot_Logistra_Order_Utils::get_deliverable_consignments_for_order($order_id);
                    if (!empty($args["wr_id"])) {
                        foreach ($deliverable_consignments as $key => $deliverable_consignment) {
                            if ($deliverable_consignment["wr_id"] !== $args["wr_id"]) {
                                unset($deliverable_consignments[$key]);
                            }
                        }
                    }
                    $deliverable_orders = array_merge($deliverable_orders, $deliverable_consignments);
                } catch (\Throwable $error) {
                    $errors = array_merge($errors, [
                        "order_id" => $order->get_id(),
                        "error" => $error->getMessage()
                    ]);
                }
            }
            wp_send_json_success([
                "deliverableOrders" => $deliverable_orders,
                "errors" => $errors,
                "ofset" => $offset
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function complete_picklist()
    {
        try {
            ob_start();
            self::check_security();
            $order_id = self::check_field('order_id');

            if (get_option("wildrobot_logistra_picklist_active") !== "yes") {
                throw new Exception(__("Plukkliste ikke aktivert", 'wildrobot-logistra'));
            }
            $user = wp_get_current_user();
            if (count($user->roles) === 0) {
                throw new Exception(__("Din bruker har ingen rolle i denne butikken.", 'wildrobot-logistra'));
            }
            $order = wc_get_order($order_id);
            $orderNoteText = "";
            // Complete order to status
            $completeOrderToStatus = get_option("wildrobot_logistra_setting_complete_order_to_status");
            $new_status = 'wc-completed';
            if (!empty($completeOrderToStatus)) {
                $new_status = $completeOrderToStatus;
            }
            if (!wc_is_order_status($new_status)) {
                throw new Exception("Prøvde å fullføre til status: " . $new_status . ". Men denne statusen ble ikke funnet.");
            }
            $success = $order->update_status($new_status);
            if (!$success) {
                throw new Exception("Prøvde å fullføre, men systemet sa at det ikke gikk. Kontakt din administrator.");
            }
            $orderNoteText .= __('- Ordren ble fullført ved skanning av plukkliste av bruker ' . $user->locale_get_display_name(), 'logistra-robots') . '</br>';
            $order->add_order_note($orderNoteText);
            $create_label = get_option('wildrobot_logistra_setting_send_consignment_on_complete_order') === 'yes';
            $order->update_meta_data('wildrobot-logistra-picklist-completed', "yes");
            $order->save();
            wp_send_json_success([
                "messages" => $create_label ? [__("Ordre fullført, vil opprette frakt.", 'wildrobot-logistra')] : [__("Ordre fullført.", "wildrobot-logistra")],
                "with_freight_label" => $create_label
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function get_options()
    {
        try {
            ob_start();
            self::check_security();
            $options = self::check_array('options');
            $res = Wildrobot_Logistra_Options::get_options_safe($options);

            wp_send_json_success($res);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function update_options()
    {
        try {
            ob_start();
            self::check_security();
            $options = self::check_array('options');
            $updated = Wildrobot_Logistra_Options::update_options($options);

            wp_send_json_success($updated);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function get_delivery_relation()
    {
        try {
            ob_start();
            self::check_security();
            $shipping_method_identifier = self::check_field('shipping_method_identifier');
            $relations = Wildrobot_Logistra_Delivery_Relations::get_relation($shipping_method_identifier);

            wp_send_json_success($relations);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function get_order_freight_label_url()
    {
        try {
            ob_start();
            self::check_security();
            $order_id = self::check_field('order_id');
            $order = wc_get_order($order_id);
            $label = $order->get_meta('logistra-robots-freight-label-url', true);
            if (empty($label)) {
                throw new Exception(__("Fant ikke fraktlabel for ordre", 'wildrobot-logistra'));
            }
            wp_send_json_success([
                "label" => $label
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function get_consignment_args_from_order()
    {
        try {
            ob_start();
            self::check_security();
            $data = self::check_array('data');

            $errors = [];
            $consignment_args = [];
            foreach (Wildrobot_Logistra_Consignment_Order::get_consignment_args_from_order_id($data["order_id"], false) as $consignment_arg) {
                try {
                    $consignment = new Wildrobot_Logistra_Consignment($consignment_arg);
                    $consignment_args[] = $consignment->to_args();
                } catch (\Throwable $error) {
                    array_push($errors, $error->getMessage());
                }
            }
            if (!empty($errors)) {
                throw new Exception(implode("\n", $errors));
            }

            wp_send_json_success([
                "messages" => ["Fant " . count($consignment_args) . " levering(er) for ordrenummer " . $data["order_id"]],
                "consignment_args" => $consignment_args
            ]);
        } catch (\Throwable $error) {
            $logger = new WC_Logger();
            $context = ['source' => 'wildrobot-freight-override-order'];
            $logger->info("Tried getting values for consigment, got error: " . wc_print_r($error, true), $context);
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function create_consignment_for_order_id()
    {
        try {
            ob_start();
            self::check_security();
            $data = self::check_array('data');

            $messages = [];
            $logger = new WC_Logger();
            $context = ['source' => 'wildrobot-freight-sending-order'];


            $errors = [];
            foreach (Wildrobot_Logistra_Consignment_Order::get_consignment_args_from_order_id($data["order_id"]) as $consignment_arg) {
                try {
                    // Simple overriding
                    if (!empty($data["wr_id"])) {
                        $consignment_arg["wr_id"] = $data["wr_id"];
                        $consignment_arg["services"] = [];
                    }
                    if (!empty($data["services"])) {
                        $consignment_arg["services"] = $data["services"];
                    }
                    if (!empty($data["email_notification_to_consignee"])) {
                        $consignment_arg["email_notification_to_consignee"] = $data["email_notification_to_consignee"] === "yes";
                    }
                    $consignment = new Wildrobot_Logistra_Consignment($consignment_arg);
                    $messages_from_consignment = $consignment->send_backend();
                    $messages = array_merge($messages, $messages_from_consignment);
                } catch (\Throwable $error) {
                    $logger->info("----------------" . $data["order_id"] . "----------START-----------", $context);
                    $logger->info(wc_print_r($error, true), $context);
                    $logger->info("----------------" . $data["order_id"] . "-----------END----------", $context);
                    array_push($errors, $error->getMessage());
                }
            }
            if (!empty($errors)) {
                throw new Exception(implode("\n", $errors));
            }
            wp_send_json_success([
                "messages" => $messages
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }
    public static function print_picklist()
    {
        try {
            ob_start();
            self::check_security();
            $data = self::check_array('data');

            if (empty($data["order_id"])) {
                throw new Exception("Order id is required to print picklist");
            }
            $picklist_res = Wildrobot_Logistra_Picklist::create_picklist($data["order_id"]);

            wp_send_json_success([
                "messages" => [$picklist_res]
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }
    public static function create_consignment_from_args()
    {
        try {
            ob_start();
            self::check_security();
            $data = self::check_array('data');
            foreach ($data as $key => $value) {
                if ($value === "no") {
                    $data[$key] = false;
                }
                if ($value === "yes") {
                    $data[$key] = true;
                }
            }

            $messages = [];
            $consignment = new Wildrobot_Logistra_Consignment($data);
            $messages_from_consignment = $consignment->send_backend();
            $messages = array_merge($messages, $messages_from_consignment);
            wp_send_json_success([
                "messages" => $messages
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function create_goods_letter()
    {
        try {
            ob_start();
            self::check_security();
            $data = self::check_array('data');

            $messages = [];
            $result = Wildrobot_Logistra_Consignment::goods_letter($data);
            if (!$result) {
                throw new Exception("Feil i repons fra server ved opprettelse av hovedsending.");
            } else {
                array_push($messages, $result);
            }
            wp_send_json_success([
                "messages" => $messages
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function get_service_partners()
    {
        try {
            ob_start();
            self::check_security();
            $data = self::check_array('data');
            $shipping_method_id = $data["shipping_method_id"];
            $wr_id = $data["wr_id"];
            $country = $data["country"];
            $postcode = $data["postcode"];
            $address = $data["address"];

            $messages = [];

            if (empty($country)) {
                $country = WC()->customer->get_shipping_country();
                if (empty($country)) {
                    throw new Exception("Trenger land pakken skal til for å hente utleveringssteder.");
                }
            }
            if (empty($postcode)) {
                $postcode = WC()->customer->get_shipping_postcode();
                if (empty($postcode)) {
                    throw new Exception("Trenger postkode pakken skal til for å hente utleveringssteder.");
                }
            }
            if (empty($address)) {
                $address = null;
            }
            // try and get transient
            if (Wildrobot_Logistra_Utils::is_test_enviroment()) {
                array_push($messages, "Henter ikke utleveringssteder fra cache pga. testmiljø...");
            } else if (!empty($address)) {
                array_push($messages, "Addresse spesifisert");
            } else if (!empty($shipping_method_id)) {
                $transient_shipping_method_name =
                    'wildrobot-logistra-service-partner-response-' .
                    $country .
                    '-' .
                    $postcode .
                    '-' .
                    $shipping_method_id;
                $transient = get_transient($transient_shipping_method_name);
                if (!empty($transient)) {
                    return wp_send_json_success([
                        "messages" => $messages,
                        "service_partner_data" => $transient
                    ]);
                }
            } else if (!empty($wr_id)) {
                $transient_freight_product_name =
                    'wildrobot-logistra-service-partner-response-' .
                    $country .
                    '-' .
                    $postcode .
                    '-' .
                    $wr_id;
                $transient = get_transient($transient_freight_product_name);
                if (!empty($transient)) {
                    return wp_send_json_success([
                        "messages" => $messages,
                        "service_partner_data" => $transient
                    ]);
                }
            } else {
                throw new Exception("Trenger frakt metode eller transport produkt for å finne utleveringssteder.");
            }

            // Need freightProductId if not set. 
            if (empty($wr_id)) {
                $delivery_relation = Wildrobot_Logistra_DB::get_delivery_relation($shipping_method_id);
                $wr_id = $delivery_relation["wr_id"];
            }

            $transport_agreeement = Wildrobot_Logistra_DB::get_transport_agreement_for_logistra_identifier($wr_id);
            if (empty($transport_agreeement)) {
                throw new Exception("Frakt produkt ikke funnet.");
            }
            if (!$transport_agreeement["service_partner_possible"]) {
                throw new Exception("Dette transportproduktet har ikke mulighet for utleveringsted.");
            }
            $carrier = $transport_agreeement["ta_carrier"]["identifier"];
            if (empty($carrier)) {
                throw new Exception("Kan ikke finne transportør for leveringen.");
            }
            $service_partner_data = Wildrobot_Logistra_Cargonizer::get_service_partners($country, $postcode, $carrier, $wr_id, $address);

            if (!empty($shipping_method_id)) {
                $transient_shipping_method_name =
                    'wildrobot-logistra-service-partner-response-' .
                    $country .
                    '-' .
                    $postcode .
                    '-' .
                    $shipping_method_id;
                set_transient($transient_shipping_method_name, $service_partner_data, 1 * DAY_IN_SECONDS);
            }
            if (!empty($wr_id)) {
                $transient_freight_product_name =
                    'wildrobot-logistra-service-partner-response-' .
                    $country .
                    '-' .
                    $postcode .
                    '-' .
                    $wr_id;
                set_transient($transient_freight_product_name, $service_partner_data, 1 * DAY_IN_SECONDS);
            }
            return wp_send_json_success([
                "messages" => $messages,
                "service_partner_data" => $service_partner_data
            ]);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function get_delivery_relations()
    {
        try {
            ob_start();
            self::check_security();
            $relations = Wildrobot_Logistra_Delivery_Relations::get_all_relations();

            wp_send_json_success($relations);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function update_delivery_relation()
    {
        try {
            ob_start();
            self::check_security();
            $data = self::check_array('data');
            $relation = Wildrobot_Logistra_DB::update_delivery_relation($data);

            wp_send_json_success($relation);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function get_pages()
    {
        try {
            ob_start();
            self::check_security();
            $pages = get_pages();
            wp_send_json_success($pages);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    public static function set_dev()
    {
        try {
            ob_start();
            self::check_security();
            $res = Wildrobot_Logistra_Options::set_dev();
            wp_send_json_success($res);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }


    public static function set_prod()
    {
        try {
            ob_start();
            self::check_security();
            $res = Wildrobot_Logistra_Options::set_prod();
            wp_send_json_success($res);
        } catch (\Throwable $error) {
            wp_send_json_error([
                "message" => $error->getMessage(),
                "code" => $error->getCode()
            ]);
        }
    }

    private static function check_security()
    {
        check_ajax_referer('randomTextForLogistraIntegration', 'security');
    }
    private static function check_array($requestVariableName)
    {
        $sanitized = sanitize_textarea_field($_REQUEST[$requestVariableName]);
        $stripped = stripcslashes($sanitized);
        $decodedArray = json_decode($stripped, true);
        if (is_object($decodedArray)) {
            $decodedArray = json_decode($stripped, true);
        }
        $array = [];
        foreach ($decodedArray as $key => $value) {
            $stripped_key = is_string($key) ? stripcslashes($key) : $key;
            $array[$stripped_key] = self::parse_value($value);
        }
        if (count($array) === 0) {
            return null;
        }
        return $array;
    }
    private static function check_field($requestVariableName)
    {
        $variable = sanitize_text_field($_REQUEST[$requestVariableName]);
        return  self::parse_value($variable);
    }

    private static function parse_value($variable)
    {
        if (is_array($variable) || is_object($variable)) {
            return $variable;
        }
        // parse array
        if (substr($variable, 0, 1) === "[" && substr($variable, strlen($variable) - 1, 1) === "]") {
            $variable = json_decode(stripslashes($variable), true);
            $last_error = json_last_error_msg();
            if ($last_error !== "No error") {
                throw new Exception($last_error);
            }
            return $variable;
        }
        // parse object
        if (substr($variable, 0, 1) === "{" && substr($variable, strlen($variable) - 1, 1) === "}") {
            $decoded_first = json_decode($variable, true, 512, JSON_THROW_ON_ERROR);
            if ($decoded_first === null) {
                $variable = json_decode($variable, true);
            } else {
                $variable = $decoded_first;
            }
            $last_error = json_last_error_msg();
            if ($last_error !== "No error") {
                throw new Exception($last_error);
            }
            return $variable;
        }
        if (
            $variable === 'null' ||
            $variable === 'undefined' ||
            $variable === ''
        ) {
            $variable = "";
        }
        if ($variable === 'true' || $variable === true) {
            $variable = 'yes';
        }
        if ($variable === 'false' || $variable === false) {
            $variable = 'no';
        }
        return $variable;
    }
}

Wildrobot_Logistra_Ajax::init();
