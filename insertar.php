<?php
include("conexion.php");

if (!empty($_POST['nombre'])) {
    $codigo = $_POST['codigo_barras'];
    $nombre = $_POST['nombre'];
    $cat    = $_POST['categoria'];
    $precio = $_POST['precio'];

    $query = "INSERT INTO productos (codigo_barras, nombre, categoria, precio) 
              VALUES ('$codigo', '$nombre', '$cat', '$precio')";
    
    if (mysqli_query($conexion, $query)) {
        header("Location: index.php?msg=Producto guardado");
    } else {
        echo "Error: " . mysqli_error($conexion);
    }
}
?>