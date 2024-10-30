<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wildrobot.app/wildrobot-logistra-cargonizer-woocommerce-integrasjon/
 * @since      1.0.0
 *
 * @package    Wildrobot_Logistra
 * @subpackage Wildrobot_Logistra/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wildrobot_Logistra
 * @subpackage Wildrobot_Logistra/admin
 * @author     Robertosnap <robertosnap@pm.me>
 */
class Wildrobot_Logistra_Admin
{
	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}


	public function order_actions($order)
	{
		// We need to attach some data to the DOM element to instantiate the REACT components correctly and wihtout extra backend calls
		// To show if order has been sent
		$order_id = $order->get_id();
		$sent = $order->get_meta('logistra-robots-sent', true) == 'yes' ? 'true' : 'false';
		$show_label_download = get_option("wildrobot_logistra_show_label_after_send_order") === "yes" ? 'true' : 'false';
		$label_url = $order->get_meta('logistra-robots-freight-label-url', true);
		// To distiguish each button /vue instance

		if (get_option("wildrobot_logistra_hide_send_buttons", "no") !== "yes") {
			echo "<div class='wildrobot-logistra-order-action-send' data-id=" . $order_id . ' data-sent=' . $sent . ' data-label=' . $show_label_download . ' data-labelUrl=' . $label_url . '></div>';
		}
		if (get_option("wildrobot_logistra_hide_override_buttons", "no") !== "yes") {
			echo "<div class='wildrobot-logistra-order-action-override' data-id=" . $order_id . ' data-sent=' . $sent . ' ></div>';
		}
		if (get_option("wildrobot_logistra_picklist_active") === "yes") {
			$picked = $order->get_meta('wildrobot-logistra-picklist-created', true) == 'yes' ? 'true' : 'false';
			echo "<div class='wildrobot-logistra-picklist-order' data-id=" . $order_id . ' data-picked=' . $picked . ' ></div>';
		}
	}

	// public function add_wildrobot_download_label_actions_button($actions, $order)
	// {
	// 	if (get_option("wildrobot_logistra_show_label_download") === "yes") {
	// 		$order_id = $order->get_id();
	// 		$label = $order->get_meta('logistra-robots-freight-label-url', true);
	// 		if (!empty($label)) {
	// 			$actions['wildrobot_download_label'] = array(
	// 				'url'       => $label,
	// 				'name'      => __('Last ned fraktetikett', 'wildrobot-logistra'),
	// 				'action'    => 'wildrobot_download_label',
	// 			);
	// 		}
	// 	}
	// 	return $actions;
	// }
	// function add_wildrobot_download_label_actions_button_css()
	// {
	// 	echo '<style>.wildrobot_download_label::after { font-family: Dashicons; content: "\f323" !important; }</style>';
	// }

	public function edit_order_deliver_order_box()
	{
		add_meta_box('wildrobot_logistra_send', __('Frakt', 'wildrobot-logistra'), [$this, 'edit_order_box_content_send'], 'shop_order', 'side', 'high');
		add_meta_box('wildrobot_logistra_send', __('Frakt', 'wildrobot-logistra'), [$this, 'edit_order_box_content_send'], 'woocommerce_page_wc-orders', 'side', 'high');  // WC 7.1
	}

	public function edit_order_return_order_box()
	{
		add_meta_box('wildrobot_logistra_return', __('Retur', 'wildrobot-logistra'), [$this, 'edit_order_box_content_return'], 'shop_order', 'side', 'high');
		add_meta_box('wildrobot_logistra_return', __('Retur', 'wildrobot-logistra'), [$this, 'edit_order_box_content_return'], 'woocommerce_page_wc-orders', 'side', 'high'); // WC 7.1
	}

	public function edit_order_box_content_send($post)
	{
		$order = wc_get_order($post->ID);
		if ($order) {
			$order_id = $order->get_id();
			$sent = $order->get_meta('logistra-robots-sent', true) === 'yes' ? 'true' : 'false';
			$show_label_download = get_option('wildrobot_logistra_show_label_after_send_order') === 'yes' ? 'true' : 'false';
			$label_url = esc_url($order->get_meta('logistra-robots-freight-label-url', true));

			if (get_option("wildrobot_logistra_hide_send_buttons", "no") !== "yes") {
				echo "<div id='wildrobot-logistra-send-order-content-box' data-id=" . $order_id . ' data-sent=' . $sent . '  data-label=' . $show_label_download . '  data-labelUrl=' . $label_url . ' ></div>';
			}
			if (get_option("wildrobot_logistra_hide_override_buttons", "no") !== "yes") {
				echo "<div id='wildrobot-logistra-override-order-content-box' data-id=" . $order_id . ' data-sent=' . $sent . ' ></div>';
			}
			// Add picklist if active to the order content box
			if (get_option("wildrobot_logistra_picklist_active") === "yes") {
				$picked = $order->get_meta('wildrobot-logistra-picklist-created', true) == 'yes' ? 'true' : 'false';
				echo "<div id='wildrobot-logistra-picklist-content-box' data-id=" . $order_id . ' data-picked=' . $picked . ' ></div>';
			}
		}
	}

	public function edit_order_box_content_return($post)
	{
		$order = wc_get_order($post->ID);
		if ($order) {
			$order_id = $order->get_id();
			$sent = $order->get_meta('logistra-robots-sent-return', true) == 'yes' ? 'true' : 'false';
			echo "<div id='wildrobot-logistra-return-order-content-box' data-id=" . $order_id . ' data-sent=' . $sent . '></div>';
			if ($sent === "true") {
				echo '<span class="description"><span class="woocommerce-help-tip"></span> Retur allrede opprettet.</span>';
			}
		}
	}

	public function display_logistra_robots_settings()
	{
		if (!current_user_can('manage_options')) {
			echo "<div id='wildrobot-logistra-settings-app-not-admin'>Laster Wildrobot fraktintegrasjon for brukere...</div>";
		} else {
			echo "<div id='wildrobot-logistra-settings-app'>Laster Wildrobot fraktintegrasjon ...</div>";
		}
	}

	public function add_woocommerce_settings_tab($settings_tabs)
	{
		$tab_name = $this->plugin_name . '_tab';
		$settings_tabs[$tab_name] = __('Wildrobot fraktintegrasjon', 'wildrobot-logistra');
		return $settings_tabs;
	}

	public function special_script_enqueue_tags($tag, $handle, $src)
	{
		// if not your script, do nothing and return original $tag
		if ($this->plugin_name . "_index" === $handle) {
			// return '<script type="module" src="' . esc_url($src) . '"></script>';;
			return '<script type="module" src="' . esc_url($src) . '"></script>';
		}
		return $tag;
	}

	public function wildrobot_woocommerce_submenu_link()
	{
		add_submenu_page('woocommerce', __('Wildrobot Innstillinger', "wildrobot-logistra"), __('Wildrobot Innstillinger', "wildrobot-logistra"), 'manage_options', admin_url('admin.php?page=wc-settings&tab=wildrobot-logistra_tab'));
		add_submenu_page('woocommerce', __('Wildrobot Plukk & Lever', "wildrobot-logistra"), __('Wildrobot Plukk & Lever', "wildrobot-logistra"), 'edit_shop_orders', "wildrobot-pick-and-delivery", [$this, 'display_pick_and_delivery_page']);
	}

	public function display_pick_and_delivery_page($order)
	{
		echo '<h3>Plukk og levering</h3>
        <div id="wildrobot-logistra-pick-and-delivery"></div>';
	}

	public function add_wildrobot_order_meta_values_to_order_query($query, $query_vars)
	{
		if (!empty($query_vars['logistra-robots-sent'])) {
			$query['meta_query'][] = array(
				'key' => 'logistra-robots-sent',
				'value' => esc_attr($query_vars['logistra-robots-sent']),
			);
		}
		if (!empty($query_vars['wildrobot-logistra-picklist-created'])) {
			$query['meta_query'][] = array(
				'key' => 'wildrobot-logistra-picklist-created',
				'value' => esc_attr($query_vars['wildrobot-logistra-picklist-created']),
			);
		}
		if (!empty($query_vars['wildrobot-logistra-picklist-completed'])) {
			$query['meta_query'][] = array(
				'key' => 'wildrobot-logistra-picklist-completed',
				'value' => esc_attr($query_vars['wildrobot-logistra-picklist-completed']),
			);
		}

		return $query;
	}

	public function add_service_partner_picker_field_to_checkout($order)
	{
		echo '<p><strong>' . __('Utleveringssted') . ':</strong> ' . $order->get_meta('_shipping_service_partner', true) . '</p>';
	}

	public function display_tracking_in_email($fields, $sent_to_admin, $order)
	{
		if (get_option('wildrobot_logistra_freight_track_url_email', "no") === 'yes') {
			$tracking_url = $order->get_meta('logistra-robots-tracking-url', true);
			if (strlen($tracking_url) > 0) {
				$tracking_field = ['label' => __('Sporing', 'logistra-robots'), 'value' => __(wp_sprintf('<a class="wildrobot-logistra-tracking-url" href="%s">Klikk her</a>', $tracking_url))];
				array_push($fields, apply_filters("logistra-robots-freight-track-email-field", $tracking_field, $fields, $sent_to_admin, $order));
			}
		}
		return $fields;
	}

	public function display_tracking_in_email_second($order, $sent_to_admin, $plain_text, $email)
	{
		if (get_option('wildrobot_logistra_freight_track_url_email', "no") === 'top') {
			$tracking_url = $order->get_meta('logistra-robots-tracking-url', true);
			if (strlen($tracking_url) > 0) {
				$tracking_field = ['label' => __('Sporing', 'logistra-robots'), 'value' => __(wp_sprintf('<a class="wildrobot-logistra-tracking-url" href="%s">Klikk her</a>', $tracking_url))];
				echo '<p class="wildrobot-logistra-tracking-url-label" style="font-weight: bold;">' . $tracking_field["label"] . ': ' . $tracking_field["value"] . '</p>';
			}
		}
	}

	public function enqueue_styles()
	{
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ReactToastify.css', array(), $this->version, 'all');
	}

	public function enqueue_scripts()
	{

		$php_to_js_variables = array(
			'version' => $this->version,
			'wc_ajax_url' => WC()->ajax_url(),
			'security' => wp_create_nonce("randomTextForLogistraIntegration"),
		);

		$options = Wildrobot_Logistra_Options::get_admin_options();

		$admin_js = self::get_js_file_path('partials/frontend', 'admin');
		wp_enqueue_script($this->plugin_name . "-admin-js", $admin_js, ['wp-element'], $this->version, true);
		wp_localize_script($this->plugin_name . '-admin-js', 'wildrobotLogistraAdmin', array_merge($php_to_js_variables, $options));
	}

	private static function get_js_file_path($folder, $file)
	{
		$files = glob(plugin_dir_path(__FILE__) . $folder . '/' . $file . '*.js');
		if (!empty($files)) {
			$full_path = $files[0];
			$app_pos = strpos($full_path, $file . '.');
			$file_name = substr($full_path, $app_pos);
			return plugin_dir_url(__FILE__) . $folder . '/' . $file_name;
		} else {
			return 'http://localhost:3000/static/js/client.js';
		}
	}
}
