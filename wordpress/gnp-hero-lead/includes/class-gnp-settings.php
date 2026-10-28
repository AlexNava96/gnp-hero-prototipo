<?php
/**
 * Ajustes › GNP Hero: números de contacto, marca, política de datos y webhook del CRM.
 *
 * @package GNP_Hero_Lead
 */

defined( 'ABSPATH' ) || exit;

/**
 * Página de ajustes con la Settings API.
 */
final class GNP_Settings {

	const OPTION = 'gnp_hero_lead';

	/**
	 * Valores por defecto (datos de demostración, no reales).
	 *
	 * @var array
	 */
	const DEFAULTS = array(
		'brand'       => 'Llama Segura',
		'wa'          => '570000000000',
		'did'         => '576010000000',
		'did_label'   => '601 000 0000',
		'privacy_url' => '/politica-de-datos/',
		'webhook'     => '',
	);

	/**
	 * Registra hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Opciones actuales con valores por defecto.
	 *
	 * @return array
	 */
	public static function get() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::DEFAULTS );
	}

	/**
	 * Agrega la página bajo Ajustes.
	 */
	public static function menu() {
		add_options_page( 'GNP Hero', 'GNP Hero', 'manage_options', 'gnp-hero', array( __CLASS__, 'page' ) );
	}

	/**
	 * Registra la opción y sus campos.
	 */
	public static function register() {
		register_setting(
			'gnp_hero',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::DEFAULTS,
			)
		);

		add_settings_section( 'gnp_hero_main', 'Contacto y CRM', '__return_false', 'gnp-hero' );

		$fields = array(
			'brand'       => 'Marca visible',
			'wa'          => 'WhatsApp (con 57, solo dígitos)',
			'did'         => 'DID de la campaña (con 57, solo dígitos)',
			'did_label'   => 'DID tal como se muestra',
			'privacy_url' => 'URL de la política de datos',
			'webhook'     => 'Webhook del CRM o n8n (opcional)',
		);
		foreach ( $fields as $key => $label ) {
			add_settings_field(
				$key,
				$label,
				array( __CLASS__, 'field' ),
				'gnp-hero',
				'gnp_hero_main',
				array(
					'key'       => $key,
					'label_for' => 'gnp-' . $key,
				)
			);
		}
	}

	/**
	 * Campo de texto.
	 *
	 * @param array $args Argumentos del campo.
	 */
	public static function field( $args ) {
		$opts = self::get();
		printf(
			'<input type="text" class="regular-text" id="gnp-%1$s" name="%2$s[%1$s]" value="%3$s">',
			esc_attr( $args['key'] ),
			esc_attr( self::OPTION ),
			esc_attr( $opts[ $args['key'] ] )
		);
	}

	/**
	 * Limpia los valores guardados.
	 *
	 * @param array $in Valores enviados.
	 * @return array
	 */
	public static function sanitize( $in ) {
		$in = (array) $in;
		return array(
			'brand'       => sanitize_text_field( $in['brand'] ?? self::DEFAULTS['brand'] ),
			'wa'          => preg_replace( '/\D/', '', $in['wa'] ?? '' ),
			'did'         => preg_replace( '/\D/', '', $in['did'] ?? '' ),
			'did_label'   => sanitize_text_field( $in['did_label'] ?? '' ),
			'privacy_url' => esc_url_raw( $in['privacy_url'] ?? '' ),
			'webhook'     => esc_url_raw( $in['webhook'] ?? '' ),
		);
	}

	/**
	 * Pantalla de ajustes.
	 */
	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>GNP Hero</h1><form action="options.php" method="post">';
		settings_fields( 'gnp_hero' );
		do_settings_sections( 'gnp-hero' );
		submit_button();
		echo '</form><p>Inserta el hero en cualquier página con <code>[gnp_hero landing="gas-bogota"]</code>.</p></div>';
	}
}
