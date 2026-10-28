<?php
/**
 * Plantilla del hero. Variables disponibles: $config (array) desde GNP_Hero_Lead::render_shortcode().
 *
 * @package GNP_Hero_Lead
 */

defined( 'ABSPATH' ) || exit;

$fields = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'gbraid', 'wbraid', 'fbclid' );
?>
<div class="ls-page" id="ls-hero"
	data-endpoint="<?php echo esc_url( $config['endpoint'] ); ?>"
	data-nonce="<?php echo esc_attr( $config['nonce'] ); ?>"
	data-wa="<?php echo esc_attr( $config['wa'] ); ?>"
	data-did="<?php echo esc_attr( $config['did'] ); ?>"
	data-landing="<?php echo esc_attr( $config['landing'] ); ?>"
	data-product="<?php echo esc_attr( $config['product'] ); ?>">

	<header class="ls-top">
		<div class="ls-wrap">
			<span class="ls-brand">
				<svg viewBox="0 0 32 32" aria-hidden="true"><path fill="currentColor" d="M16 3c1.5 4.6 6.9 7.2 6.9 13.6A6.9 6.9 0 0 1 9.1 16.6c0-3.4 1.8-5.1 3.3-6.8.3 2.4 1.3 3.8 2.8 4.5C14.4 10.6 14.8 6.4 16 3Z"/><path fill="currentColor" opacity=".45" d="M7 27h18v2H7z"/></svg>
				<?php echo esc_html( $config['brand'] ); ?>
			</span>
			<span class="ls-top-call">Ventas: <a href="#" data-channel="did" data-position="header"><?php echo esc_html( $config['did_label'] ); ?></a></span>
		</div>
	</header>

	<main class="ls-hero">
		<div class="ls-wrap">
			<section class="ls-copy" aria-labelledby="ls-title">
				<p class="ls-eyebrow"><?php echo esc_html( $config['eyebrow'] ); ?></p>
				<h1 class="ls-title" id="ls-title">Tu calentador y tu estufa, <em>revisados en casa</em> en 48 horas</h1>
				<p class="ls-sub">Un técnico certificado mide fugas y monóxido de carbono, ajusta la llama y te deja el certificado de revisión. Lo pagas en cuotas en tu factura del gas.</p>
				<p class="ls-price"><strong>$39.900</strong><span>por visita · hasta 6 cuotas sin papeleo</span></p>
			</section>

			<section class="ls-proof" aria-label="Por qué elegirnos">
				<ul class="ls-benefits">
					<li><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg><span><strong>Técnicos certificados</strong> que llegan identificados con carné.</span></li>
					<li><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg><span><strong>Garantía de 90 días</strong> en el ajuste realizado.</span></li>
					<li><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg><span><strong>Agenda en 48 horas</strong> en Bogotá y municipios cercanos.</span></li>
				</ul>

				<div class="ls-tag" aria-label="Ejemplo del certificado de revisión">
					<p class="ls-tag-title">Así se ve tu certificado de revisión</p>
					<dl>
						<dt>Monóxido de carbono en el ambiente</dt><dd>0 ppm</dd>
						<dt>Prueba de hermeticidad</dt><dd>Sin fugas</dd>
						<dt>Color de la llama</dt><dd>Azul estable</dd>
						<dt>Próxima revisión</dt><dd>12 meses</dd>
					</dl>
				</div>
			</section>

			<section class="ls-card" aria-labelledby="ls-form-title">
				<form id="ls-form" novalidate>
					<div class="ls-form-grid">
						<h2 id="ls-form-title">Agenda tu revisión</h2>
						<p class="ls-card-sub">Déjanos tu número y un asesor te llama en minutos para elegir el día.</p>

						<div class="ls-field">
							<label for="ls-name">Nombre</label>
							<input id="ls-name" name="name" type="text" autocomplete="given-name" required maxlength="80">
							<p class="ls-error" role="alert"></p>
						</div>

						<div class="ls-field">
							<label for="ls-phone">Celular o fijo</label>
							<input id="ls-phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel-national" placeholder="300 123 4567" required maxlength="16">
							<p class="ls-error" role="alert"></p>
						</div>

						<label class="ls-consent" for="ls-consent">
							<input id="ls-consent" name="consent" type="checkbox" value="1" required>
							<span>Autorizo el tratamiento de mis datos para que me contacten sobre este servicio, según la <a href="<?php echo esc_url( $config['privacy_url'] ); ?>">política de datos</a> (Ley 1581 de 2012).</span>
						</label>
						<p class="ls-error" id="ls-consent-error" role="alert"></p>

						<div class="ls-hp" aria-hidden="true">
							<label for="ls-company">No llenar</label>
							<input id="ls-company" name="company" type="text" tabindex="-1" autocomplete="off">
						</div>
						<input type="hidden" name="lead_id">
						<input type="hidden" name="landing_id">
						<?php foreach ( $fields as $f ) : ?>
							<input type="hidden" name="<?php echo esc_attr( $f ); ?>">
						<?php endforeach; ?>

						<button class="ls-btn ls-btn--primary" type="submit" data-channel="form" data-position="hero-form">Quiero que me llamen</button>

						<div class="ls-alt">
							<p>¿Prefieres otro canal?</p>
							<div class="ls-alt-row">
								<a class="ls-btn ls-btn--wa" href="#" target="_blank" rel="noopener" data-channel="whatsapp" data-position="hero-form">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3Zm0 2a7 7 0 1 1-3.6 13l-.3-.2-2.3.6.6-2.2-.2-.3A7 7 0 0 1 12 5Z"/></svg>
									WhatsApp
								</a>
								<a class="ls-btn ls-btn--ghost" href="#" data-channel="did" data-position="hero-form">
									<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2Z"/></svg>
									Llamar
								</a>
							</div>
						</div>
					</div>
				</form>

				<div class="ls-success" id="ls-success" tabindex="-1" hidden>
					<h2>Listo, <span data-name></span>.</h2>
					<p>Un asesor te llamará en los próximos minutos (lunes a sábado, 7 a. m. a 7 p. m.) para agendar tu visita.</p>
					<p class="ls-ref">Código de tu solicitud: <span data-ref></span></p>
				</div>
			</section>
		</div>
	</main>

	<nav class="ls-sticky" aria-label="Contacto rápido">
		<a class="ls-btn ls-btn--wa" href="#" target="_blank" rel="noopener" data-channel="whatsapp" data-position="sticky">WhatsApp</a>
		<a class="ls-btn ls-btn--primary" href="#" data-channel="did" data-position="sticky">Llamar ahora</a>
	</nav>
</div>
