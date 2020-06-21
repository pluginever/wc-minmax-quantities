<?php

//function prefix wc_min_max_quantities

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


/**
 * Get WC MIN MAX Quantities settings
 *
 * @param $key
 * @param bool $default
 * @param string $section
 *
 * @return bool|string|array
 */


function wc_minmax_quantities_get_settings( $key, $default = false, $section = 'wc_minmax_quantity_general_settings' ) {
	$settings = get_option( $key );
	return !empty($settings) ? $settings : $default;
}

/**
 * Get notice messages if min max condition check failed
 *
 * @param $args
 *
 * @return bool|string
 */

function wc_minmax_quantities_get_notice_message( $args ) {

	extract( wp_parse_args( $args, array(
		'type'      => 'min_qty',
		'min_qty'   => '',
		'max_qty'   => '',
		'min_price' => '',
		'max_price' => '',
		'name'      => '',
	) ) );

	$wc_minmax_quantities_min_product_quantity_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_product_quantity_error_message', "You have to buy at least %s quantities of %s", 'wc_minmax_quantity_translate_settings' );

	$wc_minmax_quantities_max_order_quantity_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_order_quantity_error_message', "You can't buy more than %s quantities of %s", 'wc_minmax_quantity_translate_settings' );

	$wc_minmax_quantities_min_order_price_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_order_price_error_message', "Minimum total price should be %s or more for %s", 'wc_minmax_quantity_translate_settings' );

	$wc_minmax_quantities_max_order_price_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_order_price_error_message', "Maximum total price can not be more than %s for %s", 'wc_minmax_quantity_translate_settings' );



	switch ( $type ) {
		case 'min_qty':
			return sprintf( __( $wc_minmax_quantities_min_product_quantity_error_message, 'wc-minmax-quantities' ), $min_qty, $name );
		case 'max_qty':
			return sprintf( __( $wc_minmax_quantities_max_order_quantity_error_message, 'wc-minmax-quantities' ), $max_qty, $name );
		case 'min_price':
			return sprintf( __( $wc_minmax_quantities_min_order_price_error_message, 'wc-minmax-quantities' ), wc_price( $min_price ), $name );
		case 'max_price':
			return sprintf( __( $wc_minmax_quantities_max_order_price_error_message, 'wc-minmax-quantities' ), wc_price( $max_price ), $name );
		default:
			return false;
	}
}

/**
 * Check min max rules to proceed checkout
 *
 * @since 3.1.3
 */

