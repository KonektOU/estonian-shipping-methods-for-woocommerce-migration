<?php
/*
	Plugin Name: Estonian Shipping Methods for WooCommerce: Migration
	Description: Shows the data from your previous shipping methods to use the newer and better Estonian Shipping Methods for WooCommerce plugin.
	Plugin URI: https://www.konekt.ee
	Version: 1.0
	Author: Konekt OÜ
	Author URI: https://www.konekt.ee
	License: GPL2 or later
	Text Domain: wc-estonian-shipping-methods-migration
*/

// Security check
if ( ! defined( 'ABSPATH' ) ) exit;

class Estonian_Shipping_Methods_For_WooCommerce_Migration {

	private static $instance = null;

	public $replace = null;

	public function __construct() {
		// Set replaceable methods
		$this->register_methods();

		// Show selected terminal in order and emails
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'show_selected_terminal' ), 9, 1 );
		add_action( 'woocommerce_email_customer_details',               array( $this, 'show_selected_terminal' ), 14, 1 );

		// Show selected terminal in admin order review
		if( is_admin() ) {
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'show_selected_terminal' ), 19 );
		}
	}

	public function register_methods() {
		$replace_methods = [
			[
				'method'   => 'ms_omniva_estonia',
				'class'    => 'WC_Estonian_Shipping_Method_Omniva_Parcel_Machines_EE',
				'field'    => '_pickup_location_key'
			],
			[
				'method'   => 'itella_smartpost_estonia',
				'class'    => 'WC_Estonian_Shipping_Method_Smartpost_Estonia',
				'field'    => 'wc_shipping_smartpost_terminal'
			],
			[
				'method'   => 'itella_smartpost_estonia',
				'class'    => 'WC_Estonian_Shipping_Method_Smartpost_Estonia',
				'field'    => '_pickup_location',
				'is_value' => true
			]
		];

		$this->replace = apply_filters( 'wc_esm_migration_methods', $replace_methods );
	}

	public function show_selected_terminal( $order ) {
		global $wc_estonian_shipping_methods;

		// Create order instance if needed
		if( is_int( $order ) ) {
			$order = wc_get_order( $order );
		}

		foreach( $this->replace as $replace ) {
			if( $order->has_shipping_method( replace['method'] ) ) {
				if( isset( $wc_estonian_shipping_methods->methods[ $replace['class'] ] ) && $wc_estonian_shipping_methods->methods[ $replace['class'] ] !== false ) {
					$terminal_id = get_post_meta( wc_esm_get_order_id( $order ), $replace['field'], true );

					if( ! $terminal_id ) {
						continue;
					}

					if( isset( $replace['is_value'] ) && $replace['is_value'] === true ) {
						$terminal_name = $terminal_id;
					}
					else {
						$terminal_name = $wc_estonian_shipping_methods->methods[ $replace['class'] ]->get_terminal_name( $terminal_id );
					}


					// Output selected terminal to user customer details
					if( current_filter() == 'woocommerce_order_details_after_customer_details' ) {
						if( version_compare( WC_VERSION, '2.3.0', '<' ) ) {
							$terminal  = '<dt>' . $wc_estonian_shipping_methods->methods[ $replace['class'] ]->i18n_selected_terminal . ':</dt>';
							$terminal .= '<dd>' . $terminal_name . '</dd>';
						}
						else {
							$terminal  = '<tr>';
							$terminal .= '<th>' . $wc_estonian_shipping_methods->methods[ $replace['class'] ]->i18n_selected_terminal . ':</th>';
							$terminal .= '<td data-title="' . $wc_estonian_shipping_methods->methods[ $replace['class'] ]->i18n_selected_terminal . '">' . $terminal_name . '</td>';
							$terminal .= '</tr>';
						}
					}
					elseif( current_filter() == 'woocommerce_email_customer_details' ) {
						$terminal  = '<h2>' . $wc_estonian_shipping_methods->methods[ $replace['class'] ]->i18n_selected_terminal . '</h2>';
						$terminal .= '<p>'. $terminal_name .'</p>';
					}
					// Output selected terminal to everywhere else
					else {
						$terminal  = '<div class="selected_terminal">';
						$terminal .= '<div><strong>' . $wc_estonian_shipping_methods->methods[ $replace['class'] ]->i18n_selected_terminal . ':</strong></div>';
						$terminal .= $terminal_name;
						$terminal .= '</div>';
					}

					echo apply_filters( 'wc_shipping_'. $wc_estonian_shipping_methods->methods[ $replace['class'] ]->id .'_selected_terminal', $terminal, $terminal_id, $terminal_name, current_filter() );
				}
			}
		}
	}

	/**
	 * Fetch instance of this plugin
	 *
	 * @return Estonian_Shipping_Methods_For_WooCommerce
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new self;

		return self::$instance;
	}
}

add_action( 'plugins_loaded', array( 'Estonian_Shipping_Methods_For_WooCommerce_Migration', 'get_instance' ) );
