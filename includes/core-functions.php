<?php

defined( 'ABSPATH' ) || exit;

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

	return ! empty( $settings ) ? $settings : $default;
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

	$wc_minmax_quantities_min_product_quantity_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_product_quantity_error_message', __( "You have to buy at least {min_qty} quantities of {product_name}.", 'wc-minmax-quantities' ), 'wc_minmax_quantity_translate_settings' );

	$wc_minmax_quantities_max_order_quantity_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_order_quantity_error_message', __( "You can't buy more than {max_qty} quantities of {product_name}.", 'wc-minmax-quantities' ), 'wc_minmax_quantity_translate_settings' );

	$wc_minmax_quantities_min_order_price_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_order_price_error_message', __( "Minimum total price should be {min_price} or more for {product_name}.", 'wc-minmax-quantities' ), 'wc_minmax_quantity_translate_settings' );

	$wc_minmax_quantities_max_order_price_error_message = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_order_price_error_message', __( "Maximum total price can not be more than {max_price} for {product_name}.", 'wc-minmax-quantities' ), 'wc_minmax_quantity_translate_settings' );

	switch ( $type ) {
		case 'min_qty':
			$patterns                                                = array( '/{min_qty}/', '/{product_name}/' );
			$replacements                                            = array( $min_qty, $name );
			$wc_minmax_quantities_min_product_quantity_error_message = preg_replace( $patterns, $replacements, $wc_minmax_quantities_min_product_quantity_error_message );

			return sprintf( __( $wc_minmax_quantities_min_product_quantity_error_message, 'wc-minmax-quantities' ) );
		case 'max_qty':
			$patterns     = array( '/{max_qty}/', '/{product_name}/' );
			$replacements = array( $max_qty, $name );

			$wc_minmax_quantities_max_order_quantity_error_message = preg_replace( $patterns, $replacements, $wc_minmax_quantities_max_order_quantity_error_message );

			return sprintf( __( $wc_minmax_quantities_max_order_quantity_error_message, 'wc-minmax-quantities' ), $max_qty, $name );
		case 'min_price':
			$patterns     = array( '/{min_price}/', '/{product_name}/' );
			$replacements = array( wc_price( $min_price ), $name );

			$wc_minmax_quantities_min_order_price_error_message = preg_replace( $patterns, $replacements, $wc_minmax_quantities_min_order_price_error_message );

			return sprintf( __( $wc_minmax_quantities_min_order_price_error_message, 'wc-minmax-quantities' ), wc_price( $min_price ), $name );
		case 'max_price':
			$patterns     = array( '/{max_price}/', '/{product_name}/' );
			$replacements = array( wc_price( $max_price ), $name );

			$wc_minmax_quantities_max_order_price_error_message = preg_replace( $patterns, $replacements, $wc_minmax_quantities_max_order_price_error_message );

			return sprintf( __( $wc_minmax_quantities_max_order_price_error_message, 'wc-minmax-quantities' ), wc_price( $max_price ), $name );
		default:
			return false;
	}
}

/**
 * Check min max rules to proceed checkout
 *
 * @since 1.0.0
 */

