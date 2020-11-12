<?php

use function PHPSTORM_META\type;

if ( ! defined( 'ABSPATH' ) ) {

	exit;
}

class WC_Square_Sandbox_CLI {

	private $api;

	function __construct( $api ) {

		$this->api = $api;
	}

	public function list( $args, $assoc_args ) {

		if ( 0 !== sizeof( $args ) ) {
			WP_CLI::error( 'list takes no arguments.' );
		}

		if ( 1 < sizeof( $assoc_args ) ) {
			unset( $assoc_args['save'] );
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		if ( 1 === sizeof( $assoc_args ) && ! isset( $assoc_args['save'] ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		$result = $this->api->list( isset( $assoc_args['save'] ) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( 'Catalog IDs fetched:' );

		foreach ( $result as $catalog_id ) {
			WP_CLI::line( $catalog_id );
		}
	}

	public function batch_upsert( $args, $assoc_args ) {

		if ( 2 !== sizeof( $args ) ) {
			WP_CLI::error( 'Invalid number of arguments. Please provide the following: \n 1) Base name for generated products\n 2) Total number of items to generate' );
		}

		if ( ! is_string( $args[0] ) ) {
			WP_CLI::error( 'Invalid first argument. Base name must be of type string.' );
		}

		if ( ! is_numeric( $args[1] ) ) {
			WP_CLI::error( 'Invalid second argument. Total must be an integer.' );
		}

		if ( 500 < $args[1] ) {
			WP_CLI::error( 'Invalid second argument. Total must 500 or less.' );
		}

		if ( 0 < sizeof( $assoc_args ) && ! isset( $assoc_args['max_variations'] ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		if ( 0 < sizeof( $assoc_args ) ) {
			if ( ! is_numeric( $assoc_args['max_variations'] ) ) {
				WP_CLI::error( 'Option max_variations must be an integer.' );
			}

			$result = $this->api->batch_upsert( $args[0], $args[1], $assoc_args['max_variations'] );
		} else {
			$result = $this->api->batch_upsert( $args[0], $args[1] );
		}

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( 'Batch upsert complete!' );
	}

	public function batch_delete( $args, $assoc_args ) {

		if ( 0 < sizeof( $args ) ) {
			foreach( $args as $arg ) {
				if ( is_numeric( $arg ) ) {
					WP_CLI::error( 'Invalid argument supplied: ' . $arg );
				}
			}
		}

		if ( 0 !== sizeof( $assoc_args ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		if ( 0 < sizeof( $args ) ) {
			$result = $this->api->batch_delete( $args );
		} else {
			$result = $this->api->batch_delete();
		}

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( 'Batch delete complete!' );
	}

	public function batch_change( $args, $assoc_args ) {

		if ( 0 === sizeof( $args ) ) {
			WP_CLI::error( 'Invalid number of arguments. Please provide the following: \n 1) Inventory change quantity\n 2) Space seperated list of object IDs (optional)' );
		}

		if ( ! is_numeric( $args[0] ) ) {
			WP_CLI::error( 'Invalid first argument. Quantity must be an integer.' );
		}

		if ( 1 < sizeof( $args ) ) {
			foreach( $args as $arg ) {
				if ( $arg !== $args[0] && is_numeric( $arg ) ) {
					WP_CLI::error( 'Invalid argument supplied: ' . $arg );
				}
			}
		}

		if ( 0 !== sizeof( $assoc_args ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		$quantity = array_shift( $args );

		if ( 0 === sizeof( $args ) ) {
			$result = $this->api->batch_change( $quantity );
		} else {
			$result = $this->api->batch_change( $quantity, $args );
		}

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( 'Batch inventory change complete!' );
	}

	public function set_interval( $args, $assoc_args ) {

		if ( 1 < sizeof( $args ) ) {
			WP_CLI::error( 'Set interval takes one argument: Interval in minutes' );
		}

		if ( 0 < sizeof( $args ) && ( ! is_numeric( $args[0] ) || 0 === (int) $args[0] ) ) {
			WP_CLI::error( 'Invalid first argument. Interval must be an integer.' );
		}

		if ( 0 === sizeof( $args ) && ! isset( $assoc_args['reset'] ) ) {
			WP_CLI::error( 'No arguments and no flags provided.' );
		}

		if ( 0 < sizeof( $assoc_args ) && ! isset( $assoc_args['reset'] ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		if ( 1 === sizeof( $args ) ) {
			update_option( 'wc_square_sandbox_helper_sync_interval', $args[0] );
			WP_CLI::success( 'Sync Interval updated to ' . $args[0] );
			exit();
		}

		if ( 0 < sizeof( $assoc_args ) && isset( $assoc_args['reset'] ) ) {
			delete_option( 'wc_square_sandbox_helper_sync_interval' );
			WP_CLI::success( 'Sync Interval has been reset' );
			exit();
		}
	}
}
