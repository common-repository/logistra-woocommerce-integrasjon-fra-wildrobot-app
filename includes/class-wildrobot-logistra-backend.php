<?php

class Wildrobot_Logistra_Backend
{
	private static $endpoint_consignments = '/cargonizer/shipment/create';
	private static $endpoint_picklist = '/cargonizer/picklist';


	public function __construct($plugin_name, $version)
	{
	}

	public static function create_consignments($consignments)
	{
		$token = get_option('wildrobot_logistra_backend_token', null);
		if ($token === null) {
			throw new Exception("Ingen akksess for Wildrobot server. Vennligst oppdater api nøkler for transportleverandør i innstillinger - Woocommerce - Wildrobot fraktintegrasjon - API Nøkler.");
		}
		$body = json_encode($consignments);
		$logger = new WC_Logger();
		$context = array('source' => 'logistra-robots-consignment');
		$logger->info("=== request === ", $context);
		$logger->info($body, $context);
		$response = wp_remote_post(get_option("wildrobot_logistra_backend_url") . self::$endpoint_consignments, array(
			'headers' => array(
				'Authorization' => "bearer " . $token,
				"Content-type"        => "application/json",
			),
			'body'    => $body,
			// 'timeout' => 20000 // Should not be needed.
		));

		$repsponseBody = wp_remote_retrieve_body($response);
		$responeObject = json_decode($repsponseBody);
		$logger->info("=== response === ", $context);
		$logger->info($repsponseBody, $context);
		if ($responeObject->statusCode === 500) {
			throw new Exception($responeObject->message);
		}
		return $responeObject;
	}

	public static function create_picklist($pdf, $printer_id)
	{

		$token = get_option('wildrobot_logistra_backend_token', null);
		if ($token === null) {
			throw new Exception("Ingen akksess for Wildrobot server. Vennligst oppdater api nøkler for transportleverandør i innstillinger - Woocommerce - Logistra - API Nøkler.");
		}
		$body = json_encode(array(
			"pdf" => base64_encode($pdf),
			"printer" => $printer_id
		));
		$logger = new WC_Logger();
		$context = array('source' => 'wildrobot-logistra-picklist');
		$logger->info("=== request === ", $context);
		$logger->info("Picklist to printer " . $printer_id, $context);
		$response = wp_remote_post(get_option("wildrobot_logistra_backend_url") . self::$endpoint_picklist, array(
			'headers' => array(
				'Authorization' => "bearer " . $token,
				"Content-type"        => "application/json",
			),
			'body'    => $body,
			// 'timeout' => 20000 // Should not be needed.
		));
		$repsponseBody = wp_remote_retrieve_body($response);
		$responeObject = json_decode($repsponseBody);
		$logger->info("=== response === ", $context);
		$logger->info(wc_print_r($repsponseBody, true), $context);
		if ($responeObject->statusCode === 500) {
			if (!empty($responeObject->message)) {
				throw new Exception($responeObject->message);
			}
			throw new Exception("Ukjent feil ved printing av plukkliste.");
		}
		if ($responeObject->statusCode === 202 || $responeObject->statusCode === 200 || $responeObject->statusCode === 201) {
			return $responeObject->message;
		}
		throw new Exception("Ukjent feil ved printing av plukkliste.");
	}
}
