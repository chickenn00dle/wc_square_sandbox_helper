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

		$cached = false;
		$save   = false;
			
		if ( isset( $assoc_args['cached'] ) ) {
			$cached = true;
			unset( $assoc_args['cached'] );
		}
		
		if ( isset( $assoc_args['save'] ) ) {
			$save = true;
			unset( $assoc_args['save'] );
		}

		if ( 0 !== sizeof( $assoc_args ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		if ( 1 < sizeof( $args ) ) {
			WP_CLI::error( 'Invalid number of arguments provided: List only accepts a single comma seperated list of types.' );
		}

		$types  = 1 === sizeof( $args ) ? str_replace( ' ', '', $args[0] ) : '';
		$result = $this->api->list( $types, $cached, $save );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( 'Catalog IDs fetched:' );

		foreach ( $result as $object_id_type => $object_ids_array ) {
			WP_CLI::line( $object_id_type );

			foreach( $object_ids_array as $object_id ) {
				WP_CLI::line( '    ' . $object_id );
			}
		}
	}

	public function batch_upsert( $args, $assoc_args ) {

		if ( 2 !== sizeof( $args ) ) {
			WP_CLI::error( 'Invalid number of arguments. Please provide the following: \n 1) Base name for generated products\n 2) Total number of items to generate' );
		}

		if ( ! is_string( $args[0] ) ) {
			WP_CLI::error( 'Invalid first argument. Base name must be of type string.' );
		}

		if ( ! is_numeric( $args[1] ) || 500 < $args[1] ) {
			WP_CLI::error( 'Invalid second argument. Total must be an integer of 500 or less.' );
		}

		$max_variations = 1;

		if ( isset( $assoc_args['max_variations'] ) ) {
			$max_variations = $assoc_args['max_variations'];
			unset( $assoc_args['max_variations'] );
		}

		if ( ! empty( $assoc_args ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		if ( ! is_numeric( $max_variations ) || 5 < $max_variations ) {
			WP_CLI::error( 'Invalid value for option max_variations. Must be an integer of 5 or less.' );
		}

		$result = $this->api->batch_upsert( $args[0], $args[1], $max_variations );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( 'Batch upsert complete!' );
	}

	public function batch_delete( $args, $assoc_args ) {

		if ( ! empty( $args ) ) {
			foreach( $args as $arg ) {
				if ( is_numeric( $arg ) ) {
					WP_CLI::error( 'Invalid argument supplied: ' . $arg );
				}
			}
		}

		if ( 0 !== sizeof( $assoc_args ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		if ( ! empty( $args ) ) {
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

		if ( empty( $args ) ) {
			WP_CLI::error( 'Invalid number of arguments provided. Please provide the following: \n 1) Inventory change quantity\n 2) Space seperated list of object IDs (optional)' );
		}

		if ( ! is_numeric( $args[0] ) ) {
			WP_CLI::error( 'Invalid first argument. Quantity must be an integer.' );
		}

		if ( 1 < sizeof( $args ) ) {
			foreach( range( 1, sizeof( $args ) ) as $arg ) {
				if ( is_numeric( $args[ $arg ] ) ) {
					WP_CLI::error( 'Invalid argument supplied: ' . $args[ $arg ] );
				}
			}
		}

		if ( ! empty( $assoc_args ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		$quantity = array_shift( $args );

		if ( empty( $args ) ) {
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

		if ( ! empty( $args ) && ( ! is_numeric( $args[0] ) || 0 === (int) $args[0] ) ) {
			WP_CLI::error( 'Invalid first argument. Interval must be an integer greater than 0.' );
		}

		$reset = false;

		if ( isset( $assoc_args['reset'] ) ) {
			$reset = true;
			unset( $assoc_args['reset'] );
		}

		if ( empty( $args ) && ! $reset ) {
			WP_CLI::error( 'No arguments and no flags provided.' );
		}

		if ( ! empty( $assoc_args ) ) {
			WP_CLI::error( 'Invalid option provided: ' . implode( ", ", array_keys( $assoc_args ) ) );
		}

		if ( $reset ) {
			delete_option( 'wc_square_sandbox_helper_sync_interval' );
			WP_CLI::success( 'Sync Interval has been reset' );
			exit();
		}

		if ( ! empty( $args ) ) {
			update_option( 'wc_square_sandbox_helper_sync_interval', $args[0] );
			WP_CLI::success( 'Sync Interval updated to ' . $args[0] );
			exit();
		}
	}
}
