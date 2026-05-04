<?php
/**
 * Worker de Procesamiento en Segundo Plano.
 * Procesa informes pesados y exportaciones de datos de forma asíncrona.
 */
require_once(__DIR__ . '/../config/confDB.php');

/**
 * CONFIGURACIÓN PARA SERVICIO DAEMON
 * 
 * - Se elimina el limite de tiempo de ejecución para que se quede corriendo permanentemente
 * - ignore_user_abort(true) es CRITICAL: permite que el proceso siga corriendo
 *   aunque el usuario cierre el navegador o se corte la conexión web
 */
set_time_limit(0);
ignore_user_abort(true);

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /**
     * DOS MODOS DE FUNCIONAMIENTO
     * 
     * 1. MODO INDIVIDUAL: Se pasa el ID de la tarea por parametro CLI
     *    Procesa solo esa tarea y termina el proceso.
     * 
     * 2. MODO SERVICIO: Sin parametros
     *    Bucle infinito que escucha constantemente la cola de tareas
     */
    $taskId = isset($argv[1]) ? intval($argv[1]) : null;

    if ($taskId) {
        procesarTarea($pdo, $taskId);
    } else {
        // MODO DAEMON SERVICIO (ejecutado por systemd o supervisord)
        while (true) {
            // Buscar la tarea pendiente mas antigua (FIFO)
            $stmt = $pdo->query("SELECT id FROM tareas_segundo_plano WHERE estado = 'pendiente' ORDER BY creado_en ASC LIMIT 1");
            $tarea = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tarea) {
                procesarTarea($pdo, $tarea['id']);
            } else {
                // Si no hay tareas, dormir 5 segundos para no saturar el servidor
                sleep(5);
            }
        }
    }

} catch (Exception $e) {
    error_log("Worker Error: " . $e->getMessage());
}

/**
 * Lógica principal de procesamiento para una tarea.
 */
function procesarTarea($pdo, $taskId)
{
    try {
        /**
         * PATRON MARK AND WORK
         * 
         * PRIMERO marcar la tarea como procesando, LUEGO procesarla.
         * Esto evita que dos workers procesen la misma tarea al mismo tiempo
         * y es el sistema estandar para colas de trabajo.
         */
        $stmt = $pdo->prepare("UPDATE tareas_segundo_plano SET estado = 'procesando' WHERE id = ?");
        $stmt->execute([$taskId]);

        // Cargar parametros de la tarea guardados en JSON
        $stmt = $pdo->prepare("SELECT * FROM tareas_segundo_plano WHERE id = ?");
        $stmt->execute([$taskId]);
        $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
        $params = json_decode($tarea['parametros'], true);

        /**
         * PROCESAMIENTO REAL
         * 
         * Aqui es donde iria la logica real de generacion de los informes pesados,
         * exportaciones a CSV/PDF, calculos masivos y cualquier operación
         * que tarde mas de 30 segundos y no se pueda ejecutar por web.
         * 
         * Actualmente esta puesto un sleep(5) como demostración.
         */
        sleep(5);

        // Marcar tarea como completada correctamente y guardar ruta del resultado
        $stmt = $pdo->prepare("UPDATE tareas_segundo_plano SET estado = 'completado', finalizado_en = NOW(), resultado_url = ? WHERE id = ?");
        $resultadoSimulado = "cache/informe_finalizado_" . $taskId . ".json";
        $stmt->execute([$resultadoSimulado, $taskId]);

    } catch (Exception $e) {
        // En caso de error, marcar tarea como fallida y guardar el mensaje de error
        $stmt = $pdo->prepare("UPDATE tareas_segundo_plano SET estado = 'error', mensaje_error = ?, finalizado_en = NOW() WHERE id = ?");
        $stmt->execute([$e->getMessage(), $taskId]);
    }
}
