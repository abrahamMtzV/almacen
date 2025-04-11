<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Remisión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #title{
            background-color: #8B4513;
            color: white;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">

<?php
// Conexión MySQL (ajusta los datos según tu configuración)
$host = 'localhost';
$dbname = 'almacen';
$username = 'root';
$password = ''; // usualmente en XAMPP no hay contraseña

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}


// Obtener productos del formulario
$productos = $_POST['productos'] ?? [];

$sin_stock = [];
$productos_a_vender = [];

// Validación y armado de productos a vender
foreach ($productos as $id_producto => $datos) {
    if (isset($datos['activo'])) {
        $cantidad = intval($datos['cantidad']);

        // Consulta para obtener stock y costo_unitario
        $stmt = $db->prepare("SELECT producto, stock, costo_unitario FROM inventario WHERE id_producto = ?");
        $stmt->execute([$id_producto]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($producto && $producto['stock'] >= $cantidad) {
            $productos_a_vender[] = [
                'id_producto' => $id_producto,
                'cantidad' => $cantidad,
                'producto' => $producto['producto'],
                'costo_unitario' => $producto['costo_unitario'],
                'total' => $cantidad * $producto['costo_unitario']
            ];
        } else {
            $sin_stock[] = $producto['producto'] ?? "ID $id_producto";
        }
    }
}

// Si hay productos sin stock, cancelar todo
if (!empty($sin_stock)) {
    echo "<h2>No hay suficiente stock de los siguientes productos:</h2><ul>";
    foreach ($sin_stock as $p) {
        echo "<li>$p</li>";
    }
    echo "</ul><a href='javascript:history.back()'>Volver</a>";
    exit;
}

// Crear nota de remisión (ejemplo: fecha+hora)
date_default_timezone_set('America/Mexico_City'); // Ajusta a la zona horaria que corresponda

$nota_remision = 'NR' . date('YmdHis');
$fecha = date('Y-m-d H:i:s');

// Iniciar transacción
$db->beginTransaction();

try {
    foreach ($productos_a_vender as $p) {
        // Insertar en productos_vendidos
        $stmt = $db->prepare("INSERT INTO productos_vendidos (nota_remision, id_producto, fecha, cantidad, total) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nota_remision, $p['id_producto'], $fecha, $p['cantidad'], $p['total']]);

        // Restar stock
        $stmt = $db->prepare("UPDATE inventario SET stock = stock - ? WHERE id_producto = ?");
        $stmt->execute([$p['cantidad'], $p['id_producto']]);
    }

    $db->commit();

    echo "<div class='alert alert-success text-center ' id='title'><h2>¡Venta realizada con éxito!</h2></div>";
    echo "<div class='card shadow-sm mb-4'><div class='card-body'>";
    echo "<p class='fs-5'>Nota de remisión: <strong>$nota_remision</strong></p>";
    echo "<ul class='list-group list-group-flush'>";
    foreach ($productos_a_vender as $p) {
        echo "<li class='list-group-item'>{$p['producto']}: <strong>{$p['cantidad']}</strong> piezas por <strong>$" . number_format($p['total'], 2) . " MXN</strong></li>";
    }
    echo "</ul>";
    echo "</div></div>";
    echo "<a href='portal_venta.html' class='btn btn-dark'>Registrar nueva venta</a>";

} catch (Exception $e) {
    $db->rollBack();
    echo "Error al registrar venta: " . $e->getMessage();
}
?>

</div>
</body>
</html>