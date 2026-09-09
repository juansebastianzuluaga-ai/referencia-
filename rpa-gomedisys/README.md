# RPA Gomedisys

Automatiza el diligenciamiento del formulario "Registro de Referencia" en Gomedisys: 9 campos confirmados, a partir de una solicitud ya aceptada en la aplicación. **Nunca guarda por su cuenta ni toca el login** — siempre se detiene para que una persona revise y decida.

## Versión activa: extensión de navegador

El botón **"Enviar a Gomedisys"** (en la vista interna "Solicitudes de referencia",
visible en solicitudes `aceptado`/`en_espera`) usa la extensión en
[`extension/`](extension/) — ver [`extension/README.md`](extension/README.md) para
instalarla e instrucciones detalladas.

Por qué así y no con un navegador remoto en vivo (como se intentó antes, ver abajo): el
personal de la clínica ya deja Gomedisys abierto y logueado todo el turno en su propio
navegador. La extensión aprovecha exactamente esa pestaña — nunca pide usuario ni
contraseña, nunca abre una sesión aparte:

```
Botón "Enviar a Gomedisys" (app) → extensión → pestaña de Gomedisys ya abierta
```

Si no hay ninguna pestaña de Gomedisys abierta, la app avisa con un mensaje claro
("Instala la extensión..." o el error que devuelva la extensión) — no intenta abrir
ni loguear nada por su cuenta.

## Versiones anteriores (quedan en el repo como referencia, ya no son el camino activo)

- **`autocompletar.js`** — modo terminal con Playwright: abre un Chromium normal en tu
  pantalla, login 100% manual, y diligencia el resto. Fue la primera versión validada de
  punta a punta y sigue sirviendo para probar cambios de selectores contra Gomedisys sin
  tocar la extensión.
- **`streaming-server.js`** + **`public/viewer.html`** — un navegador remoto en un
  servidor, transmitido en vivo por WebSocket y controlado a distancia. Se descartó
  porque cada sesión requería iniciar sesión de nuevo (usuario, contraseña, reCAPTCHA) en
  ESE navegador aparte, en vez de reusar la sesión que el personal ya tiene abierta.

`diligenciar.js` + `kendoHelpers.js` (Playwright, usados por las dos versiones
anteriores) y `extension/content-gomedisys.js` (DOM puro, usado por la extensión)
implementan la MISMA secuencia — si Gomedisys cambia un selector, hay que actualizarlo
en ambos lados.

## Preparación del modo terminal (una sola vez)

```bash
cp .env.example .env
npm install
npx playwright install chromium
```

Edita `.env` y pon la URL de Gomedisys. `GOMEDISYS_USER` es opcional (solo se muestra como sugerencia en pantalla, el login siempre es 100% manual).

```bash
node autocompletar.js
```

Se abre una ventana de Chromium. Inicia sesión tú mismo (usuario, contraseña, reCAPTCHA) hasta llegar al dashboard, presiona Enter en la terminal, y el script diligencia el resto solo. Al final vuelve a pedir Enter — revisa la pantalla antes de presionarlo (eso cierra el navegador).

## Qué reportar si algo falla

El mensaje de estado (toast en la app, o consola en modo terminal) indica el último paso alcanzado — cuéntamelo junto con qué esperabas que pasara.

## Datos de prueba

`datosPrueba.js` — usado por `autocompletar.js` y `streaming-server.js` (las versiones anteriores). La extensión ya no lo usa: toma los datos reales de la solicitud seleccionada en la app.