function wc_min_max_quantities_proceed_to_checkout_conditions() {

	$checkout_url = wc_get_checkout_url();

	global $woocommerce;

	if ( apply_filters( 'wc_min_max_quantities_allow_global_rule', true ) ) {
		$total_quantity                            = $woocommerce->cart->cart_contents_count;
		$total_amount                              = floatval( WC()->cart->cart_contents_total );
		$wc_minmax_quantities_min_cart_total_price = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_cart_total_price', 0, 'wc_minmax_quantity_advanced_settings' );
		$wc_minmax_quantities_max_cart_total_price = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_cart_total_price', 0, 'wc_minmax_quantity_advanced_settings' );
	}
	$items            = WC()->cart->get_cart();
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
			$min_quantity     = (int) get_post_meta( $product_id, '_minmax_product_min_quantity', true );
			$max_quantity     = (int) get_post_meta( $product_id, '_minmax_product_max_quantity', true );
			$min_price        = (int) get_post_meta( $product_id, '_minmax_product_min_price', true );
			$max_price        = (int) get_post_meta( $product_id, '_minmax_product_max_price', true );
			$ignor_cart_total = true;
		} else {
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

	$total_amount = floatval( WC()->cart->cart_contents_total );

	$wc_minmax_quantities_bypass_discount_code = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_bypass_discount_code', 'no', 'wc_minmax_quantity_advanced_settings' );

	if ( $wc_minmax_quantities_bypass_discount_code == 'yes' ) {
		$total_amount = $total_amount + WC()->cart->get_discount_total();
	}

	$wc_minmax_quantities_min_cart_total_price = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_cart_total_price', 0, 'wc_minmax_quantity_advanced_settings' );
	$wc_minmax_quantities_max_cart_total_price = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_cart_total_price', 0, 'wc_minmax_quantity_advanced_settings' );
	$min_cart_total_error_message              = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_min_cart_total_error_message', __( "Minimum cart total price should be {min_cart_total_price} or more", 'wc-minmax-quantities' ), 'wc_minmax_quantity_translate_settings' );
	$max_cart_total_error_message              = wc_minmax_quantities_get_settings( 'wc_minmax_quantities_max_cart_total_error_message', __( "Maximum cart total price can not be more than {max_cart_total_price}", 'wc-minmax-quantities' ), 'wc_minmax_quantity_translate_settings' );


	if ( ! empty( $wc_minmax_quantities_min_cart_total_price ) && $total_amount < $wc_minmax_quantities_min_cart_total_price && ! $ignor_cart_total ) {
		$min_cart_total_error_message = preg_replace( '/{min_cart_total_price}/', wc_price( $wc_minmax_quantities_min_cart_total_price ), $min_cart_total_error_message );
		wc_add_notice( __( $min_cart_total_error_message, 'wc-minmax-quantities' ), 'error' );
		wc_min_max_quantities_hide_checkout_btn();
	}

	if ( ! empty( $wc_minmax_quantities_max_cart_total_price ) && $total_amount > $wc_minmax_quantities_max_cart_total_price && ! $ignor_cart_total ) {
		$max_cart_total_error_message = preg_replace( '/{max_cart_total_price}/', wc_price( $wc_minmax_quantities_max_cart_total_price ), $max_cart_total_error_message );
		wc_add_notice( __( $max_cart_total_error_message, 'wc-minmax-quantities' ), 'error' );
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

/**
 * List icon depending on pro plugin
 *
 * @param string pro or free
 *
 * @return null
 */
function ever_wc_minmax_feature_icon( $type ) {

	if ( $type == 'pro' ) {
		if ( class_exists( 'WC_MINMAX_PRO' ) ) {
			$image = '/images/ever-tick.svg';
		} else {
			$image = '/images/ever-cross.svg';
		}
	}
	if ( $type == 'free' ) {

		$image = '/images/ever-tick.svg';

	}
	echo "<img width='12' src='" . WC_MINMAX_ASSETS_URL . $image . "' alt='' />";
}


add_filter( 'wc_minmax_quantities_features_pro', 'ever_wc_minmax_upgrade_to_pro' );
/**
 * Callback for wc_minmax_quantities_features_pro filter
 *
 * @param string feature text
 *
 * @return string $text
 */
function ever_wc_minmax_upgrade_to_pro( $text ) {

	if ( ! class_exists( 'WC_MINMAX_PRO' ) ) {
		$text .= '&nbsp <a target="_blank" href="https://pluginever.com/plugins/woocommerce-min-max-quantities-pro/" title="' . esc_attr( __( 'Upgrade To Pro', 'wc-minmax-quantities' ) ) . '" style="color:red;font-weight:bold;">' . __( 'Upgrade To Pro', 'wc-minmax-quantities' ) . '</a>';
	}

	return $text;
}
