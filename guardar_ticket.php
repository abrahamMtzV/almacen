<?php
// Ruta donde se guardarán los archivos
$carpeta = 'tickets/';

// Verificar si se recibió la información correctamente
if (isset($_POST['contenido']) && isset($_POST['nombreArchivo'])) {
    $contenido = $_POST['contenido'];
    $nombreArchivo = $_POST['nombreArchivo'];

    // Asegúrate de que la carpeta "tickets" exista
    if (!file_exists($carpeta)) {
        if (!mkdir($carpeta, 0777, true)) {
            // Si no se pudo crear la carpeta, enviar error
            echo json_encode(['success' => false, 'message' => 'No se pudo crear la carpeta "tickets".']);
            exit;
        }
    }

    // Crear la ruta completa del archivo
    $rutaArchivo = $carpeta . $nombreArchivo;

    // Intentar guardar el archivo
    if (file_put_contents($rutaArchivo, $contenido)) {
        // Respuesta exitosa
        echo json_encode(['success' => true]);
    } else {
        // Si hay un error al guardar el archivo
        echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo en la carpeta "tickets".']);
    }
} else {
    // Si faltan datos
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
}
?>