function wc_min_max_quantities_proceed_to_checkout_conditions() {

	$checkout_url = wc_get_checkout_url();

	global $woocommerce;

	if ( apply_filters( 'wc_min_max_quantities_allow_global_rule', true ) ) {
		$total_quantity       = $woocommerce->cart->cart_contents_count;
		$total_amount         = floatval( WC()->cart->cart_contents_total );
		$wc_minmax_quantities_min_cart_total_price = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_cart_total_price', 0, 'wc_minmax_quantity_advanced_settings' );
		$wc_minmax_quantities_max_cart_total_price = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_cart_total_price', 0, 'wc_minmax_quantity_advanced_settings' );
	}
//	error_log($total_quantity);
	$items = WC()->cart->get_cart();
	$ignor_cart_total = false;
	foreach ( $items as $item ) {

		$product_id    = $item['product_id'];
		$qty           = $item['quantity'];
		$product_name  = $item['data']->get_title();
		$subtotal      = $item['line_subtotal'];
		$ignore_global = get_post_meta( $product_id, '_minmax_ignore_global', true );
		$total_amount  = $subtotal;
		$min_quantity  = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_product_quantity', 0 );
		$max_quantity  = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_product_quantity', 0 );
		$min_price     = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_product_price', 0 );
		$max_price     = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_product_price', 0 );

		if ( $ignore_global == 'yes' ) {
			$min_quantity = (int) get_post_meta( $product_id, '_minmax_product_min_quantity', true );
			$max_quantity = (int) get_post_meta( $product_id, '_minmax_product_max_quantity', true );
			$min_price    = (int) get_post_meta( $product_id, '_minmax_product_min_price', true );
			$max_price    = (int) get_post_meta( $product_id, '_minmax_product_max_price', true );
			$ignor_cart_total = true;
		}else{
			$ignor_cart_total = false;
		}

		//=== Check minimum quantity ===
		if ( ! empty( $min_quantity ) && $qty < $min_quantity ) {
			wc_add_notice( wc_minmax_quantities_get_notice_message( array(
				'type'    => 'min_qty',
				'min_qty' => $min_quantity,
				'name'    => $product_name,
			) ), 'error' );
			wc_min_max_quantities_hide_checkout_btn();
		}

		//=== Check maximum quantity ===
		if ( ! empty( $max_quantity && $qty > $max_quantity ) ) {
			wc_add_notice( wc_minmax_quantities_get_notice_message( array(
				'type'    => 'max_qty',
				'max_qty' => $max_quantity,
				'name'    => $product_name,
			) ), 'error' );
			wc_min_max_quantities_hide_checkout_btn();
		}

		//=== Check minimum Price ===
		if ( ! empty( $min_price ) && $total_amount < $min_price ) {
			wc_add_notice( wc_minmax_quantities_get_notice_message( array(
				'type'      => 'min_price',
				'min_price' => $min_price,
				'name'      => $product_name,
			) ), 'error' );
			wc_min_max_quantities_hide_checkout_btn();
		}

		//=== Check maximum Price ===
		if ( ! empty( $max_price ) && $total_amount > $max_price ) {
			wc_add_notice( wc_minmax_quantities_get_notice_message( array(
				'type'      => 'max_price',
				'max_price' => $max_price,
				'name'      => $product_name,
			) ), 'error' );
			wc_min_max_quantities_hide_checkout_btn();
		}

	}

	//for cart total

	$total_amount         = floatval( WC()->cart->cart_contents_total );
	$wc_minmax_quantities_min_cart_total_price = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_cart_total_price', 0, 'wc_minmax_quantity_advanced_settings' );
	$wc_minmax_quantities_max_cart_total_price = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_cart_total_price', 0, 'wc_minmax_quantity_advanced_settings' );
	$min_cart_total_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_cart_total_error_message', "Minimum cart total price should be %s or more", 'wc_minmax_quantity_translate_settings' );
	$max_cart_total_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_cart_total_error_message', "Maximum cart total price can not be more than %s", 'wc_minmax_quantity_translate_settings' );


	if ( ! empty( $wc_minmax_quantities_min_cart_total_price ) && $total_amount < $wc_minmax_quantities_min_cart_total_price && !$ignor_cart_total) {
		wc_add_notice( sprintf( __( $min_cart_total_error_message, 'wc-minmax-quantities' ), wc_price( $wc_minmax_quantities_min_cart_total_price ) ), 'error' );
		wc_min_max_quantities_hide_checkout_btn();
	}

	if ( ! empty( $wc_minmax_quantities_max_cart_total_price ) && $total_amount > $wc_minmax_quantities_max_cart_total_price && !$ignor_cart_total) {
		wc_add_notice( sprintf( __( $max_cart_total_error_message, 'wc-minmax-quantities' ), wc_price( $wc_minmax_quantities_max_cart_total_price ) ), 'error' );
		wc_min_max_quantities_hide_checkout_btn();
	}
}

add_action( 'woocommerce_check_cart_items', 'wc_min_max_quantities_proceed_to_checkout_conditions' );

/**
 * Hide checkout button if the hide option is checked in the settings.
 */

function wc_min_max_quantities_hide_checkout_btn() {

	$hide = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_hide_checkout', 'yes' );

	if ( 'yes' != $hide ) {
		return;
	}

	remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
}



