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
 * 
 * Cuando se active este plugin, crea estas 3 cajas.
 */
function abwebheroe_activation() {
	// Las options son unas cajas de almacenamiento permanentemente accesibles.
	// add, get, update.
	add_option( 'abwebheroe-original', 0, '', 'no' );

	add_option( 'abwebheroe-b', 0, '', 'no' );

	add_option( 'abwebheroe-visitas', 0, '', 'no' );
}
register_activation_hook( __FILE__, 'abwebheroe_activation' );



/**
 * Modificador de versiones.
 * 
 * Cuando se carga la página de inicio
 * Obtén las visitas
 * y si es par, sustituimos la clase del botón
 * sumamos 1 a las visitas
 */
function abwebheroe_modificator( $content ) {

	if ( ! is_front_page() ) {
		// Si no es la página de inicio, salte de la función.
		return;
	}

	// Obtenemos cantidad de visitas.
	$recuento = get_option( 'abwebheroe-visitas' );

	// El operador módulo obtiene el resto de la division.
	if ( 0 === ( $recuento % 2 ) ) {
		// La clase CSS testab-original pasa a ser testab-b.
		$content = str_replace( 'testab-original', 'testab-b', $content );
	}

	// Sumamos 1 y actualizamos.
	update_option( 'abwebheroe-visitas', ++$recuento );

	return $content;
}
add_filter( 'the_content', 'abwebheroe_modificator' );



/**
 * Reaccionar a los clics.
 * 
 * Si hacen clic,
 * reconocemos la versión
 * y enviamos la información mediante POST.
 */
function abwebheroe_addevents() {

	if ( ! is_front_page() ) {
		// Si no es la página de inicio, sal de la función.
		return;
	}

	?>

	<script>
	jQuery( document ).ready( function($) {

		$( '.button-testab' ).click( function() {
			let button = $(this);

			//Reconocemos la versión.
			let version;
			if ( button.hasClass( 'testab-b' ) ) {
				version = 'abwebheroe-b';
			} else if ( button.hasClass( 'testab-original' ) ) {
				version = 'abwebheroe-original';
			}

			// Datos a enviar a la base de datos.
			let data = {
				'action': 'click-item',
				'version': version
			};

			// Enviando mediante post.
			$.ajax( {
				type: 'POST',
				url: '<?php echo admin_url() . 'admin-ajax.php'; ?>',
				data: data,
			} )

		} )
	} )
	</script>

	<?php
}
add_action( 'wp_footer', 'abwebheroe_addevents' );



/**
 * Gestionamos datos recibidos mediante POST.
 */
function abwebheroe_add_clicks() {

	if ( ! empty( $_POST['version'] ) ){
		// Con los datos obtenidos conocemos el nombre de la caja.
		$option_name  = $_POST['version'];
		$option_value = get_option( $option_name );

		// Actualizamos la caja con el nuevo click.
		update_option( $option_name, ++$option_value );
	}
}
add_action( 'wp_ajax_nopriv_click-item', 'abwebheroe_add_clicks' );



/**************************Zona administración****************************/

/**
 * Creación de menú de administración.
 */
function abwebheroe_menu_administracion() {

	add_menu_page(
		'A/B Simple',
		'A/B Simple',
		'activate_plugins',
		'abwebheroe',
		'abwebheroe_simple', // función para ver los datos.
		'dashicons-chart-pie',
		'5'
	);
}
add_action( 'admin_menu', 'abwebheroe_menu_administracion' );



/**
 * función para ver los datos.
 */
function abwebheroe_simple() {

	// Obtenemos las 3 cajas.
	$original      = get_option( 'abwebheroe-original' );
	$version_b     = get_option( 'abwebheroe-b' );
	$visit_counter = get_option( 'abwebheroe-visitas' );

	// Mostramos los valores.
	?>
	<style>
	#ab-data p, #ab-data h2 {
		margin: 0 0 5px;
	}
	</style>
	<div id="ab-data">
		<h2>Versión original: </h2>
		<p><?php echo esc_html( $original ); ?></p>
		<h2>Versión B: </h2>
		<p><?php echo esc_html( $version_b ); ?></p>
		<h2>Visitas totales: </h2>
		<p><?php echo esc_html( $visit_counter ); ?></p>
	</div>
	<?php
}
