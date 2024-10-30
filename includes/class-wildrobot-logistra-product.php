<?php

class Wildrobot_Logistra_Product
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// Add AJAX action for rendering package fields
		add_action('wp_ajax_render_package_fields', array($this, 'render_package_fields_ajax'));
	}

	public function wildrobot_add_freight_product_fields()
	{
		global $post;
		$product = wc_get_product($post->ID);

		echo '<h5 style="background-color: #f0f0f0; padding: 10px; border-left: 4px solid #FF486A; margin-bottom: 10px;">' . __('Wildrobot produktinnstillinger', 'wildrobot-logistra') . '</h5>';

		echo '<div class="options_group">';

		woocommerce_wp_checkbox(
			array(
				'id'            => '_wildrobot_no_free_freight',
				'value'       => $product->get_meta('_wildrobot_no_free_freight', true),
				'label'         => __('Ekskluder fra gratis frakt', 'wildrobot-logistra'),
				'description'   => __('Hvis valgt, vil dette produktets beløp ikke bidra til å nå grensen for gratis frakt.', 'wildrobot-logistra'),
			)
		);

		echo '<h5 style="background-color: #f0f0f0; padding: 10px; border-left: 4px solid #FF486A; margin-bottom: 10px;">' . __('Wildrobot kolli kontroll', 'wildrobot-logistra') . '</h5>';

		woocommerce_wp_checkbox(
			array(
				'id'            => '_wildrobot_separate_package_for_product',
				'value'       => $product->get_meta('_wildrobot_separate_package_for_product', true),
				'label'         => __('Egen kolli?', 'wildrobot-logistra'),
				'description'   => __('Vil lage en egen kolli ved fratktopprettelse for dette produktet. Flytter dimensjoner og vekt fra produktet til egen kolli.', 'wildrobot-logistra'),
			)
		);
		woocommerce_wp_text_input(array(
			'id'          => '_wildrobot_separate_package_for_product_name',
			'label'       => __('Egen kolli gruppe', 'wildrobot-logistra'),
			'placeholder' => "Transportbeskrivelse av kolli",
			'desc_tip'    => 'true',
			'description' => __('Kan stå tom. Bruker da produkt navnet. Grupper med samme navn vil bli gruppert. Flytter dimensjoner og vekt til egen kolli.', 'wildrobot-logistra'),
			'value'       => $product->get_meta('_wildrobot_separate_package_for_product_name', true),
		));
		echo '</div>';

		echo '<div class="options_group">';

		$package_amount = $product->get_meta('_wildrobot_package_amount', true);
		if (empty($package_amount)) {
			$package_amount = 0;
		}

		woocommerce_wp_text_input(array(
			'id'          => '_wildrobot_package_amount',
			'label'       => __('Antall ekstra kolli', 'wildrobot-logistra'),
			'placeholder' => "Antall kolli",
			'desc_tip'    => 'true',
			'type'        => 'number',
			'description' => __('Antall ekstra "kolli" som produktet består av. Du kan tilpasse navn, dimensjoner og vekt for hver ekstra kolli etter du har lagt til denne. Vekt og dimensjoner for hver ekstra kolli vil overstyre vekt og dimensjoner for produktet. Hvis det ikke er andre produkter i ordren vil det kun være ekstra kolli for dette produktet som vil bli brukt.', 'wildrobot-logistra'),
			'value'       => $package_amount,
			'custom_attributes' => array(
				'min'  => '0',
				'step' => '1',
			),
		));

		echo '<div id="wildrobot_package_fields_container">';
		for ($i = 0; $i < $package_amount; $i++) {
			$this->render_package_fields($i, $product->get_meta("_wildrobot_package_$i", true));
		}
		echo '</div>';

		echo '</div>';

		// Add JavaScript to handle dynamic package fields
?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var packageFieldsContainer = $('#wildrobot_package_fields_container');
				var packageAmountField = $('#_wildrobot_package_amount');

				packageAmountField.on('change', function() {
					var amount = parseInt($(this).val());
					updatePackageFields(amount);
				});

				function updatePackageFields(amount) {
					packageFieldsContainer.empty();
					for (var i = 0; i < amount; i++) {
						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'render_package_fields',
								index: i,
								security: '<?php echo wp_create_nonce("render_package_fields_nonce"); ?>',
								product_id: '<?php echo $post->ID; ?>'
							},
							success: function(response) {
								packageFieldsContainer.append(response);
							}
						});
					}
				}
			});
		</script>
