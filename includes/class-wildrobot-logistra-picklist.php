<?php

class Wildrobot_Logistra_Picklist
{

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}


	public function bulk_actions_picklist_order($actions)
	{
		if (get_option("wildrobot_logistra_picklist_active") === "yes") {
			$actions['picklist_order'] = __('Lag plukkliste ', 'wildrobot-logistra');
		}
		return $actions;
	}

	public static function create_picklist_pdf($order_id)
	{
		$document = wcpdf_get_packing_slip($order_id, true);
		$pdf = $document->get_pdf();
		return $pdf;
	}

	public static function picklist_qr_code_url($order_id)
	{
		// https://developers.google.com/chart/infographics/docs/qr_codes
		$base_api_url = "https://api.qrserver.com/v1/create-qr-code/";
		$qr_code_url = add_query_arg([
			"size" => "50x50",
			"data" => self::picklist_fullfill_url($order_id),
			// "choe" => "Shift_JIS"
		], $base_api_url);

		return $qr_code_url;
	}

	public static function picklist_fullfill_url($order_id)
	{
		// https://docs.wpovernight.com/category/woocommerce-pdf-invoices-packing-slips/
		$picklist_page = get_option('wildrobot_logistra_picklist_page', null);
		$url = add_query_arg(array(
			"order_id" => $order_id
		), get_page_link($picklist_page));
		// return str_replace("&", "%26", $url);
		return urlencode(str_replace("&", "%26", $url));
		// return urlencode($url);
	}


	// ADD QR CODE to packing slip 
	function wildrobot_logistra_packing_slip_qr_code($template_type, $order)
	{
		if (get_option("wildrobot_logistra_picklist_active") === "yes") {
			$order_id = $order->get_id();

			$qr_url = self::picklist_qr_code_url($order_id);

			// $document = wcpdf_get_document($template_type, $order);
			if ($template_type == 'packing-slip') { ?>
				<div class="qr-code" style="display:inline;">
					<img src="<?php echo $qr_url ?>">
				</div>
			<?php }
		}
	}

	static function wpo_wcpdf_custom_styles($document_type, $document)
	{
		if (get_option("wildrobot_logistra_picklist_active") === "yes") {
			?>
			@page {
			margin-top: 2mm;
			margin-bottom: 2mm;
			margin-left: 4mm;
			margin-right: 2mm;
			}

			.document-type-label {
			float:left;
			}

			h1 {
			font-size: 8pt;
			margin: 5mm 0;
			}

			body {
			font-size: 6pt;
			}

			table.head {
			margin-bottom: 2mm;
			}
<?php
		}
	}

	static function wcpdf_custom_mm_page_size($paper_format, $template_type)
	{
		if (get_option("wildrobot_logistra_picklist_active") === "yes") {
			// change the values below
			$width = 102; //mm!
			$height = 192; //mm!

			//convert mm to points
			$paper_format = array(0, 0, ($width / 25.4) * 72, ($height / 25.4) * 72);
		}

		return $paper_format;
	}

	public static function create_picklist($order_id)
	{
		if (empty($order_id)) {
			throw new Exception(__('Må ha ordre id for å generere plukkliste.', 'logistra-robots'));
		}
		// change layout and style on packing-slip so it fits printer.
		add_filter('wpo_wcpdf_paper_format', [__CLASS__, 'wcpdf_custom_mm_page_size'], 10, 2);
		add_action('wpo_wcpdf_custom_styles', [__CLASS__, 'wpo_wcpdf_custom_styles'], 10, 2);
		// Create and send a ready PDF picklist for print
		$pdf = self::create_picklist_pdf($order_id);
		$printer_id = get_option("wildrobot_logistra_picklist_printer");
		if (empty($printer_id)) {
			throw new Exception("Vennligst velg en plukkliste printer i Wildrobot plukkliste innstillinger.");
		}
		$res = Wildrobot_Logistra_Backend::create_picklist($pdf, $printer_id);
		$order = new WC_Order($order_id);
		$order->update_meta_data('wildrobot-logistra-picklist-created', "yes");
		$order->save();
		return $res;
	}

	public function bulk_picklist_order($redirect_to, $action, $post_ids)
	{
		if ($action !== 'picklist_order') {
			return $redirect_to;
		} // Exit
		try {

			$logger = new WC_Logger();
			$context = ['source' => 'wildrobot-logistra-picklist'];
			$logger->info('_________Startet bulk plukkliste generering______________', $context);
			$logger->info(wc_print_r($post_ids, true), $context);

			$processed_ids = [];
			$error_ids = [];
			foreach ($post_ids as $order_id) {
				try {
					$picklist_res = self::create_picklist($order_id);
				} catch (\Throwable $error) {
					$logger->error("Feil med ordre " . $order_id . ". " . $error->getMessage(), $context);
					$error_ids[] = $order_id;
					continue;
				}
				$logger->info("Opprettet plukkliste for " . $order_id . " med bulk. " . wc_print_r($picklist_res, true), $context);
				$processed_ids[] = $order_id;
			}
			set_transient('wildrobot_picklist_notices_success', $processed_ids, 5);
			set_transient('wildrobot_picklist_notices_error', $error_ids, 5);
			return $redirect_to = add_query_arg(['picklist_order' => 1, 'processed_count' => count($processed_ids), 'errors_count' => count($error_ids), 'processed_ids' => implode(',', $processed_ids),], $redirect_to);
		} catch (\Throwable $error) {
			echo $error->getMessage();
		}
	}

	public function display_bulk_picklist_notices()
	{
		// Display processed_ids
		$bulk_processed_ids = get_transient('wildrobot_picklist_notices_success');
		if ($bulk_processed_ids) {
			echo '<div class="notice notice-success is-dismissible">
            <p>Opprettet plukkliste:</p>
            <ul>';
			foreach ($bulk_processed_ids as $orderId) {
				echo "<li>Ordre: $orderId</li>";
			}
			echo '</ul></div>';

			// Don't forget to delete the transient
			delete_transient('wildrobot_picklist_notices_success');
		}

		// Display error_ids
		$bulk_error_ids = get_transient('wildrobot_picklist_notices_error');
		if ($bulk_error_ids) {
			echo '<div class="notice notice-error is-dismissible">
            <p>Feil ved plukkliste opprettelse:</p>
            <ul>';
			foreach ($bulk_error_ids as $orderId) {
				echo "<li>Ordre: $orderId</li>";
			}
			echo '</ul></div>';

			// Don't forget to delete the transient
			delete_transient('wildrobot_picklist_notices_error');
		}
	}
}
// Wildrobot_Logistra_Order_Utils::init();
