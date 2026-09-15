<?php
    header('Content-Type: application/jason; charset=utf-8);

    require '../config.php';

    $sqlCategoria = "select * from categoria order by categoria";
    $consultaCategoria = "


