<?php

if ( ! defined( 'ABSPATH' ) ) {

	exit;
}

class WC_Square_Sandbox_API {

	const ENDPOINT = 'https://connect.squareupsandbox.com/v2/';

	const VERSION  = '2020-10-28';

	private $access_token;

	private $location_id;

	function __construct() {

		$square_settings    = get_option( 'wc_square_settings', array() );

		$this->access_token = $square_settings['sandbox_token'];
		$this->location_id  = $square_settings['sandbox_location_id'];
	}

	public function list( $types = '', $cached = false, $save = false ) {

		if ( $cached ) {
			$object_ids = get_option( 'wc_square_sandbox_helper_object_ids', array() );

			return empty( $object_ids ) ? new WP_Error( 'wc_square_sandbox_helper_empty_cache', 'No cached catalog IDs' ) : $object_ids;
		}

		if ( ! $types ) {
			$types = 'ITEM,ITEM_VARIATION';
		}

		$api          = 'catalog/list';
		$method       = 'GET';
		$cursor       = null;
		$object_types = explode( ',', $types );
		$object_ids   = array_combine(
			$object_types,
			array_fill( 0, sizeof( $object_types ), array() )
		);

		do {
			$query_args = array(
				"types"  => urlencode( $types ),
				"cursor" => $cursor
			);

			$response = $this->request( null, $api, $method, $query_args );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( ! isset( $response['objects'] ) ) {
				return new WP_Error( 'wc_square_sandbox_helper_empty_response', 'Square catalog is empty.' );
			}

			foreach ( $response['objects'] as $object ) {
				$object_ids[ $object['type'] ][] = $object['id'];
			}

			$cursor = isset( $response['cursor'] ) ? $response['cursor'] : null;

		} while ( $cursor );

		if ( $save ) {
			update_option( 'wc_square_sandbox_helper_object_ids', $object_ids );
		}

		return $object_ids;
	}

	public function batch_upsert( $base_name, $num_items, $max_variations = 1 ) {

		$api     = 'catalog/batch-upsert';
		$method  = 'POST';
		$batches = array();

		foreach( range( 1, $num_items ) as $num ) {
			$object    = new WC_Square_Sandbox_Catalog_Object( $base_name . ' ' . $num, rand( 1, $max_variations ) );
			$batches[] = (object) array( "objects" => array( (object) $object->get_object() ) );
		}

		$data   = (object) array(
			"batches"         => $batches,
			"idempotency_key" => $this->generate_uuid(),
		);

		$response = $this->request( $data, $api, $method );

		if ( ! is_wp_error( $response ) ) {

			$object_ids = array(
				'ITEM'           => array(),
				'ITEM_VARIATION' => array(),
			);

			foreach( $response['id_mappings'] as $object ) {
				if ( strpos( $object['client_object_id'], 'variation' ) ) {
					$object_ids['ITEM_VARIATION'][] = $object['object_id'];
				} else {
					$object_ids['ITEM'][] = $object['object_id'];
				}
			}

			$new_object_ids = array_merge_recursive( $object_ids, get_option( 'wc_square_sandbox_helper_object_ids', array() ) );
			update_option( 'wc_square_sandbox_helper_object_ids', $new_object_ids );
		}

		return $response;
	}

	public function batch_delete( $object_ids = null ) {

		$api                = 'catalog/batch-delete';
		$method             = 'POST';
		$cached_object_ids  = get_option( 'wc_square_sandbox_helper_object_ids', array() );
		$deleted_object_ids = array();

		if ( ! $object_ids ) {
			$object_ids        = array();

			foreach( $cached_object_ids as $object_ids_array ) {
				$object_ids = array_merge( $object_ids, $object_ids_array );
			}
		}

		if ( empty( $object_ids ) ) {
			return new WP_Error( 'wc_square_sandbox_helper_request', 'No cached catalog IDs.' );
		}

		$batches = array_chunk( $object_ids, 200 );

		foreach( $batches as $batch ) {
			$data     = (object) array( "object_ids" => $batch );
			$response = $this->request( $data, $api, $method );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			if ( isset( $response['deleted_object_ids'] ) ) {
				$deleted_object_ids = array_merge( $deleted_object_ids, $response['deleted_object_ids'] );
			}
		}

		if ( empty( $deleted_object_ids ) ) {
			return new WP_Error( 'wc_square_sandbox_helper_response_deleted_object_ids', 'No catalog IDs to delete.' );
		}

		foreach( $cached_object_ids as $type => $object_ids_array ) {
			foreach( $deleted_object_ids as $object_id ) {
				if ( in_array( $object_id, $object_ids_array ) ) {
					unset( $cached_object_ids[ $type ][ array_search( $object_id, $object_ids_array ) ] );
					continue;
				}
			}
		}

		update_option( 'wc_square_sandbox_helper_object_ids', $cached_object_ids );

		foreach( $deleted_object_ids as $object_id ) {
			$product = WooCommerce\Square\Handlers\Product::get_product_by_square_variation_id( $object_id );

			if ( $product ) {
				$product->delete( true );

				if ( $product->is_type( 'variation' ) && $parent = wc_get_product( $product->get_parent_id() ) ) {
					$parent->delete( true );
				}
			}
		}

		return $object_ids;
	}

	public function batch_change( $quantity, $object_ids = null ) {

		$api     = 'inventory/batch-change';
		$method  = 'POST';

		if ( ! $object_ids ) {
			$cached_object_ids = get_option( 'wc_square_sandbox_helper_object_ids', array() );
			$object_ids        = ! empty( $cached_object_ids ) && isset( $cached_object_ids['ITEM_VARIATION'] ) ? $cached_object_ids['ITEM_VARIATION'] : null;
		}

		if ( ! $object_ids ) {
			return new WP_Error( 'wc_square_sandbox_helper_request', 'No cached item variation catalog IDs.' );
		}

		$batches = array_chunk( $object_ids, 100 );

		foreach( $batches as $batch ) {

			$changes = array();

			foreach( $batch as $object_id ) {
				$changes[] = (object) array(
					"type"       => "ADJUSTMENT",
					"adjustment" => (object) array(
						"catalog_object_id"   => $object_id,
						"from_state"          => "NONE",
						"to_state"            => "IN_STOCK",
						"location_id"         => $this->location_id,
						"quantity"            => $quantity,
						"occurred_at"         => gmdate( "Y-m-d\TH:m:s.u\Z" ),
					),
				);
			}

			$data = (object) array(
				"changes"         => $changes,
				"idempotency_key" => $this->generate_uuid(),
			);

			$response = $this->request( $data, $api, $method );

			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		return $object_ids;
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

	private function request( $data, $api, $method, $query_args = null ) {

		$headers  = $this->get_headers();
		$url      = $this::ENDPOINT . $api;
		$args     = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 150,
		);

		if ( $query_args ) {
			$url = add_query_arg( $query_args, $url );
		}

		if ( $data ) {
			$args['body'] = wp_json_encode( $data );
		}

		$response = wp_safe_remote_post(
			$url,
			$args
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response_body ) ) {
			return new WP_Error( 'wc_square_sandbox_helper_response_empty', 'Empty response from Square API' );
		}

		if ( array_key_exists( 'errors', $response_body ) && isset( $response_body['errors'] ) ) {
			return new WP_Error( 'wc_square_sandbox_helper_response_error', $this->get_errors( $response_body ) );
		}

		return $response_body;
	}
}
