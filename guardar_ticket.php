<?php
file_put_contents('debug.log', print_r($_POST, true) . "\n", FILE_APPEND);
header('Content-Type: application/json');

// Obtener datos del POST (de ambas formas posibles)
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$contenido = $data['contenido'] ?? '';
$nombreArchivo = $data['nombreArchivo'] ?? '';

// Validación básica
if (empty($contenido) {
    echo json_encode(['success' => false, 'message' => 'El contenido está vacío']);
    exit;
}

// Asegurar que el nombre termine en .txt
if (!preg_match('/\.txt$/i', $nombreArchivo)) {
    $nombreArchivo .= '.txt';
}

// Crear directorio si no existe
if (!file_exists('tickets') && !mkdir('tickets', 0755, true)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo crear la carpeta tickets']);
    exit;
}

// Guardar archivo (sobreescribe si ya existe)
$ruta = 'tickets/' . $nombreArchivo;
if (file_put_contents($ruta, $contenido) === false) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
    exit;
}

// Éxito
echo json_encode([
    'success' => true,
    'message' => 'Nota guardada correctamente',
    'archivo' => $nombreArchivo,
    'ruta' => $ruta
]);