<?php
/**
 * Genera la versión estática (index.html en la raíz) a partir de la misma plantilla del plugin,
 * para publicarla en un hosting estático (GitHub Pages / Netlify) sin WordPress.
 * Uso: php build/build-static.php
 */

define( 'ABSPATH', __DIR__ );

function esc_url( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }

$config = array(
	'endpoint'    => '', // sin backend: hero.js simula el envío
	'nonce'       => '',
	'wa'          => '570000000000',
	'did'         => '576010000000',
	'did_label'   => '601 000 0000',
	'brand'       => 'Llama Segura',
	'privacy_url' => '#politica',
	'landing'     => 'gas-bogota-demo',
	'product'     => 'revision_gas',
	'eyebrow'     => 'Revisión de gasodomésticos · Bogotá',
);

ob_start();
include dirname( __DIR__ ) . '/wordpress/gnp-hero-lead/templates/hero.php';
$hero = ob_get_clean();

$debug = <<<HTML
<section class="ls-page ls-debug" aria-label="Panel de medición">
	<div class="ls-wrap">
		<details open>
			<summary>Panel de medición (solo en la demo)</summary>
			<p>Eventos que el hero envía al <code>dataLayer</code>. Prueba la página con <code>?utm_source=meta&amp;utm_campaign=gas_bogota&amp;fbclid=TEST</code>, toca WhatsApp o envía el formulario.</p>
			<pre id="ls-debug-log"></pre>
			<p class="ls-demo-note">Prototipo demostrativo con marca, precio y números de contacto ficticios.</p>
		</details>
	</div>
</section>
HTML;

$head = <<<HTML
<title>Llama Segura</title>
<meta name="claude:viewport-fit-sides" content="cover">
<meta name="description" content="Revisión de calentador y estufa a gas en casa, en 48 horas, con certificado. Prototipo de hero para la prueba técnica GNP.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700&family=Figtree:wght@400;600;700&display=swap">
HTML;

$root   = dirname( __DIR__ );
$css    = file_get_contents( $root . '/assets/css/hero.css' );
$js     = file_get_contents( $root . '/assets/js/hero.js' );
$bodyBg = "\nhtml, body { background: var(--bg); }\n";

// 1) index.html — archivos separados (para GitHub Pages / Netlify).
$index = "<!doctype html>\n<html lang=\"es-CO\">\n<head>\n<meta charset=\"utf-8\">\n<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, viewport-fit=cover\">\n"
	. $head . "\n<link rel=\"stylesheet\" href=\"assets/css/hero.css\">\n<style>html,body{margin:0;background:var(--bg)}</style>\n</head>\n<body>\n"
	. $hero . "\n" . $debug . "\n<script src=\"assets/js/hero.js\" defer></script>\n</body>\n</html>\n";
file_put_contents( $root . '/index.html', $index );

// 2) build/artifact.html — un solo archivo con CSS y JS en línea (vista previa compartible).
$artifact = $head . "\n<style>\n" . $css . $bodyBg . "</style>\n" . $hero . "\n" . $debug . "\n<script>\n" . $js . "\n</script>\n";
// (vista previa de un solo archivo desactivada en el repositorio)

// 3) Copia CSS y JS al plugin (una sola fuente de verdad: assets/).
copy( $root . '/assets/css/hero.css', $root . '/wordpress/gnp-hero-lead/assets/css/hero.css' );
copy( $root . '/assets/js/hero.js', $root . '/wordpress/gnp-hero-lead/assets/js/hero.js' );

echo "OK\n";
