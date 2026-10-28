<?php
/**
 * Leads: tipo de contenido privado, endpoint REST, deduplicación y envío al CRM.
 *
 * @package GNP_Hero_Lead
 */

defined( 'ABSPATH' ) || exit;

/**
 * Guarda cada lead como un post privado con su atribución en metadatos.
 */
final class GNP_Leads {

	const POST_TYPE    = 'gnp_lead';
	const NONCE_ACTION = 'gnp_hero_lead';
	const DEDUP_DAYS   = 30;
	const RATE_LIMIT   = 5;   // envíos por IP...
	const RATE_WINDOW  = 600; // ...cada 10 minutos.

	/**
	 * Campos de atribución que se guardan con cada lead.
	 *
	 * @var string[]
	 */
	const ATTR_FIELDS = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'gbraid', 'wbraid', 'fbclid' );

	/**
	 * Registra hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_value' ), 10, 2 );
	}

	/**
	 * Tipo de contenido privado: visible solo en el admin.
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => 'Leads',
					'singular_name' => 'Lead',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-phone',
				'supports'        => array( 'title', 'custom-fields' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * POST /wp-json/gnp/v1/lead
	 */
	public static function register_route() {
		register_rest_route(
			'gnp/v1',
			'/lead',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => '__return_true', // Formulario público; se valida nonce, honeypot y límite por IP en handle().
			)
		);
	}

	/**
	 * Procesa el envío del formulario.
	 *
	 * @param WP_REST_Request $request Petición.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'x_ls_nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return new WP_Error( 'gnp_nonce', 'La sesión expiró. Recarga la página e intenta de nuevo.', array( 'status' => 403 ) );
		}

		$p = $request->get_json_params();

		// Honeypot: un bot llena el campo oculto; se responde éxito sin guardar nada.
		if ( ! empty( $p['company'] ) ) {
			return rest_ensure_response( array( 'status' => 'created' ) );
		}

		if ( self::rate_limited() ) {
			return new WP_Error( 'gnp_rate', 'Recibimos varias solicitudes seguidas. Intenta en unos minutos.', array( 'status' => 429 ) );
		}

		$name    = sanitize_text_field( $p['name'] ?? '' );
		$phone   = self::normalize_phone( $p['phone'] ?? '' );
		$consent = ! empty( $p['consent'] );
		$lead_id = sanitize_text_field( $p['lead_id'] ?? '' );

		if ( mb_strlen( $name ) < 2 || ! preg_match( '/^(3\d{9}|60\d{8})$/', $phone ) || ! $consent || ! wp_is_uuid( $lead_id ) ) {
			return new WP_Error( 'gnp_invalid', 'Revisa nombre, teléfono y autorización de datos.', array( 'status' => 422 ) );
		}

		// Deduplicación: mismo teléfono en los últimos 30 días = mismo lead.
		$existing = self::find_recent_by_phone( $phone );
		if ( $existing ) {
			add_post_meta( $existing, '_touch', array( 'ts' => current_time( 'mysql', true ), 'lead_id' => $lead_id ) );
			return rest_ensure_response(
				array(
					'status'  => 'duplicate',
					'lead_id' => get_post_meta( $existing, 'lead_id', true ),
				)
			);
		}

		$meta = array(
			'lead_id'    => $lead_id,
			'name'       => $name,
			'phone'      => $phone,
			'channel'    => 'form',
			'landing_id' => sanitize_key( $p['landing_id'] ?? '' ),
			'page_url'   => esc_url_raw( $p['page_url'] ?? '' ),
			'consent_ts' => current_time( 'mysql', true ),
			'status'     => 'nuevo',
		);
		foreach ( self::ATTR_FIELDS as $f ) {
			$meta[ $f ] = sanitize_text_field( $p[ $f ] ?? '' );
		}
		if ( ! empty( $p['first_touch'] ) && is_array( $p['first_touch'] ) ) {
			$meta['first_touch'] = wp_json_encode( array_map( 'sanitize_text_field', $p['first_touch'] ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( '%s · %s', $name, $meta['utm_campaign'] ? $meta['utm_campaign'] : 'sin campaña' ),
				'meta_input'  => $meta,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'gnp_save', 'No pudimos guardar tu solicitud.', array( 'status' => 500 ) );
		}

		self::send_to_crm( $post_id, $meta );

		/**
		 * Permite conectar otras integraciones (CRM, n8n, correo) sin tocar el plugin.
		 *
		 * @param int   $post_id ID del lead.
		 * @param array $meta    Datos guardados.
		 */
		do_action( 'gnp_lead_created', $post_id, $meta );

		return rest_ensure_response(
			array(
				'status'  => 'created',
				'lead_id' => $lead_id,
			)
		);
	}

	/**
	 * Deja el teléfono en 10 dígitos (quita +57 y separadores).
	 *
	 * @param string $raw Teléfono escrito por el usuario.
	 * @return string
	 */
	public static function normalize_phone( $raw ) {
		$d = preg_replace( '/\D/', '', (string) $raw );
		if ( 12 === strlen( $d ) && 0 === strpos( $d, '57' ) ) {
			$d = substr( $d, 2 );
		}
		return $d;
	}

	/**
	 * Busca un lead con el mismo teléfono en la ventana de deduplicación.
	 *
	 * @param string $phone Teléfono normalizado.
	 * @return int 0 si no existe.
	 */
	private static function find_recent_by_phone( $phone ) {
		$q = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'private',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => 'phone', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $phone,  // phpcs:ignore WordPress.DB.SlowDBQuery
				'date_query'     => array( array( 'after' => self::DEDUP_DAYS . ' days ago' ) ),
			)
		);
		return $q->posts ? (int) $q->posts[0] : 0;
	}

	/**
	 * Límite simple por IP con transients.
	 *
	 * @return bool
	 */
	private static function rate_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'gnp_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= self::RATE_LIMIT ) {
			return true;
		}
		set_transient( $key, $n + 1, self::RATE_WINDOW );
		return false;
	}

	/**
	 * Envía el lead al webhook configurado (CRM o n8n) sin bloquear la respuesta al usuario.
	 *
	 * @param int   $post_id ID del lead.
	 * @param array $meta    Datos del lead.
	 */
	private static function send_to_crm( $post_id, $meta ) {
		$url = GNP_Settings::get()['webhook'];
		if ( ! $url ) {
			return;
		}
		wp_remote_post(
			$url,
			array(
				'blocking' => false,
				'timeout'  => 3,
				'headers'  => array( 'Content-Type' => 'application/json' ),
				'body'     => wp_json_encode( array_merge( $meta, array( 'wp_id' => $post_id ) ) ),
			)
		);
	}

	/**
	 * Columnas del listado de leads en el admin.
	 *
	 * @param array $cols Columnas.
	 * @return array
	 */
	public static function columns( $cols ) {
		return array(
			'cb'           => $cols['cb'],
			'title'        => 'Lead',
			'phone'        => 'Teléfono',
			'utm_source'   => 'Fuente',
			'utm_campaign' => 'Campaña',
			'gclid'        => 'gclid',
			'date'         => $cols['date'],
		);
	}

	/**
	 * Valor de cada columna.
	 *
	 * @param string $col     Columna.
	 * @param int    $post_id Lead.
	 */
	public static function column_value( $col, $post_id ) {
		if ( in_array( $col, array( 'phone', 'utm_source', 'utm_campaign', 'gclid' ), true ) ) {
			$v = get_post_meta( $post_id, $col, true );
			echo esc_html( $v ? $v : '—' );
		}
	}
}
