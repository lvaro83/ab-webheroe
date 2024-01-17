<?php
/**
 * Plugin Name: Simple A/B test plugin
 * Plugin Uri: https://webheroe.com/
 * Description: Basic plugin for A/B tests.
 * Author: Álvaro Torres
 * Author URI: https://webheroe.com/
 * Version: 1.0
 * License: GPLv2 or later
 * Text Domain: abwebheroe
 *
 * @package ABWebheroe
 */

/**
 * We prevent virtual thieves from entering this file externally.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Activation function.
 */
function abwebheroe_activation() {
	// Event counter original version.
	add_option( 'abwebheroe-original', 0, '', 'no' );

	// Event counter version B.
	add_option( 'abwebheroe-b', 0, '', 'no' );

	// Visitor counter.
	add_option( 'abwebheroe-count', 0, '', 'no' );
}
register_activation_hook( __FILE__, 'abwebheroe_activation' );

/**
 * Versions modifier.
 *
 * @param string $content Post content.
 *
 * @return string
 */
function abwebheroe_modificator( $content ) {

	$recuento = intval( get_option( 'abwebheroe-count' ) );

	if ( 0 === ( $recuento % 2 ) ) {
		// It is version B, so we modify the content.
		$content = str_replace( 'testab-original', 'testab-b', $content );
	}

	// We add 1 and update.
	update_option( 'abwebheroe-count', ++$recuento );

	return $content;
}
add_filter( 'the_content', 'abwebheroe_modificator' );

/**
 * Add events.
 */
function abwebheroe_addevents() {
	if ( ! is_front_page() ) {
		return;
	}

	$js_code = <<<'CL'
	jQuery( document ).ready( function( $ ) {
		$( '.button-testab' ).click( function() {
			let button = $(this);

			let version;
			if ( button.hasClass( 'testab-b' ) ) {
				version = 'b';
			} else if ( button.hasClass( 'testab-original' ) ) {
				version = 'original';
			}

			// Datos a enviar al servidor.
			let data = {
				'action': 'click-item',
				'version': version
			};

			$.ajax( {
				type: 'POST',
				url: '
CL;

	$js_code .= esc_url( admin_url() . 'admin-ajax.php' );

	$js_code .= <<<'CL'
				',
				data: data,
				/* success: function(response) {
					console.log(version);
					console.log(response);
				} */
			} );
		} )
	} )
CL;

	wp_register_script( 'abwebheroe-addevents-js-footer', '', array( 'jquery' ), null, true ); // phpcs:ignore
	wp_enqueue_script( 'abwebheroe-addevents-js-footer' );
	wp_add_inline_script( 'abwebheroe-addevents-js-footer', $js_code );
}
add_action( 'wp_enqueue_scripts', 'abwebheroe_addevents' );

/**
 * Adding new clicks to the database.
 */
function abwebheroe_add_clicks() {
	if ( ! empty( $_POST['version'] ) ) { // phpcs:ignore
		if ( 'b' === $_POST['version'] ) { // phpcs:ignore
			$option_name = 'abwebheroe-b';
		} elseif ( 'original' === $_POST['version'] ) { // phpcs:ignore
			$option_name = 'abwebheroe-original';
		} else {
			return;
		}

		// With the data obtained we recover the option.
		$option_value = get_option( $option_name );

		// We obtain the above value and add 1 to add the click..
		$click = intval( $option_value ) + 1;

		// We update the database with the added value of the new click..
		update_option( $option_name, $click );
	}
}
add_action( 'wp_ajax_nopriv_click-item', 'abwebheroe_add_clicks' );

/**
 * Admin section.
 */
function abwebheroe_menu_administracion() {

	add_menu_page(
		'A/B Simple',
		'A/B Simple',
		'activate_plugins',
		'abwebheroe',
		'abwebheroe_simple', // callback.
		'dashicons-chart-pie',
		'5'
	);
}
add_action( 'admin_menu', 'abwebheroe_menu_administracion' );

/**
 * Admin control callback.
 */
function abwebheroe_simple() {
	$original      = get_option( 'abwebheroe-original' );
	$version_b     = get_option( 'abwebheroe-b' );
	$visit_counter = get_option( 'abwebheroe-count' );

	?>
	<div id="ab-data">
		<h2><?php esc_html_e( 'Original version:', 'abwebheroe' ); ?></h2>
		<p><?php echo esc_html( $original ); ?></span>
		<h2><?php esc_html_e( 'Version B:', 'abwebheroe' ); ?></h2>
		<p><?php echo esc_html( $version_b ); ?></span>
		<h2><?php esc_html_e( 'Total visits:', 'abwebheroe' ); ?></h2>
		<p><?php echo esc_html( $visit_counter ); ?></p>
	</div>
	<?php
}

/**
 * Inline menu icon style.
 *
 * @return void
 */
function abwebheroe_icon_menu_style() {
	$custom_css = '#ab-data p, #ab-data h2 { margin: 0 0 5px; }';

	wp_add_inline_style( 'icon-menu-style', $custom_css );
}
add_action( 'admin_enqueue_scripts', 'abwebheroe_icon_menu_style' );
