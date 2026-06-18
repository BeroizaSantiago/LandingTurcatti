# Landing Dalila Turcatti

Landing page responsive para Dalila Turcatti, abogada, con formulario de contacto enviado por SMTP mediante PHPMailer e integración con WhatsApp.

## Configuración

1. Instalá las dependencias:

   ```bash
   composer install
   ```

2. Duplicá `config.example.php` como `config.php`.
3. Completá en `config.php` los datos SMTP y el correo del encargado.
4. Reemplazá el número de WhatsApp en `script.js`:

   ```js
   const WHATSAPP_NUMBER = "5492944691270";
   ```

   Usá código de país + código de área + número, sin espacios ni símbolos.

5. Revisá teléfono, email, Instagram y textos de `index.html` antes de publicar.

## Prueba local

```bash
php -S localhost:8000
```

Abrí `http://localhost:8000`.

Para Gmail, activá la verificación en dos pasos y utilizá una contraseña de aplicación como `password`. No uses la contraseña normal de la cuenta.
