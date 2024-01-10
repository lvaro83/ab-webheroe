<?php
/**
 * Plugin Name: Simple A/B test plugin
 * Plugin Uri: https://webheroe.com/
 * Description: Plugin básico para tests A/B
 * Author: Álvaro Torres
 * Author URI: https://webheroe.com/
 * Version: 1.0
 * License: GPLv2 or later
 * Text Domain: abwebheroe
 */

/**
 * Evitamos que los cacos virtuales entren en este archivo de forma externa.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Función de activación.
 */
function abwebheroe_activation() {
	// Contador eventos versión original.
	add_option( 'abwebheroe-original', 0, '', 'no' );

	// Contador eventos versión B.
	add_option( 'abwebheroe-b', 0, '', 'no' );

	// Contador visitantes.
	add_option( 'abwebheroe-count', 0, '', 'no' );
}
register_activation_hook( __FILE__, 'abwebheroe_activation' );

/**
 * Modificador de versiones.
 */
function abwebheroe_modificator( $content ) {

	$recuento = intval( get_option( 'abwebheroe-count' ) );

	// Usamos el operador módulo para obtener el resto de la division.
	if ( 0 === ( $recuento % 2 ) ) {
		// Es versión B, entonces modificamos el contenido.
		$content = str_replace( 'testab-original', 'testab-b', $content );
	}

	// Sumamos 1 y actualizamos.
	update_option( 'abwebheroe-count', $recuento++ );

	return $content;
}
add_filter( 'the_content', 'abwebheroe_modificator' );

/**
 * Añadir eventos.
 */
function abwebheroe_addevents() {

	if ( is_front_page() ) {
		?>

		<script>
		jQuery( document ).ready( function($) {
			$( '.button-testab' ).click( function(){
				let button = $(this);

				let version;
				if ( button.hasClass( 'testab-b' ) ) {
					version = 'b';
				} else if ( button.hasClass( 'testab-original' ) ) {
					version = 'original';
				}

				// Datos a enviar a la base de datos.
				let data = {
					'action': 'click-item',
					'version': version
				};

				// Enviando a la base de datos.
				$.ajax( {
					type: 'POST',
					url: '<?php echo admin_url() . 'admin-ajax.php'; ?>',
					data: data,
					/* success: function(response) {
						console.log(version);
						console.log(response);
					} */
				} );
			} )
		} )
		</script>

		<?php
	}
}
add_action( 'wp_footer', 'abwebheroe_addevents' );

/**
 * Añadir nuevos clics a la base de datos.
 */
function abwebheroe_add_clicks() {

	if ( ! empty( $_POST['version'] ) ){
		// Con los datos obtenidos creamos el nombre de la option.
		$version      = sanitize_text_field( $_POST['version'] );
		$option_name  = 'abwebheroe-' . $version;
		$option_value = get_option( $option_name );

		// Obtenemos el valor anterior y sumamos 1 para añadir el click.
		$click = intval( $option_value ) + 1;

		// Actualizamos la base de datos con el valor añadido del nuevo click.
		update_option( $option_name, $click );

		/* wp_send_json( get_option( $option_name ) );
		die(); */
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
	<style>
	#ab-data p, #ab-data h2 {
		margin: 0 0 5px;
	}
	</style>
	<div id="ab-data">
		<h2>Versión original: </h2>
		<p><?php echo esc_html( $original ); ?></span>
		<h2>Versión B: </h2>
		<p><?php echo esc_html( $version_b ); ?></span>
		<h2>Visitas totales: </h2>
		<p><?php echo esc_html( $visit_counter ); ?></p>
	</div>
	<?php
}
