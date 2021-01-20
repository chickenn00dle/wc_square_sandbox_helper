<?php
/**
 * Plugin Name:     Woocommerce Square Sandbox Helper
 * Plugin URI:      https://github.com/chickenn00dle/wc_square_sandbox_helper
 * Description:     Helper commands for setting up large Square test stores
 * Author:          Rasmy Nguyen
 * Author URI:      https://rzmy.win
 * Text Domain:     wc-square-sandbox-helper
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Woocommerce_Square_Sandbox_Helper
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;
}

function wc_square_sandbox_helper_missing_square_notice() {

	echo  sprintf(
			__( '%1$sWooCommerce Square Sandbox Helper requires Square for WooCommerce to be installed and active. You can download %2$sSquare for WooCommerce%3$s here.%4$s', 'wc-square-sandbox-helper' ),
			'<div class="error"><p><strong>',
			'<a href="https://woocommerce.com/products/square/" target="_blank">',
			'</a>',
			'</strong></p></div>'
	);
}

function wc_square_sandbox_helper_missing_square_token_notice() {

	echo  sprintf(
			__( '%1$sWooCommerce Square Sandbox Helper requires a Square sandbox access token and Location ID to be set. You can set your %2$saccess token and location ID here%3$s.%4$s', 'wc-square-sandbox-helper' ),
			'<div class="error"><p><strong>',
			'<a href="' . get_site_url() . '/wp-admin/admin.php?page=wc-settings&tab=square" target="_blank">',
			'</a>',
			'</strong></p></div>'
	);
}

function wc_square_sandbox_helper() {

	static $plugin;

	if ( isset( $plugin ) ) {

		return $plugin;
	}

	class WC_Square_Sandbox_Helper {

		private $api;

		private $cli;

		function __construct() {

			$this->init();
			$this->maybe_set_sync_interval();

			add_action( 'cli_init', array( $this, 'register_cli_commands' ) );
		}

		private function init() {

			require_once dirname( __FILE__ ) . '/includes/class-wc-square-sandbox-api.php';
			require_once dirname( __FILE__ ) . '/includes/class-wc-square-sandbox-cli.php';
			require_once dirname( __FILE__ ) . '/includes/class-wc-square-sandbox-catalog-object.php';

			$this->api = new WC_Square_Sandbox_API();
			$this->cli = new WC_Square_Sandbox_CLI( $this->api );
		}

		private function maybe_set_sync_interval() {

			if ( get_option( 'wc_square_sandbox_helper_sync_interval', false ) ) {
				add_filter(
					'wc_square_sync_interval',
					function( $interval ) {
						return ( (int) get_option( 'wc_square_sandbox_helper_sync_interval' ) ) * MINUTE_IN_SECONDS;
					}
				);
			}
		}

		public function register_cli_commands() {

			WP_CLI::add_command( 'square', $this->cli );
		}
	}

	$plugin = new WC_Square_Sandbox_Helper();

	return $plugin;
}

add_action( 'plugins_loaded', 'wc_square_sandbox_helper_init' );

function wc_square_sandbox_helper_init() {

	if ( ! class_exists( 'WooCommerce_Square_Loader' ) ) {
		add_action( 'admin_notices', 'wc_square_sandbox_helper_missing_square_notice' );
		return;
	}

	$square_settings = get_option( 'wc_square_settings', array() );

	if ( ! isset( $square_settings['sandbox_token'], $square_settings['sandbox_location_id'] ) || ! $square_settings['sandbox_token'] || ! $square_settings['sandbox_location_id'] ) {
		add_action( 'admin_notices', 'wc_square_sandbox_helper_missing_square_token_notice' );
		return;
	}

	wc_square_sandbox_helper();
}
