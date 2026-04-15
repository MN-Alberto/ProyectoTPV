<?php
/**
 * Worker de Procesamiento en Segundo Plano.
 * Procesa informes pesados y exportaciones de datos de forma asíncrona.
 */
require_once(__DIR__ . '/../config/confDB.php');

// Evitar bloqueos de tiempo
set_time_limit(0);
ignore_user_abort(true);

try {
    $pdo = new PDO(RUTA, USUARIO, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Si se pasa un ID específico, procesar solo ese
    $taskId = isset($argv[1]) ? intval($argv[1]) : null;

    if ($taskId) {
        procesarTarea($pdo, $taskId);
    } else {
        // Modo bucle (si se ejecuta como servicio)
        while (true) {
            $stmt = $pdo->query("SELECT id FROM tareas_segundo_plano WHERE estado = 'pendiente' ORDER BY creado_en ASC LIMIT 1");
            $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tarea) {
                procesarTarea($pdo, $tarea['id']);
            } else {
                sleep(5); // Esperar nuevas tareas
            }
        }
    }

} catch (Exception $e) {
    error_log("Worker Error: " . $e->getMessage());
}

/**
 * Lógica principal de procesamiento para una tarea.
 */
function procesarTarea($pdo, $taskId) {
    try {
        // 1. Marcar como procesando
        $stmt = $pdo->prepare("UPDATE tareas_segundo_plano SET estado = 'procesando' WHERE id = ?");
        $stmt->execute([$taskId]);

        // 2. Obtener parámetros
        $stmt = $pdo->prepare("SELECT * FROM tareas_segundo_plano WHERE id = ?");
        $stmt->execute([$taskId]);
        $tarea = $stmt->fetch(PDO::FETCH_ASSOC);
        $params = json_decode($tarea['parametros'], true);

        // 3. Simular procesamiento pesado (Aquí iría la lógica de informes.php refactorizada)
        // En un caso real, aquí llamaríamos a una función que genere el PDF/CSV
        // Para esta demo, simularemos la generación del informe masivo
        sleep(5); 

        // 4. Finalizar éxito
        $stmt = $pdo->prepare("UPDATE tareas_segundo_plano SET estado = 'completado', finalizado_en = NOW(), resultado_url = ? WHERE id = ?");
        $resultadoSimulado = "cache/informe_finalizado_" . $taskId . ".json";
        $stmt->execute([$resultadoSimulado, $taskId]);

    } catch (Exception $e) {
        $stmt = $pdo->prepare("UPDATE tareas_segundo_plano SET estado = 'error', mensaje_error = ?, finalizado_en = NOW() WHERE id = ?");
        $stmt->execute([$e->getMessage(), $taskId]);
    }
}
