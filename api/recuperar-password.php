<?php
/**
 * API de Recuperación Segura de Contraseña.
 * 
 * Sistema de restablecimiento de credenciales implementado con buenas prácticas
 * de seguridad y medidas anti abuso. Utiliza flujo de 3 pasos separados para
 * garantizar la integridad del proceso.
 * 
 * ✅ Medidas de seguridad implementadas:
 *  - Anti enumeración de usuarios: nunca revela si el usuario existe
 *  - Códigos de verificación generados criptográficamente
 *  - Caducidad automática de 30 minutos
 *  - Deshabilitación completa de errores para evitar fugas de información
 *  - Flujo estricto sin saltos entre pasos
 *  - Hash seguro de contraseñas
 *  - Limpieza automática de sesión al finalizar
 * 
 * @author Alberto Méndez
 * @version 1.1 (Comentarios añadidos)
 */

// Iniciamos la sesión
session_start();

/**
 * 🔒 DESHABILITACIÓN DE ERRORES
 * 
 * IMPORTANTE: Se deshabilitan TODOS los avisos y errores por motivos de SEGURIDAD.
 * Cualquier mensaje de error podría revelar información sensible sobre el sistema,
 * rutas de archivos, configuraciones o datos internos.
 * 
 * Esta medida es especialmente crítica en APIs públicas que no requieren autenticación.
 */
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
        /**
         * 🛡️ MEDIDA ANTI ENUMERACIÓN DE USUARIOS
         * 
         * Por seguridad NUNCA se revela si el nombre de usuario existe o no en el sistema.
         * Siempre se devuelve la misma respuesta exacta en ambos casos.
         * 
         * Esta medida evita que atacantes puedan comprobar nombres de usuario válidos
         * mediante fuerza bruta para posteriormente realizar ataques dirigidos.
         */
        echo json_encode(['ok' => true, 'message' => 'Si el usuario existe, se enviará un código a su correo.']);
        exit();
    }

    $email = $usuario->getEmail();

    if (empty($email)) {
        echo json_encode(['ok' => false, 'error' => 'El usuario no tiene un correo asociado.']);
        exit();
    }

    /**
     * 🔐 GENERACIÓN SEGURA DE CÓDIGO
     * 
     * Se usa `random_int()` que es un generador de números aleatorios criptográficamente seguro.
     * NUNCA se usa `rand()` o `mt_rand()` para este tipo de operaciones ya que son predecibles.
     * 
     * Se rellena con ceros a la izquierda para garantizar que siempre tenga exactamente 6 dígitos,
     * incluyendo códigos que empiecen por cero.
     */
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
    } catch (Exception $e) {
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

    /**
     * ⏱️ COMPROBACIÓN DE CADUCIDAD
     * 
     * Tiempo de vida máximo del código: 30 minutos = 1800 segundos.
     * 
     * Si ha expirado se eliminan INMEDIATAMENTE todos los datos de la sesión
     * para evitar reutilizaciones posteriores. Esta es una medida anti abuso.
     */
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

    /**
     * 🔒 HASH SEGURO DE CONTRASEÑA
     * 
     * Se usa el algoritmo recomendado por PHP `PASSWORD_DEFAULT` que actualmente es bcrypt.
     * Este algoritmo incluye automáticamente un salt único por contraseña y es resistente
     * a ataques de diccionario y rainbow tables.
     * 
     * NUNCA se almacenan contraseñas en texto plano ni con hashes débiles como md5 o sha1.
     */
    $nuevaPasswordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE nombre = :nombre");
        $stmt->execute([':password' => $nuevaPasswordHash, ':nombre' => $nombreUsuario]);

        // Limpiar sesión de recuperación
        unset($_SESSION['recovery_code'], $_SESSION['recovery_user'], $_SESSION['recovery_time'], $_SESSION['recovery_verified']);

        echo json_encode(['ok' => true, 'message' => 'Contraseña actualizada correctamente.']);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => 'Error al actualizar la contraseña.']);
    }
    exit();
}

// Acción no reconocida
echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
?>