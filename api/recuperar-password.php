<?php
/**
 * API de Recuperación de Contraseña.
 * Maneja el envío de códigos de verificación y cambio de contraseña.
 */

// Iniciamos la sesión
session_start();

// Deshabilitar mostrar errores para que la respuesta sea JSON limpio
error_reporting(0);
ini_set('display_errors', 0);

// Cabecera JSON
header('Content-Type: application/json; charset=utf-8');

// Verificar que es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit();
}

// Obtener la acción
$action = $_POST['action'] ?? '';

// Cargar dependencias necesarias
require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Usuario.php');

// Cargar PHPMailer
require_once __DIR__ . '/../core/Exception.php';
require_once __DIR__ . '/../core/PHPMailer.php';
require_once __DIR__ . '/../core/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$pdo = ConexionDB::getInstancia()->getConexion();

// Acción: Enviar código de recuperación
if ($action === 'send_recovery_code') {
    $nombre = trim($_POST['nombre'] ?? '');

    if (empty($nombre)) {
        echo json_encode(['ok' => false, 'error' => 'El nombre de usuario es obligatorio.']);
        exit();
    }

    // Buscar usuario por nombre
    $usuario = Usuario::buscarPorNombre($nombre);

    if (!$usuario) {
        // Por seguridad, no revelar si el usuario existe o no
        echo json_encode(['ok' => true, 'message' => 'Si el usuario existe, se enviará un código a su correo.']);
        exit();
    }

    $email = $usuario->getEmail();

    if (empty($email)) {
        echo json_encode(['ok' => false, 'error' => 'El usuario no tiene un correo asociado.']);
        exit();
    }

    // Generar código de 6 dígitos
    $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Guardar código en sesión
    $_SESSION['recovery_code'] = $codigo;
    $_SESSION['recovery_user'] = $nombre;
    $_SESSION['recovery_time'] = time();

    // Enviar correo con el código
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'albertomennun04@gmail.com';
        $mail->Password = 'jdpq cfwd whpm ekmc';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Remitente
        $mail->setFrom('albertomennun04@gmail.com', 'TPV Bazar');

        // Destinatario
        $mail->addAddress($email, $usuario->getNombre());

        // Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Código de recuperación de contraseña - TPV Bazar';
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h2 style="color: #333;">Recuperación de Contraseña</h2>
            <p>Has solicitado recuperar tu contraseña en <strong>TPV Bazar</strong>.</p>
            <div style="background: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;">
                <p style="margin: 0; font-size: 14px; color: #666;">Tu código de verificación es:</p>
                <p style="margin: 10px 0 0 0; font-size: 32px; font-weight: bold; color: #333; letter-spacing: 8px;">' . $codigo . '</p>
            </div>
            <p style="color: #666; font-size: 12px;">Este código expira en 30 minutos.</p>
            <p style="color: #666; font-size: 12px;">Si no has solicitado este código, puedes ignorar este mensaje.</p>
        </div>
        ';
        $mail->AltBody = 'Tu código de verificación es: ' . $codigo . '. Este código expira en 30 minutos.';

        $mail->send();

        echo json_encode(['ok' => true, 'message' => 'Código enviado correctamente.']);
    }
    catch (Exception $e) {
        error_log('Error al enviar correo: ' . $mail->ErrorInfo);
        echo json_encode(['ok' => false, 'error' => 'Error al enviar el correo. Inténtalo más tarde.']);
    }
    exit();
}

// Acción: Verificar código
if ($action === 'verify_recovery_code') {
    $codigoIngresado = trim($_POST['codigo'] ?? '');

    if (empty($codigoIngresado)) {
        echo json_encode(['ok' => false, 'error' => 'El código es obligatorio.']);
        exit();
    }

    // Verificar que existe una sesión de recuperación
    if (!isset($_SESSION['recovery_code']) || !isset($_SESSION['recovery_time'])) {
        echo json_encode(['ok' => false, 'error' => 'No hay una solicitud de recuperación activa.']);
        exit();
    }

    // Verificar que no ha expirado (30 minutos)
    $tiempoTranscurrido = time() - $_SESSION['recovery_time'];
    if ($tiempoTranscurrido > 1800) {
        unset($_SESSION['recovery_code'], $_SESSION['recovery_user'], $_SESSION['recovery_time']);
        echo json_encode(['ok' => false, 'error' => 'El código ha expirado. Solicita uno nuevo.']);
        exit();
    }

    // Verificar código
    if ($codigoIngresado !== $_SESSION['recovery_code']) {
        echo json_encode(['ok' => false, 'error' => 'Código incorrecto.']);
        exit();
    }

    // Código válido, marcar como verificado
    $_SESSION['recovery_verified'] = true;

    echo json_encode(['ok' => true, 'message' => 'Código verificado correctamente.']);
    exit();
}

// Acción: Cambiar contraseña
if ($action === 'change_password') {
    $nuevaPassword = $_POST['password'] ?? '';
    $confirmarPassword = $_POST['confirm_password'] ?? '';

    // Validaciones
    if (empty($nuevaPassword) || empty($confirmarPassword)) {
        echo json_encode(['ok' => false, 'error' => 'Ambas contraseñas son obligatorias.']);
        exit();
    }

    if ($nuevaPassword !== $confirmarPassword) {
        echo json_encode(['ok' => false, 'error' => 'Las contraseñas no coinciden.']);
        exit();
    }

    if (strlen($nuevaPassword) < 6) {
        echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.']);
        exit();
    }

    // Verificar que la sesión está validada
    if (!isset($_SESSION['recovery_verified']) || !$_SESSION['recovery_verified'] || !isset($_SESSION['recovery_user'])) {
        echo json_encode(['ok' => false, 'error' => 'Debes verificar el código primero.']);
        exit();
    }

    $nombreUsuario = $_SESSION['recovery_user'];

    // Buscar usuario
    $usuario = Usuario::buscarPorNombre($nombreUsuario);

    if (!$usuario) {
        echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado.']);
        exit();
    }

    // Actualizar contraseña
    $nuevaPasswordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE nombre = :nombre");
        $stmt->execute([':password' => $nuevaPasswordHash, ':nombre' => $nombreUsuario]);

        // Limpiar sesión de recuperación
        unset($_SESSION['recovery_code'], $_SESSION['recovery_user'], $_SESSION['recovery_time'], $_SESSION['recovery_verified']);

        echo json_encode(['ok' => true, 'message' => 'Contraseña actualizada correctamente.']);
    }
    catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => 'Error al actualizar la contraseña.']);
    }
    exit();
}

// Acción no reconocida
echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
?>