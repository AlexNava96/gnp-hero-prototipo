<?php
/**
 * Plugin Name:       GNP Hero Lead (prueba técnica)
 * Description:       Hero mobile-first de captación con formulario, WhatsApp y llamada; guarda leads con su atribución de campaña y empuja eventos al dataLayer. Uso: [gnp_hero landing="gas-bogota"].
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Alex (prueba técnica GNP)
 * License:           GPL-2.0-or-later
 * Text Domain:       gnp-hero-lead
 *
 * @package GNP_Hero_Lead
 */

defined( 'ABSPATH' ) || exit;

define( 'GNP_HERO_LEAD_VERSION', '1.0.0' );
define( 'GNP_HERO_LEAD_FILE', __FILE__ );
define( 'GNP_HERO_LEAD_DIR', plugin_dir_path( __FILE__ ) );
define( 'GNP_HERO_LEAD_URL', plugin_dir_url( __FILE__ ) );

require_once GNP_HERO_LEAD_DIR . 'includes/class-gnp-leads.php';
require_once GNP_HERO_LEAD_DIR . 'includes/class-gnp-settings.php';

/**
 * Shortcode, assets y arranque del plugin.
 */
final class GNP_Hero_Lead {

	/**
	 * Registra hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_assets' ) );
		add_shortcode( 'gnp_hero', array( __CLASS__, 'render_shortcode' ) );
		GNP_Leads::init();
		GNP_Settings::init();
	}

	/**
	 * Registra (sin encolar) CSS y JS. Solo se encolan en páginas que usan el shortcode.
	 */
	public static function register_assets() {
		wp_register_style(
			'gnp-hero-fonts',
			'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700&family=Figtree:wght@400;600;700&display=swap',
			array(),
			null
		);
		wp_register_style(
			'gnp-hero',
			GNP_HERO_LEAD_URL . 'assets/css/hero.css',
			array( 'gnp-hero-fonts' ),
			GNP_HERO_LEAD_VERSION
		);
		wp_register_script(
			'gnp-hero',
			GNP_HERO_LEAD_URL . 'assets/js/hero.js',
			array(),
			GNP_HERO_LEAD_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Renderiza el hero.
	 *
	 * @param array $atts Atributos del shortcode.
	 * @return string
	 */
	public static function render_shortcode( $atts ) {
		$opts = GNP_Settings::get();

		$atts = shortcode_atts(
			array(
				'landing' => 'gas-bogota',
				'product' => 'revision_gas',
				'eyebrow' => 'Revisión de gasodomésticos · Bogotá',
			),
			$atts,
			'gnp_hero'
		);

		wp_enqueue_style( 'gnp-hero' );
		wp_enqueue_script( 'gnp-hero' );

		$config = array(
			'endpoint'    => rest_url( 'gnp/v1/lead' ),
			'nonce'       => wp_create_nonce( GNP_Leads::NONCE_ACTION ),
			'wa'          => preg_replace( '/\D/', '', $opts['wa'] ),
			'did'         => preg_replace( '/\D/', '', $opts['did'] ),
			'did_label'   => $opts['did_label'],
			'brand'       => $opts['brand'],
			'privacy_url' => $opts['privacy_url'],
			'landing'     => sanitize_key( $atts['landing'] ),
			'product'     => sanitize_key( $atts['product'] ),
			'eyebrow'     => $atts['eyebrow'],
		);

		ob_start();
		include GNP_HERO_LEAD_DIR . 'templates/hero.php';
		return ob_get_clean();
	}
}

GNP_Hero_Lead::init();
