<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, bool $success, string $message): never
{
    http_response_code($status);
    echo json_encode(
        ['success' => $success, 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, false, 'Método no permitido.');
}

$autoload = __DIR__ . '/vendor/autoload.php';
$configFile = __DIR__ . '/config.php';

if (!is_file($autoload)) {
    respond(500, false, 'Falta instalar PHPMailer. Ejecutá composer install.');
}

if (!is_file($configFile)) {
    respond(500, false, 'Falta configurar el correo del sitio.');
}

require $autoload;
$config = require $configFile;

$clean = static fn (string $key): string => trim((string) ($_POST[$key] ?? ''));

$name = $clean('name');
$email = filter_var($clean('email'), FILTER_VALIDATE_EMAIL);
$phone = $clean('phone');
$area = $clean('area');
$message = $clean('message');
$privacy = $clean('privacy');
$honeypot = $clean('website');
$startedAt = filter_var($_POST['form_started'] ?? null, FILTER_VALIDATE_INT);

$allowedAreas = [
    'Derecho de Familia',
    'Sucesiones',
    'Derecho Laboral para Trabajadores',
    'Asesoramiento para Empresas y PyMEs',
    'Otra consulta',
];

if ($honeypot !== '') {
    respond(200, true, 'Gracias. Recibimos tu consulta.');
}

if ($startedAt && time() - $startedAt < 3) {
    respond(429, false, 'El formulario se envió demasiado rápido. Intentá nuevamente.');
}

if (
    mb_strlen($name) < 2 ||
    mb_strlen($name) > 100 ||
    $email === false ||
    mb_strlen($phone) > 30 ||
    !in_array($area, $allowedAreas, true) ||
    mb_strlen($message) < 10 ||
    mb_strlen($message) > 3000 ||
    $privacy !== 'accepted'
) {
    respond(422, false, 'Revisá los datos ingresados e intentá nuevamente.');
}

$safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars((string) $email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safePhone = htmlspecialchars($phone !== '' ? $phone : 'No informado', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeArea = htmlspecialchars($area, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = (string) $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $config['smtp']['username'];
    $mail->Password = (string) $config['smtp']['password'];
    $mail->Port = (int) $config['smtp']['port'];
    $mail->CharSet = PHPMailer::CHARSET_UTF8;

    $encryption = strtolower((string) $config['smtp']['encryption']);
    $mail->SMTPSecure = $encryption === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;

    $mail->setFrom(
        (string) $config['mail']['from_address'],
        (string) $config['mail']['from_name']
    );
    $mail->addAddress(
        (string) $config['mail']['to_address'],
        (string) $config['mail']['to_name']
    );
    $mail->addReplyTo((string) $email, $name);

    $mail->isHTML(true);
    $mail->Subject = "Nueva consulta web: {$area}";
    $mail->Body = <<<HTML
        <div style="font-family:Arial,sans-serif;max-width:640px;color:#17201d">
            <h2 style="color:#173f35">Nueva consulta desde el sitio web</h2>
            <table style="width:100%;border-collapse:collapse">
                <tr><td style="padding:10px;border-bottom:1px solid #ddd"><strong>Nombre</strong></td><td style="padding:10px;border-bottom:1px solid #ddd">{$safeName}</td></tr>
                <tr><td style="padding:10px;border-bottom:1px solid #ddd"><strong>Email</strong></td><td style="padding:10px;border-bottom:1px solid #ddd">{$safeEmail}</td></tr>
                <tr><td style="padding:10px;border-bottom:1px solid #ddd"><strong>Teléfono</strong></td><td style="padding:10px;border-bottom:1px solid #ddd">{$safePhone}</td></tr>
                <tr><td style="padding:10px;border-bottom:1px solid #ddd"><strong>Área</strong></td><td style="padding:10px;border-bottom:1px solid #ddd">{$safeArea}</td></tr>
            </table>
            <h3 style="margin-top:24px">Mensaje</h3>
            <p style="padding:16px;background:#f7f5ef">{$safeMessage}</p>
        </div>
        HTML;
    $mail->AltBody = "Nueva consulta web\n\nNombre: {$name}\nEmail: {$email}\nTeléfono: {$phone}\nÁrea: {$area}\n\nMensaje:\n{$message}";

    $mail->send();
    respond(200, true, '¡Gracias! Tu consulta fue enviada correctamente.');
} catch (Exception $exception) {
    error_log('Error de PHPMailer: ' . $mail->ErrorInfo);
    respond(500, false, 'No pudimos enviar tu consulta. Intentá nuevamente o escribinos por WhatsApp.');
}
