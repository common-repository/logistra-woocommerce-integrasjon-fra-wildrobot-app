<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wildrobot.app/wildrobot-logistra-cargonizer-woocommerce-integrasjon/
 * @since      1.0.0
 *
 * @package    Wildrobot_Logistra
 * @subpackage Wildrobot_Logistra/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wildrobot_Logistra
 * @subpackage Wildrobot_Logistra/includes
 * @author     Robertosnap <robertosnap@pm.me>
 */
class Wildrobot_Logistra
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wildrobot_Logistra_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('WILDROBOT_LOGISTRA_VERSION')) {
			$this->version = WILDROBOT_LOGISTRA_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wildrobot-logistra';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wildrobot_Logistra_Loader. Orchestrates the hooks of the plugin.
	 * - Wildrobot_Logistra_i18n. Defines internationalization functionality.
	 * - Wildrobot_Logistra_Admin. Defines all hooks for the admin area.
	 * - Wildrobot_Logistra_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wildrobot-logistra-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wildrobot-logistra-public.php';

		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-frontend.php';

		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-ajax.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-options.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-migration.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-cargonizer.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-delivery-relations.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-db.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-consignment.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-consignment-order.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-utils.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-order-utils.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-dhl.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-backend.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-picklist.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/wildrobot-logistra-shipping-method.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-user.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-order-automation.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wildrobot-logistra-product.php';

		$this->loader = new Wildrobot_Logistra_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wildrobot_Logistra_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Wildrobot_Logistra_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$admin = new Wildrobot_Logistra_Admin($this->get_plugin_name(), $this->get_version());
		$migration = new Wildrobot_Logistra_Migration($this->get_plugin_name(), $this->get_version());
		$options = new Wildrobot_Logistra_Options($this->get_plugin_name(), $this->get_version());
		$dhl = new Wildrobot_Logistra_DHL($this->get_plugin_name(), $this->get_version());
		$order_utils = new Wildrobot_Logistra_Order_Utils($this->get_plugin_name(), $this->get_version());
		$delivery_relation = new Wildrobot_Logistra_Delivery_Relations($this->get_plugin_name(), $this->get_version());
		$picklist = new Wildrobot_Logistra_Picklist($this->get_plugin_name(), $this->get_version());
		$user = new Wildrobot_Logistra_User($this->get_plugin_name(), $this->get_version());
		$order_automation = new Wildrobot_Logistra_Order_Automation($this->get_plugin_name(), $this->get_version());
		$product = new Wildrobot_Logistra_Product($this->get_plugin_name(), $this->get_version());
		$cargonizer = new Wildrobot_Logistra_Cargonizer($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
		$this->loader->add_action('woocommerce_settings_tabs_' . $this->get_plugin_name() . '_tab', $admin, 'display_logistra_robots_settings');
		$this->loader->add_action('woocommerce_settings_tabs_array', $admin, 'add_woocommerce_settings_tab', 90);
		$this->loader->add_filter('script_loader_tag', $admin, 'special_script_enqueue_tags', 10, 3);
		$this->loader->add_filter('admin_menu', $admin, 'wildrobot_woocommerce_submenu_link', 99);
		$this->loader->add_filter('woocommerce_admin_order_actions_end', $admin, 'order_actions');
		$this->loader->add_filter('add_meta_boxes', $admin, 'edit_order_deliver_order_box');
		$this->loader->add_filter('add_meta_boxes', $admin, 'edit_order_return_order_box');
		$this->loader->add_action('woocommerce_admin_order_data_after_shipping_address', $admin, 'add_service_partner_picker_field_to_checkout', 10, 1);
		$this->loader->add_action('woocommerce_email_order_meta_fields', $admin, 'display_tracking_in_email', 10, 3);
		$this->loader->add_action('woocommerce_email_order_details', $admin, 'display_tracking_in_email_second', 10, 4);
		$this->loader->add_action('woocommerce_order_data_store_cpt_get_orders_query', $admin, 'add_wildrobot_order_meta_values_to_order_query', 10, 2);
		// $this->loader->add_filter('woocommerce_admin_order_actions', $admin, 'add_wildrobot_download_label_actions_button', 10, 2);
		// $this->loader->add_action('admin_head', $admin, 'add_wildrobot_download_label_actions_button_css');

		// Order automation
		// $this->loader->add_action('woocommerce_new_order', $adorder_automationmin, 'run_wildrobot_order_automation_trigger_new_order', 10, 2);
		// $this->loader->add_action('admin_init', $order_automation, 'setup_wildrobot_order_automation', 10, 2);
		$this->loader->add_action('woocommerce_order_status_changed', $order_automation, 'run_wildrobot_order_automation_trigger_status_changed', 10, 3);

		// add_action('woocommerce_admin_order_actions_end', [$this, 'sendOrderAction',]);

		$this->loader->add_action('admin_init', $migration, 'run_migrations');

		$this->loader->add_action('admin_init', $options, "set_semi_constants");

		// Add custom fields to product shipping tab
		$this->loader->add_action('woocommerce_product_options_shipping', $dhl, "logistra_robots_add_shipping_option_commodity_code");
		// Save the custom fields values as meta data
		$this->loader->add_action('woocommerce_process_product_meta', $dhl, "logistra_robots_save_shipping_option_commodity_code");

		// Add custom fields to product shipping tab
		$this->loader->add_action('woocommerce_product_options_shipping', $product, "wildrobot_add_freight_product_fields", 1);
		// Save the custom fields values as meta data
		$this->loader->add_action('woocommerce_process_product_meta', $product, "wildrobot_save_freight_product_fields");

		// deliver orden on order status change to complete
		$this->loader->add_action('woocommerce_order_status_completed', $order_utils, "send_order", 10);

		// Adding to admin order list bulk dropdown a custom action 'send_order_transport'
		$this->loader->add_filter('bulk_actions-edit-shop_order', $order_utils, "bulk_actions_send_order_transport", 20, 1);
		$this->loader->add_filter('bulk_actions-woocommerce_page_wc-orders', $order_utils, "bulk_actions_send_order_transport", 20, 1); // wc 7.1

		$this->loader->add_filter('bulk_actions-edit-shop_order', $picklist, "bulk_actions_picklist_order", 20, 1);
		$this->loader->add_filter('bulk_actions-woocommerce_page_wc-orders', $picklist, "bulk_actions_picklist_order", 20, 1);
		// Action for bulk
		$this->loader->add_filter('handle_bulk_actions-edit-shop_order', $order_utils, "bulk_send_order", 10, 3);
		$this->loader->add_filter('handle_bulk_actions-edit-shop_order', $picklist, "bulk_picklist_order", 10, 3);
		// Show bulk action notices
		$this->loader->add_action('admin_notices', $order_utils, "display_bulk_action_notices");
		$this->loader->add_action('admin_notices', $picklist, "display_bulk_picklist_notices");

		// 
		$this->loader->add_action('wildrobot_check_order_no_consignment_response', $order_utils, "wildrobot_check_order_no_consignment_response_function", 10, 2);
		$this->loader->add_action('admin_notices', $order_utils, "display_notice_if_user_has_orders_not_responded");
		$this->loader->add_action('admin_init', $order_utils, "wildrobot_logistra_handle_dismiss_notice");


		// add qr code to packing slip
		$this->loader->add_action('wpo_wcpdf_after_document_label', $picklist, "wildrobot_logistra_packing_slip_qr_code", 10, 3);

		// delete delivery relation on shipping method delete
		$this->loader->add_action('woocommerce_shipping_zone_method_deleted', $delivery_relation, "delete_relation", 10, 3);

		// add user options 
		$this->loader->add_action('show_user_profile', $user, "user_printer");
		$this->loader->add_action('edit_user_profile', $user, "user_printer");
		$this->loader->add_action('personal_options_update', $user, "save_user_printer");
		$this->loader->add_action('edit_user_profile_update', $user, "save_user_printer");

		$this->loader->add_action('wp_ajax_wildrobot_logistra_print_consignment', $cargonizer, "wildrobot_logistra_print_consignment_ajax");
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Wildrobot_Logistra_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('woocommerce_locate_template', $plugin_public, 'wildrobot_logistra_locate_template', 1, 3);
		$this->loader->add_filter('woocommerce_checkout_fields', $plugin_public, 'add_service_partner_picker_field');
		$this->loader->add_filter('logistra_robots_service_partner_select', $plugin_public, 'logistra_robots_cart_shipping_template_args', 1, 2);
		$this->loader->add_action('woocommerce_checkout_order_processed', $plugin_public, 'wildrobot_logistra_woocommerce_checkout_process', 10, 3);
		$this->loader->add_filter('the_content', $plugin_public, 'add_picklist_to_page');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wildrobot_Logistra_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
