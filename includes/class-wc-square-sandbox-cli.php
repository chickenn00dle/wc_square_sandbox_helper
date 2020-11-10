<?php

if ( ! defined( 'ABSPATH' ) ) {

	exit;
}

class WC_Square_Sandbox_CLI {

	private $api;

	function __construct( $api ) {

		$this->api = $api;
	}

	public function batch_upsert( $args, $assoc_args ) {

		if ( 2 !== sizeof( $args ) ) {
			WP_CLI::error( 'Invalid number of arguments. Please provide the following: \n 1) Base name for generated products\n 2) Total number of items to generate' );
		}

		if ( ! is_string( $args[0] ) ) {
			WP_CLI::error( 'Invalid first argument. Base name must be of type string.' );
		}

		if ( ! is_numeric( $args[1] ) ) {
			WP_CLI::error( 'Invalid second argument. Total must be of type int.' );
		}

		$result = $this->api->batch_upsert( $args[0], $args[1] );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( 'Batch upsert complete!' );
	}
}
