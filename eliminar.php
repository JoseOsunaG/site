<?php
include("conexion.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    mysqli_query($conexion, "DELETE FROM productos WHERE id = $id");
}

header("Location: index.php");
?>