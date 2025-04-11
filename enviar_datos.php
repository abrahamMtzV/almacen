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

$host = 'dpg-cvsn13ndiees73fka790-a';
$port = '5432';
$dbname = 'almacen_vc7a';
$user = 'root';
$password = 'TmOZtEO2VQKk33HCTZYOtjtfPsSOPLlY';

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo "<div class='alert alert-danger'>Error al conectar a PostgreSQL.</div>";
    exit;
}

$productos = $_POST['productos'] ?? [];

$sin_stock = [];
$productos_a_vender = [];

foreach ($productos as $id_producto => $datos) {
    if (isset($datos['activo'])) {
        $cantidad = intval($datos['cantidad']);

        $query = "SELECT producto, stock, costo_unitario FROM inventario WHERE id_producto = $1";
        $result = pg_query_params($conn, $query, [$id_producto]);

        if ($result && pg_num_rows($result) > 0) {
            $producto = pg_fetch_assoc($result);

            if ($producto['stock'] >= $cantidad) {
                $productos_a_vender[] = [
                    'id_producto' => $id_producto,
                    'cantidad' => $cantidad,
                    'producto' => $producto['producto'],
                    'costo_unitario' => $producto['costo_unitario'],
                    'total' => $cantidad * $producto['costo_unitario']
                ];
            } else {
                $sin_stock[] = $producto['producto'];
            }
        } else {
            $sin_stock[] = "ID $id_producto";
        }
    }
}

if (!empty($sin_stock)) {
    echo "<h2>No hay suficiente stock de los siguientes productos:</h2><ul>";
    foreach ($sin_stock as $p) {
        echo "<li>$p</li>";
    }
    echo "</ul><a href='javascript:history.back()'>Volver</a>";
    exit;
}

$nota_remision = 'NR' . date('YmdHis');
$fecha = date('Y-m-d H:i:s');

pg_query($conn, 'BEGIN');
$todo_bien = true;

foreach ($productos_a_vender as $p) {
    $insert = "INSERT INTO productos_vendidos (nota_remision, id_producto, fecha, cantidad, total) VALUES ($1, $2, $3, $4, $5)";
    $res_insert = pg_query_params($conn, $insert, [$nota_remision, $p['id_producto'], $fecha, $p['cantidad'], $p['total']]);

    $update = "UPDATE inventario SET stock = stock - $1 WHERE id_producto = $2";
    $res_update = pg_query_params($conn, $update, [$p['cantidad'], $p['id_producto']]);

    if (!$res_insert || !$res_update) {
        $todo_bien = false;
        break;
    }
}

if ($todo_bien) {
    pg_query($conn, 'COMMIT');

    echo "<div class='alert alert-success text-center' id='title'><h2 class='shadow-mm'>¡Venta realizada con éxito!</h2></div>";
    echo "<div class='card shadow-sm mb-4 border border-2' style='border-color:#8B4513'><div class='card-body'>";
    echo "<p class='fs-5'>Nota de remisión: <strong>$nota_remision</strong></p>";
    echo "<ul class='list-group list-group-flush'>";
    foreach ($productos_a_vender as $p) {
        echo "<li class='list-group-item'>{$p['prodcto']}: <strong>{$p['cantidad']}</strong> piezas por <strong>$" . number_format($p['total'], 2) . " MXN</strong></li>";
    }
    echo "</ul></div></div>";
    echo "<a href='index.html' class='btn btn-dark'>Volver al inicio</a>";

} else {
    pg_query($conn, 'ROLLBACK');
    echo "<div class='alert alert-danger'>Error al registrar venta. Intente nuevamente.</div>";
}

?>

</div>
</body>
</html>
