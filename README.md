# Prototipo de hero · Prueba técnica GNP

Hero mobile-first para una landing de revisión de gasodomésticos, con formulario, WhatsApp y llamada. La marca, el precio y los números de contacto son ficticios.

**Ver el prototipo:** https://alexnava96.github.io/gnp-hero-prototipo/

Para ver cómo se guardan los datos de campaña, abre el enlace con UTM de prueba:
https://alexnava96.github.io/gnp-hero-prototipo/?utm_source=meta&utm_campaign=gas_bogota&fbclid=TEST

El panel al final de la página muestra los eventos que se envían a Google Tag Manager.

## Qué hay en el repositorio

- `index.html` y `assets/`: la versión HTML del hero, publicada con GitHub Pages.
- `wordpress/gnp-hero-lead/`: el mismo hero como plugin de WordPress (shortcode `[gnp_hero]`). Guarda cada lead con su campaña, evita leads duplicados por teléfono y puede enviarlos a un CRM.
- `capturas/`: el prototipo en celular y en computador.
- `build/build-static.php`: genera `index.html` a partir de la plantilla del plugin, para que las dos versiones sean iguales.

## Qué hace el código

- Guarda los datos de la campaña (UTM, `gclid`, `fbclid`) en una cookie y los envía con el lead.
- Crea un código único por visita (`lead_id`) que se usa en el formulario y en el mensaje de WhatsApp.
- Envía eventos a Google Tag Manager: visita, clic en un canal, inicio de formulario, error y lead enviado. El evento del lead se envía una sola vez.
- Valida nombre, teléfono colombiano de 10 dígitos y autorización de datos, con mensajes de error visibles.

## Probar el plugin en WordPress

1. Comprime la carpeta `wordpress/gnp-hero-lead` en un ZIP y súbelo en **Plugins › Añadir nuevo › Subir plugin**.
2. Actívalo y configura el WhatsApp y el número de ventas en **Ajustes › GNP Hero**.
3. Crea una página con el shortcode `[gnp_hero]`.
4. Envía el formulario: el lead aparece en el menú **Leads** con su campaña.

## Uso de IA

El código se generó con ayuda de Claude (Anthropic) a partir de mis decisiones de diseño, conversión y medición. Lo revisé y lo probé antes de publicarlo.
