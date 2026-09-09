# Extensión "Autocompletar Gomedisys"

Reemplaza al servidor de streaming (`../streaming-server.js`) como forma de
llevar los datos de una solicitud desde la aplicación hasta Gomedisys. En vez
de un navegador remoto controlado a distancia, esta extensión usa la pestaña
de Gomedisys que el personal **ya tiene abierta y logueada** en su propio
Chrome — nunca toca el login ni el reCAPTCHA.

## Cómo funciona

```
Botón "Enviar a Gomedisys" (app)
   → postMessage
      → content-bridge.js (inyectado en la app)
         → background.js (service worker de la extensión)
            → busca la pestaña de Gomedisys ya abierta, la trae al frente
            → content-gomedisys.js (inyectado en esa pestaña)
               → diligencia el formulario, SIN darle clic a Guardar
```

Si no hay ninguna pestaña de Gomedisys abierta, la extensión responde con un
mensaje claro pidiendo abrirla primero — nunca abre una pestaña nueva por su
cuenta.

## Instalar en modo desarrollador (una sola vez, por persona)

1. Abre `chrome://extensions` (o el equivalente en Edge).
2. Activa "Modo de desarrollador" (interruptor arriba a la derecha).
3. Clic en "Cargar descomprimida" ("Load unpacked").
4. Selecciona esta carpeta (`rpa-gomedisys/extension`).

Cada vez que se edite el código de la extensión, hay que darle "Recargar" en
esa misma pantalla para que tome los cambios.

## Antes de usar en producción

`manifest.json` → `content_scripts[0].matches` solo incluye
`localhost:8020`, `localhost:5173` y `referencia.test` (los dominios usados
en desarrollo). **Falta agregar ahí el dominio real donde viva la
aplicación en producción** — si no, el botón de la app no podrá hablar con
la extensión fuera de estos entornos de prueba.
