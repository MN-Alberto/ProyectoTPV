<?php
/**
 * API de Recuperación Segura de Contraseña (Versión DB).
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit();
}

$action = $_POST['action'] ?? '';

require_once(__DIR__ . '/../config/confDB.php');
require_once(__DIR__ . '/../model/Usuario.php');
require_once __DIR__ . '/../core/Exception.php';
require_once __DIR__ . '/../core/PHPMailer.php';
require_once __DIR__ . '/../core/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Acción: Enviar código de recuperación
    if ($action === 'send_recovery_code') {
        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            echo json_encode(['ok' => false, 'error' => 'El nombre de usuario es obligatorio.']);
            exit();
        }

        $usuario = Usuario::buscarPorNombre($nombre);
        if (!$usuario) {
            echo json_encode(['ok' => true, 'message' => 'Si el usuario existe, se enviará un código a su correo.']);
            exit();
        }

        $email = $usuario->getEmail();
        if (empty($email)) {
            echo json_encode(['ok' => false, 'error' => 'El usuario no tiene un correo asociado.']);
            exit();
        }

        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiracion = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // Borrar solicitudes previas de este usuario
        $stmt = $pdo->prepare("DELETE FROM recuperacion_password WHERE usuario = ?");
        $stmt->execute([$nombre]);

        // Insertar nueva solicitud
        $stmt = $pdo->prepare("INSERT INTO recuperacion_password (usuario, codigo, expiracion) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $codigo, $expiracion]);

        // Enviar correo
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'albertomennun04@gmail.com';
        $mail->Password = 'jdpq cfwd whpm ekmc';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('albertomennun04@gmail.com', 'TPV Bazar');
        $mail->addAddress($email, $usuario->getNombre());
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '🔐 Código de seguridad - Restablecer Contraseña';
        
        $mail->Body = '
        <div style="background-color: #f3f4f6; padding: 40px 20px; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <div style="max-width: 500px; margin: 0 auto; background-color: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);">
                <!-- Header -->
                <div style="background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); padding: 40px 30px; text-align: center;">
                    <div style="background: rgba(255,255,255,0.2); width: 60px; height: 60px; border-radius: 18px; margin: 0 auto 20px; display: table;">
                        <span style="display: table-cell; vertical-align: middle; color: white; font-size: 30px;">🔐</span>
                    </div>
                    <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">Restablecer Contraseña</h1>
                </div>

                <!-- Content -->
                <div style="padding: 40px 35px; text-align: center;">
                    <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                        Hola <strong>' . htmlspecialchars($usuario->getNombre()) . '</strong>,<br>
                        Has solicitado restablecer tu contraseña. Utiliza el siguiente código de verificación para completar el proceso:
                    </p>

                    <!-- Code Box -->
                    <div style="background-color: #f9fafb; border: 2px dashed #e5e7eb; border-radius: 20px; padding: 25px; margin-bottom: 30px;">
                        <span style="display: block; color: #9ca3af; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">Tu código de seguridad</span>
                        <span style="display: block; color: #111827; font-size: 42px; font-weight: 800; letter-spacing: 10px; margin-left: 10px;">' . $codigo . '</span>
                    </div>

                    <p style="color: #ef4444; font-size: 13px; font-weight: 600; margin-bottom: 0;">
                        ⏱️ Este código caducará en 30 minutos.
                    </p>
                </div>

                <!-- Footer -->
                <div style="background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #f1f5f9;">
                    <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                        Si no has solicitado este cambio, puedes ignorar este correo con seguridad.<br>
                        <strong>TPV Bazar System</strong>
                    </p>
                </div>
            </div>
        </div>';

        $mail->send();

        echo json_encode(['ok' => true, 'message' => 'Código enviado correctamente.']);
        exit();
    }

    // Acción: Verificar código
    if ($action === 'verify_recovery_code') {
        $codigoIngresado = trim($_POST['codigo'] ?? '');
        if (empty($codigoIngresado)) {
            echo json_encode(['ok' => false, 'error' => 'El código es obligatorio.']);
            exit();
        }

        $stmt = $pdo->prepare("SELECT * FROM recuperacion_password WHERE codigo = ? AND expiracion > NOW() AND verificado = 0");
        $stmt->execute([$codigoIngresado]);
        $recup = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recup) {
            echo json_encode(['ok' => false, 'error' => 'Código incorrecto o expirado.']);
            exit();
        }

        // Marcar como verificado
        $stmt = $pdo->prepare("UPDATE recuperacion_password SET verificado = 1 WHERE id = ?");
        $stmt->execute([$recup['id']]);

        echo json_encode(['ok' => true, 'message' => 'Código verificado correctamente.', 'temp_token' => $recup['codigo']]);
        exit();
    }

    // Acción: Cambiar contraseña
    if ($action === 'change_password') {
        $nuevaPassword = $_POST['password'] ?? '';
        $codigo = $_POST['temp_token'] ?? '';

        if (empty($nuevaPassword) || empty($codigo)) {
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos.']);
            exit();
        }

        $stmt = $pdo->prepare("SELECT * FROM recuperacion_password WHERE codigo = ? AND verificado = 1 AND expiracion > NOW()");
        $stmt->execute([$codigo]);
        $recup = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recup) {
            echo json_encode(['ok' => false, 'error' => 'Sesión de recuperación inválida.']);
            exit();
        }

        $nuevaPasswordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE nombre = ?");
        $stmt->execute([$nuevaPasswordHash, $recup['usuario']]);

        // Borrar registro de recuperación
        $stmt = $pdo->prepare("DELETE FROM recuperacion_password WHERE id = ?");
        $stmt->execute([$recup['id']]);

        echo json_encode(['ok' => true, 'message' => 'Contraseña actualizada correctamente.']);
        exit();
    }

    echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>