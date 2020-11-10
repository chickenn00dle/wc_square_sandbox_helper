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

	public function batch_upsert( $base_name, $num_items ) {

		$api     = 'catalog/batch-upsert';
		$method  = 'POST';
		$batches = array();

		foreach( range( 1, $num_items ) as $num ) {
			$name    = $base_name . ' ' . $num;
			$sku     = strtolower( str_replace( ' ', '', $name ) );
			$item_id = '#' . $sku;
			$object  = array(
				"type"                     => "ITEM",
				"id"                       => $item_id,
				"is_deleted"               => false,
				"present_at_all_locations" => true,
				"item_data"                => array(
					"name"         => $name,
					"description"  => $name . " is a test product.",
					"product_type" => "REGULAR",
					"variations"   => array(),
				),
			);

			$object["item_data"]["variations"][] = array(
				"type"                     => "ITEM_VARIATION",
				"id"                       => $item_id . "_variation",
				"is_deleted"               => false,
				"present_at_all_locations" => true,
				"item_variation_data"      => array(
					"item_id"         => $item_id,
					"name"            => $name,
					"pricing_type"    => "FIXED_PRICING",
					"sku"             => $sku,
					"track_inventory" => true,
					"price_money"     => array(
						"amount"   => rand( 1, 10 ) * 1000,
						"currency" => "USD"
					),
				),
			);

			$batches[] = (object) array( "objects" => array( (object) $object ) );
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
