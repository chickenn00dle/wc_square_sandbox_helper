<?php

if ( ! defined( 'ABSPATH' ) ) {

	exit;
}

class WC_Square_Sandbox_API {

	const ENDPOINT = 'https://connect.squareupsandbox.com/v2/';

	const VERSION  = '2020-10-28';

	private $access_token;

	function __construct() {

		$square_settings    = get_option( 'wc_square_settings', array() );
		$this->access_token = $square_settings['sandbox_token'];
	}

	public function batch_upsert( $base_name, $num_items, $max_variations = 1 ) {

		$api     = 'catalog/batch-upsert';
		$method  = 'POST';
		$batches = array();

		foreach( range( 1, $num_items ) as $num ) {
			$object    = new WC_Square_Sandbox_Catalog_Object( $base_name . ' ' . $num, rand( 1, $max_variations ) );
			$batches[] = (object) array( "objects" => array( (object) $object->get_object() ) );
			$uuid      = $this->generate_uuid();
			$request   = (object) array(
				"batches"         => $batches,
				"idempotency_key" => $uuid,
			);
		}

		return $this->request( $request, $api, $method );
	}

	private function generate_uuid() {

		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	private function get_headers() {

		return array(
			'Square-Version' => $this::VERSION,
			'Authorization'  => 'Bearer ' . $this->access_token,
			'Content-Type'   => 'application/json',
		);
	}

	private function get_errors( $response ) {

		$message = "";

		foreach( $response['errors'] as $error ) {
			$message .= $error['code'];
			$message .= ': ';
			$message .= $error['detail'];
			$message .= "\n";
		}

		return $message;
	}

	private function request( $request, $api, $method ) {

		$headers  = $this->get_headers();
		$response = wp_safe_remote_post(
			$this::ENDPOINT . $api,
			array(
				'method'  => $method,
				'headers' => $headers,
				'body'    => wp_json_encode( $request ),
				'timeout' => 70,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( array_key_exists( 'errors', $response_body ) ) {
			return new WP_Error( 'wc_square_sandbox_helper_response', $this->get_errors( $response_body ) );
		}

		return $response_body;
	}
}