<?php
	}

	private function render_package_fields($index, $package)
	{
		// Check nonce for security
		if (empty($package)) {
			if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'render_package_fields_nonce')) {
				wp_die(__('Security check failed', 'wildrobot-logistra'));
			}
			// Get product ID from POST data
			$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
			$index = isset($_POST['index']) ? intval($_POST['index']) : 0;
			// Validate product ID
			if (!$product_id || !wc_get_product($product_id)) {
				wp_die(__('Invalid product ID', 'wildrobot-logistra'));
			}
			$product = wc_get_product($product_id);
			$package = $product->get_meta("_wildrobot_package_$index", true);
		}


		$dimension_unit = get_option('woocommerce_dimension_unit');
		$weight_unit = get_option('woocommerce_weight_unit');

		echo '<div class="package_fields">';
		echo '<h4 style="margin-left: 10px;">' . sprintf(__('Pakke %d', 'wildrobot-logistra'), $index + 1) . '</h4>';

		woocommerce_wp_text_input(array(
			'id'          => "_wildrobot_package_name_$index",
			'label'       => __('Kolli beskrivelse', 'wildrobot-logistra'),
			'desc_tip'    => 'true',
			'description' => __('Beskrivelse av kolli. Kan stå tom. Bruker da produkt navnet.', 'wildrobot-logistra'),
			'value'       => isset($package['name']) ? $package['name'] : '',
		));
		woocommerce_wp_text_input(array(
			'id'          => "_wildrobot_package_max_amount_$index",
			'label'       => __('Maks antall', 'wildrobot-logistra'),
			'desc_tip'    => 'true',
			'description' => __('Maks antall av dette produktet i en pakke. Ved flere enn dette vil det bli opprettet en enda en pakke. La det stå tomt for at det kun skal være en pakke uansett antall. Sett til 1 dersom et antall produkt av denne genererer et kolli. osv.', 'wildrobot-logistra'),
			'value'       => isset($package['max_amount']) ? $package['max_amount'] : '',
		));
		woocommerce_wp_text_input(array(
			'id'    => "_wildrobot_package_length_$index",
			'label' => sprintf(__('Lengde (%s)', 'wildrobot-logistra'), $dimension_unit),
			'type'  => 'number',
			'value' => isset($package['length']) ? $package['length'] : '',
		));
		woocommerce_wp_text_input(array(
			'id'    => "_wildrobot_package_width_$index",
			'label' => sprintf(__('Bredde (%s)', 'wildrobot-logistra'), $dimension_unit),
			'type'  => 'number',
			'value' => isset($package['width']) ? $package['width'] : '',
		));
		woocommerce_wp_text_input(array(
			'id'    => "_wildrobot_package_height_$index",
			'label' => sprintf(__('Høyde (%s)', 'wildrobot-logistra'), $dimension_unit),
			'type'  => 'number',
			'value' => isset($package['height']) ? $package['height'] : '',
		));
		woocommerce_wp_text_input(array(
			'id'    => "_wildrobot_package_weight_$index",
			'label' => sprintf(__('Vekt (%s)', 'wildrobot-logistra'), $weight_unit),
			'type'  => 'number',
			'value' => isset($package['weight']) ? $package['weight'] : '',
		));
		echo '</div>';
	}

	public function wildrobot_save_freight_product_fields($post_id)
	{
		$product = wc_get_product($post_id);

		$_wildrobot_no_free_freight = $_POST['_wildrobot_no_free_freight'];
		$product->update_meta_data('_wildrobot_no_free_freight', isset($_wildrobot_no_free_freight) ? "yes" : "no");
		$product->save();

		$_wildrobot_separate_package_for_product = $_POST['_wildrobot_separate_package_for_product'];
		$product->update_meta_data('_wildrobot_separate_package_for_product', isset($_wildrobot_separate_package_for_product) ? "yes" : "no");
		$product->save();

		$_wildrobot_separate_package_for_product_name = $_POST['_wildrobot_separate_package_for_product_name'];
		if (isset($_wildrobot_separate_package_for_product_name)) {
			$product->update_meta_data('_wildrobot_separate_package_for_product_name', esc_attr($_wildrobot_separate_package_for_product_name));
			$product->save();
		}

		$package_amount = isset($_POST['_wildrobot_package_amount']) ? intval($_POST['_wildrobot_package_amount']) : 0;
		$product->update_meta_data('_wildrobot_package_amount', $package_amount);


		for ($i = 0; $i < $package_amount; $i++) {
			$package_data = array(
				'name'   => isset($_POST["_wildrobot_package_name_$i"]) ? sanitize_text_field($_POST["_wildrobot_package_name_$i"]) : '',
				'length' => isset($_POST["_wildrobot_package_length_$i"]) ? floatval($_POST["_wildrobot_package_length_$i"]) : '',
				'width'  => isset($_POST["_wildrobot_package_width_$i"]) ? floatval($_POST["_wildrobot_package_width_$i"]) : '',
				'height' => isset($_POST["_wildrobot_package_height_$i"]) ? floatval($_POST["_wildrobot_package_height_$i"]) : '',
				'weight' => isset($_POST["_wildrobot_package_weight_$i"]) ? floatval($_POST["_wildrobot_package_weight_$i"]) : '',
				'max_amount' => isset($_POST["_wildrobot_package_max_amount_$i"]) ? floatval($_POST["_wildrobot_package_max_amount_$i"]) : '',
			);
			$product->update_meta_data("_wildrobot_package_$i", $package_data);
		}

		$product->save();
	}

	// Add this method to the Wildrobot_Logistra_Product class

	public function render_package_fields_ajax()
	{
		check_ajax_referer('render_package_fields_nonce', 'security');

		if (!current_user_can('edit_products')) {
			wp_die(-1);
		}

		$index = isset($_POST['index']) ? intval($_POST['index']) : 0;
		$this->render_package_fields($index, array());
		wp_die();
	}
}
