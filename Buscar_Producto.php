<?php
include("conexion.php");


$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : '';

if (!empty($codigo)) {
    
    $codigo = mysqli_real_escape_string($conexion, $codigo);
    
    $query = "SELECT nombre, precio FROM productos WHERE codigo_barras = '$codigo' LIMIT 1";
    $resultado = mysqli_query($conexion, $query);

    if ($row = mysqli_fetch_assoc($resultado)) {
        
        echo json_encode([
            'success' => true,
            'nombre' => $row['nombre'],
            'precio' => $row['precio']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No encontrado']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Código vacío']);
}
?>