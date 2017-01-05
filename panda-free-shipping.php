<?php
/*
Plugin Name: Panda Free Shipping
Plugin URI: http://theauberginepanda.com/
Description: Panda Free Shipping - Enables free shipping for WooCommerce
Author: Danilo Setra
Version: 0.0.3
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function panda_free_shipping_method_init() {
		if ( ! class_exists( 'WC_Panda_Free_Shipping_Method' ) ) {
			class WC_Panda_Free_Shipping_Method extends WC_Shipping_Method {

				/**
				 * Constructor.
				 */
				public function __construct( $instance_id = 0 ) {
					$this->id                    = 'panda_free_shipping_method';
					$this->instance_id 			 = absint( $instance_id );
					$this->method_title          = 'Panda Free Shipping';
					$this->method_description    = __( 'Lets you setup free shipping.' );
					$this->supports              = array(
						'shipping-zones',
						'instance-settings',
						'instance-settings-modal',
					);
					$this->init();

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				/**
				 * init user set variables.
				 */
				public function init() {
					$this->init_form_fields();
				}


				/**
				 * set & get configuration fields
				 */
				public function init_form_fields() {
					$this->instance_form_fields = array(
						'title' => array(
							'title' 		=> __( 'Method Title', 'woocommerce' ),
							'type' 			=> 'text',
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default'		=> __( 'Free Shipping', 'woocommerce' )
						),
						'bundle_count_type' => array(
							'title' 		=> __( 'Bundled Products Count' ),
							'type' 			=> 'select',
							'class'         => 'wc-enhanced-select',
							'default' 		=> 'group',
							'options' 		=> array(
								'group' 	=> __( 'Per Group: Count product as only one pack' ),
								'item' 	    => __( 'Per Product: Count bundled products as unique itens' ),
							)
						),
						'amount' => array(
							'title' 		=> __( 'Amount','woocommerce' ),
							'type' 			=> 'number',
							'description' 	=> __( 'This controls the amount which allows free shipping for cart', 'woocommerce' ),
							'default'		=> __( '1' )
						)
					);

					$this->title                = $this->get_option( 'title' );
					$this->bundle_count_type    = $this->get_option( 'bundle_count_type' );
					$this->amount               = $this->get_option( 'amount' );
				}

				/**
				 * calculate_cart_quantity function.
				 *
				 * return cart amount for calculating
				 *
				 * @param boolean $unitybypack (default: group)
				 */
				public function calculate_cart_quantity( $unitybypack = 'group'  ){
					$quantity = WC()->cart->get_cart_contents_count();

					if ($unitybypack == 'item'){
						foreach(WC()->cart->cart_contents as $value0){
							$multiply = $value0['quantity'];
							foreach ($value0 as $key1 => $value1) {
								if($key1 == 'data'){
									foreach ($value1 as $key2 => $value2) {
										if($key2 == 'bundle_data'){
											$quantity -= $multiply;
											foreach ($value2 as $key3 => $value3) {
												$quantity += $value3['bundle_quantity']*$multiply;
											}
										}
									}
								}
							}
						}
					}
					return $quantity;
				}

				/**
				 * calculate_shipping function.
				 *
				 * @param array $package (default: array())
				 */
				public function calculate_shipping( $package = array() ) {
					$quantitycalc = $this->calculate_cart_quantity($this->bundle_count_type);

					if ($quantitycalc >= $this->amount && $this->amount > 0) {
						$rate = array(
							'id' => $this->id,
							'label' => $this->title,
							'cost' => 0,
							'calc_tax' => 'per_item'
						);

						// Register the rate
						$this->add_rate( $rate );
					}
				}

			}
		}
	}

	add_action( 'woocommerce_shipping_init', 'panda_free_shipping_method_init' );

	function add_panda_free_shipping_method( $methods ) {
		$methods['panda_free_shipping_method'] = 'WC_Panda_Free_Shipping_Method';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'add_panda_free_shipping_method' );
}