<?php

if ( ! defined( 'ABSPATH' ) ) {

	exit;
}

class WC_Square_Sandbox_Catalog_Object {

	private $object_name;

	private $variations;

	private $object_data;

	function __construct( $name, $variations ) {

		$this->object_name = $name;
		$this->variations  = $variations;
		$this->object_data = $this->generate_object();
	}

	public function get_object() {

		return $this->object_data;
	}

	private function generate_object() {

		$sku     = strtolower( str_replace( ' ', '', $this->object_name ) );
		$item_id = '#' . $sku;
		$object  = array(
			"type"                     => "ITEM",
			"id"                       => $item_id,
			"is_deleted"               => false,
			"present_at_all_locations" => true,
			"item_data"                => array(
				"name"         => $this->object_name,
				"description"  => $this->object_name . " is a test product.",
				"product_type" => "REGULAR",
				"variations"   => array(),
			),
		);

		foreach( range( 1, $this->variations ) as $variation ) {
			$object["item_data"]["variations"][] = array(
				"type"                     => "ITEM_VARIATION",
				"id"                       => $item_id . "_variation_" . $variation,
				"is_deleted"               => false,
				"present_at_all_locations" => true,
				"item_variation_data"      => array(
					"item_id"         => $item_id,
					"name"            => $this->object_name . ( 1 < $this->variations ? ' - ' . $variation : '' ),
					"pricing_type"    => "FIXED_PRICING",
					"sku"             => $sku . "-" . $variation,
					"track_inventory" => true,
					"price_money"     => array(
						"amount"   => rand( 1, 10 ) * 1000,
						"currency" => "USD"
					),
				),
			);
		}

		return $object;
	}
}
